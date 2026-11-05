<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Validates the compact relationship editor and saves all membership edits
 * through the parent ProductModel / GroupModel transaction. Never deletes a
 * product record, only explicit group deletions and membership changes.
 */
final class GroupEditor
{
    public static function normalize(mixed $input, string $mode): array
    {
        if (!in_array($mode, ['product','group'], true) ||
            !is_string($input) || strlen($input) > 250000) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        try { $rows = json_decode($input, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        if (!is_array($rows) || !array_is_list($rows) || count($rows) > 100 ||
            ($mode === 'group' && count($rows) !== 1)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        $out=[];$seenGroups=[];
        foreach ($rows as $g) {
            if (!is_array($g) || !isset($g['id']) ||
                (!is_int($g['id']) && !ctype_digit((string)$g['id'])) ||
                (int)$g['id'] < 0 || !isset($g['members']) ||
                !is_array($g['members']) || !array_is_list($g['members']) ||
                count($g['members']) > 1000) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
            $id=(int)$g['id'];
            if ($id > 0 && isset($seenGroups[$id])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
            if ($id > 0) $seenGroups[$id]=true;
            $title=ProductGroups::normalizeNewTitle($g['title']??'');
            if ($mode==='product' && $title === '' &&
                empty($g['delete'])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
            if (($g['delete']??false)!==false && ($g['delete']??false)!==true) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
            if (($g['delete']??false) && ($id < 1 || $mode!=='product')) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
            $members=[];$seen=[];
            foreach ($g['members'] as $m) {
                if (!is_array($m) || !isset($m['id']) ||
                    !array_key_exists('enabled',$m) ||
                    !is_bool($m['enabled'])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
                }
                $mid=$m['id'];
                if ($mid!=='current' &&
                    (!is_int($mid) && (!is_string($mid) ||
                        !preg_match('/^[1-9][0-9]{0,9}$/D',$mid)))) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
                }
                $key=(string)$mid;
                if (isset($seen[$key])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
                }
                $seen[$key]=true;
                if ($mid==='current' && $mode!=='product') {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
                }
                $members[]=['id'=>$mid,'enabled'=>$m['enabled']];
            }
            $out[]=['id'=>$id,'title'=>$title,
                'delete'=>(bool)($g['delete']??false),'members'=>$members];
        }
        return $out;
    }

    private static function memberIds(array $members, int $currentId): array
    {
        $ids=[];
        foreach ($members as $member) {
            if (!$member['enabled']) continue;
            $id=$member['id']==='current' ? $currentId : (int)$member['id'];
            if ($id < 1) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
            $ids[]=$id;
        }
        return ProductGroups::normalizeIds($ids);
    }

    private static function updateTitle(DatabaseInterface $db, int $id, string $title): void
    {
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_product_groups'))
            ->set($db->quoteName('title').' = '.$db->quote($title))
            ->where('id = '.$id);
        $db->setQuery($q);$db->execute();
    }

    private static function requireGroup(DatabaseInterface $db, int $id): void
    {
        $q=$db->getQuery(true)->select('id')
            ->from($db->quoteName('#__minimarket_product_groups'))
            ->where('id = '.$id);
        $db->setQuery($q);
        if ((int)$db->loadResult() !== $id) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
    }

    /**
     * Save all relationships visible from this product. A saved group missing
     * from the payload is a validation error, not a destructive mass detach.
     */
    public static function saveProduct(
        DatabaseInterface $db, int $productId, array $rows
    ): void {
        if ($productId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        $q=$db->getQuery(true)->select('product_type')
            ->from($db->quoteName('#__minimarket_products'))->where('id='.$productId);
        $db->setQuery($q);
        $productType=(string)$db->loadResult();
        if(!in_array($productType,['simple','bundle'],true))
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        $original=ProductGroups::currentIds($db,$productId);
        $present=array_column($rows,'id');
        foreach ($original as $gid) {
            if (!in_array($gid,$present,true)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
        }
        foreach ($rows as $row) {
            $id=$row['id'];
            if ($id > 0) {
                self::requireGroup($db,$id);
                $q=$db->getQuery(true)->select('type')
                    ->from($db->quoteName('#__minimarket_product_groups'))->where('id='.$id);
                $db->setQuery($q);
                $groupType=(string)$db->loadResult();
                if (($productType==='bundle')!==($groupType==='bundle'))
                    throw new \InvalidArgumentException('COM_MINIMARKET_GROUP_SCOPE_INVALID');
            }
            if ($row['delete']) {
                // Explicit, confirmed action: unlink all products and delete
                // the group, leaving every product itself untouched.
                $q=$db->getQuery(true)
                    ->delete($db->quoteName('#__minimarket_product_group_members'))
                    ->where('group_id = '.$id);
                $db->setQuery($q);$db->execute();
                $q=$db->getQuery(true)
                    ->delete($db->quoteName('#__minimarket_product_groups'))
                    ->where('id = '.$id);
                $db->setQuery($q);$db->execute();
                continue;
            }
            $members=self::memberIds($row['members'],$productId);
            if ($id===0) {
                // A new group can survive removal of its founding product.
                // It remains independently visible in the Groups section, even
                // when no members remain. Only explicit group deletion removes it.
                $q=$db->getQuery(true)
                    ->insert($db->quoteName('#__minimarket_product_groups'))
                    ->columns($db->quoteName(['title','type','state']))
                    ->values($db->quote($row['title']).','.$db->quote(
                        $productType==='bundle'?'bundle':'similar').',1');
                $db->setQuery($q);$db->execute();
                $id=(int)$db->insertid();
                if ($id<1) throw new \RuntimeException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            } else {
                self::updateTitle($db,$id,$row['title']);
            }
            ProductGroups::replaceForGroup($db,$id,$members);
        }
    }

    /** Edit group members from the standalone «Связи товаров» screen. */
    public static function saveGroup(
        DatabaseInterface $db, int $groupId, array $rows
    ): void {
        if ($groupId < 1 || count($rows)!==1 ||
            !in_array($rows[0]['id'],[0,$groupId],true) ||
            $rows[0]['delete']) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
        }
        ProductGroups::replaceForGroup(
            $db,$groupId,self::memberIds($rows[0]['members'],0)
        );
    }
}
