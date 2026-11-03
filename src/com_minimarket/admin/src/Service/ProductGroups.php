<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Groups independently indexable products without changing their URLs, SKUs
 * or category. All changes are part of the parent product-save transaction.
 */
final class ProductGroups
{
    public static function currentId(DatabaseInterface $db,int $productId): int
    {
        if ($productId<1) return 0;
        $q=$db->getQuery(true)->select('group_id')
            ->from($db->quoteName('#__minimarket_product_group_members'))
            ->where($db->quoteName('product_id').' = '.$productId);
        $db->setQuery($q);
        return (int)$db->loadResult();
    }

    public static function options(DatabaseInterface $db): array
    {
        $q=$db->getQuery(true)->select(['id','title'])
            ->from($db->quoteName('#__minimarket_product_groups'))
            ->order('title ASC,id ASC');
        $db->setQuery($q);
        return (array)$db->loadAssocList();
    }

    public static function normalize(mixed $id,mixed $newTitle): array
    {
        if (!is_int($id) && !is_string($id) && $id !== null) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        if (!is_string($newTitle) && $newTitle !== null) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        $selected=trim((string)($id??'0'));
        $title=trim((string)($newTitle??''));
        if (!preg_match('/^(0|[1-9][0-9]{0,9})$/D',$selected)
            || (float)$selected>4294967295
            || mb_strlen($title)>255 || preg_match('/[\x00-\x1f<>]/u',$title)
            || ($title!=='' && (int)$selected!==0)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        return [(int)$selected,$title];
    }

    public static function assign(DatabaseInterface $db,int $productId,int $groupId,string $newTitle): void
    {
        if ($productId<1 || ($groupId>0 && $newTitle!=='')) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        if ($newTitle!=='') {
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_product_groups'))
                ->columns($db->quoteName(['title']))->values($db->quote($newTitle));
            $db->setQuery($q);$db->execute();
            $groupId=(int)$db->insertid();
            if ($groupId<1) throw new \RuntimeException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        if ($groupId>0) {
            $q=$db->getQuery(true)->select('id')->from($db->quoteName('#__minimarket_product_groups'))
                ->where($db->quoteName('id').' = '.$groupId);
            $db->setQuery($q);
            if ((int)$db->loadResult()!==$groupId) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
        }
        $previous=self::currentId($db,$productId);
        if ($groupId===$previous) return;
        if ($groupId===0) {
            $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_product_group_members'))
                ->where($db->quoteName('product_id').' = '.$productId);
        } elseif ($previous>0) {
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_product_group_members'))
                ->set($db->quoteName('group_id').' = '.$groupId)
                ->where($db->quoteName('product_id').' = '.$productId);
        } else {
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_product_group_members'))
                ->columns($db->quoteName(['product_id','group_id']))
                ->values($productId.','.$groupId);
        }
        $db->setQuery($q);$db->execute();
    }
}
