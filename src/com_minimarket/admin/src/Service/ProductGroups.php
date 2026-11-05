<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Optional many-to-many relationships between independent products.
 * No group owns a product, URL, SKU, stock, images or SEO data.
 * Call all mutations inside the owning Joomla AdminModel transaction.
 */
final class ProductGroups
{
    public const TYPES = ['model', 'similar', 'collection', 'accessory', 'bundle'];

    public static function normalizeIds(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        $ids = [];
        foreach ($value as $id) {
            if ((!is_int($id) && !is_string($id))
                || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $id)
                || (float) $id > 4294967295 || isset($ids[(int) $id])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
            $ids[(int) $id] = (int) $id;
        }
        return array_values($ids);
    }

    public static function normalizeNewTitle(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        $title = trim($value);
        if (mb_strlen($title) > 255 || preg_match('/[\x00-\x1f<>]/u', $title)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        return $title;
    }

    public static function currentIds(DatabaseInterface $db, int $productId): array
    {
        if ($productId < 1) return [];
        $q = $db->getQuery(true)->select('group_id')
            ->from($db->quoteName('#__minimarket_product_group_members','m'))
            ->join('INNER',$db->quoteName('#__minimarket_product_groups','g').' ON g.id=m.group_id')
            ->join('INNER',$db->quoteName('#__minimarket_products','p').' ON p.id=m.product_id')
            ->where('m.product_id = '.$productId)
            ->where("((g.type='bundle' AND p.product_type='bundle') OR (g.type<>'bundle' AND p.product_type='simple'))")
            ->order('group_id ASC');
        $db->setQuery($q);
        return array_map('intval', (array) $db->loadColumn());
    }

    public static function memberIds(DatabaseInterface $db, int $groupId): array
    {
        if ($groupId < 1) return [];
        $q = $db->getQuery(true)->select('product_id')
            ->from($db->quoteName('#__minimarket_product_group_members'))
            ->where($db->quoteName('group_id') . ' = ' . $groupId)
            ->order('sort_order ASC, product_id ASC');
        $db->setQuery($q);
        return array_map('intval', (array) $db->loadColumn());
    }

    public static function options(DatabaseInterface $db,string $productType='simple'): array
    {
        $q = $db->getQuery(true)->select(['id', 'title', 'state'])
            ->from($db->quoteName('#__minimarket_product_groups'))
            ->where($productType==='bundle' ? "type='bundle'" : "type<>'bundle'")
            ->order('title ASC, id ASC');
        $db->setQuery($q);
        return (array) $db->loadAssocList();
    }

    public static function members(DatabaseInterface $db, int $groupId): array
    {
        if ($groupId < 1) return [];
        $q = $db->getQuery(true)->select(['p.id', 'p.title', 'p.state'])
            ->from($db->quoteName('#__minimarket_product_group_members', 'm'))
            ->join('INNER', $db->quoteName('#__minimarket_products', 'p')
                . ' ON p.id = m.product_id')
            ->where('m.group_id = ' . $groupId)
            ->order('m.sort_order ASC, p.id ASC');
        $db->setQuery($q);
        return (array) $db->loadAssocList();
    }

    public static function forProduct(DatabaseInterface $db, int $productId): array
    {
        $result = [];
        foreach (self::currentIds($db, $productId) as $groupId) {
            $q = $db->getQuery(true)->select(['id','title','type','state'])
                ->from($db->quoteName('#__minimarket_product_groups'))
                ->where('id = ' . $groupId);
            $db->setQuery($q);
            $group = $db->loadAssoc();
            if ($group) {
                $group['members'] = self::members($db, $groupId);
                $result[] = $group;
            }
        }
        return $result;
    }

    private static function assertExisting(DatabaseInterface $db, array $ids, string $table): void
    {
        if (!$ids) return;
        $q = $db->getQuery(true)->select('id')
            ->from($db->quoteName($table))
            ->where('id IN (' . implode(',', $ids) . ')');
        $db->setQuery($q);
        $found = array_map('intval', (array) $db->loadColumn());
        sort($found);
        $expected = $ids;
        sort($expected);
        if ($found !== $expected) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
    }

    public static function replaceForProduct(
        DatabaseInterface $db, int $productId, array $groupIds, string $newTitle = ''
    ): void {
        if ($productId < 1) throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        $groupIds = self::normalizeIds($groupIds);
        $newTitle = self::normalizeNewTitle($newTitle);
        self::assertExisting($db, $groupIds, '#__minimarket_product_groups');
        if ($newTitle !== '') {
            $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_product_groups'))
                ->columns($db->quoteName(['title', 'type', 'state']))
                ->values($db->quote($newTitle) . ',' . $db->quote('similar') . ',1');
            $db->setQuery($q);
            $db->execute();
            $groupIds[] = (int) $db->insertid();
        }
        $old = self::currentIds($db, $productId);
        foreach (array_diff($old, $groupIds) as $groupId) {
            $q = $db->getQuery(true)->delete($db->quoteName('#__minimarket_product_group_members'))
                ->where('group_id = ' . (int) $groupId)->where('product_id = ' . $productId);
            $db->setQuery($q);
            $db->execute();
        }
        foreach (array_diff($groupIds, $old) as $groupId) {
            $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_product_group_members'))
                ->columns($db->quoteName(['product_id', 'group_id']))
                ->values($productId . ',' . (int) $groupId);
            $db->setQuery($q);
            $db->execute();
        }
    }

    public static function replaceForGroup(DatabaseInterface $db, int $groupId, array $productIds): void
    {
        if ($groupId < 1) throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        $productIds = self::normalizeIds($productIds);
        self::assertExisting($db, $productIds, '#__minimarket_products');
        // A bundle relationship must never contain ordinary products, and
        // ordinary product relations must never contain a bundle.
        $q=$db->getQuery(true)->select('type')->from($db->quoteName('#__minimarket_product_groups'))
            ->where('id='.$groupId);
        $db->setQuery($q);
        $type=(string)$db->loadResult();
        if($type==='')throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        if($productIds){
            $q=$db->getQuery(true)->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_products'))
                ->where('id IN ('.implode(',',$productIds).')')
                ->where($type==='bundle' ? "product_type<>'bundle'" : "product_type<>'simple'");
            $db->setQuery($q);
            if((int)$db->loadResult()>0)throw new \InvalidArgumentException('COM_MINIMARKET_GROUP_SCOPE_INVALID');
        }
        $old = self::memberIds($db, $groupId);
        foreach (array_diff($old, $productIds) as $productId) {
            $q = $db->getQuery(true)->delete($db->quoteName('#__minimarket_product_group_members'))
                ->where('group_id = ' . $groupId)->where('product_id = ' . (int) $productId);
            $db->setQuery($q);
            $db->execute();
        }
        foreach (array_diff($productIds, $old) as $productId) {
            $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_product_group_members'))
                ->columns($db->quoteName(['group_id', 'product_id']))
                ->values($groupId . ',' . (int) $productId);
            $db->setQuery($q);
            $db->execute();
        }
    }
}
