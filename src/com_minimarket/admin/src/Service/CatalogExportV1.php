<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Conservative export of the fields supported by CatalogImport v1.
 * Do not silently downgrade incompatible attributes, missing base SKUs
 * or links. Full backup of variants, media, SEO etc is a separate feature.
 */
final class CatalogExportV1
{
    private static function rows(DatabaseInterface $db, string $table, string $order): array
    {
        $q = $db->getQuery(true)->select('*')->from($db->quoteName($table));
        if ($order !== '') $q->order($order);
        $db->setQuery($q);
        return (array)$db->loadAssocList();
    }

    private static function checkCount(array $doc): void
    {
        $count=0;
        foreach (CatalogTabularV1::FIELDS as $section=>$_) $count+=count($doc[$section]??[]);
        if ($count>2500) throw new \RuntimeException('COM_MINIMARKET_EXPORT_TOO_MANY');
    }

    public static function document(DatabaseInterface $db): array
    {
        $doc=['version'=>1,'source'=>'minimarket-export'];
        foreach (CatalogTabularV1::FIELDS as $section=>$_) $doc[$section]=[];
        $categoryIds=[]; $attributeIds=[]; $optionIds=[]; $productIds=[]; $groupIds=[];
        foreach (self::rows($db,'#__minimarket_categories','id ASC') as $r) {
            $id=(int)$r['id'];
            $categoryIds[$id]='category-'.$id;
        }
        foreach (self::rows($db,'#__minimarket_categories','id ASC') as $r) {
            $id=(int)$r['id'];$pid=(int)($r['parent_id']??0);
            if ($pid!==0 && !isset($categoryIds[$pid])) {
                throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            }
            $doc['categories'][]=[
                'key'=>$categoryIds[$id],
                'title'=>(string)$r['title'],
                'alias'=>(string)$r['alias'],
                'parent'=>$pid>0?$categoryIds[$pid]:null,
                'state'=>(int)$r['state'],
            ];
        }

        foreach (self::rows($db,'#__minimarket_attributes','id ASC') as $r) {
            $id=(int)$r['id'];
            $type=(string)$r['input_type'];
            if (!in_array($type,['text','number','editor','boolean','document','color'],true)) {
                throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            }
            $attributeIds[$id]='attribute-'.$id;
            $doc['attributes'][]=['key'=>$attributeIds[$id],'title'=>(string)$r['title'],
                'code'=>(string)$r['code'],'type'=>$type,'state'=>(int)$r['state']];
        }
        $attrRows=self::rows($db,'#__minimarket_attribute_values','attribute_id ASC, sort_order ASC, id ASC');
        $attrMap=[];
        foreach ($attrRows as $row) {
            $id=(int)$row['attribute_id'];
            if (!isset($attributeIds[$id])) throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            $attrMap[$id][]=[
                'code'=>(string)$row['code'],'title'=>(string)$row['title'],
                'state'=>(int)$row['state'],
                ...((string)($row['color_hex']??'')!==''?
                    ['color_hex'=>(string)$row['color_hex']]:[]),
            ];
        }
        foreach ($doc['attributes'] as &$item) {
            $id=(int)substr($item['key'],strlen('attribute-'));
            if (!empty($attrMap[$id])) {
                if ($item['type']!=='color') {
                    // v1 imports dictionary values only for color attributes.
                    throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
                }
                $item['values']=$attrMap[$id];
            }
        }
        unset($item);

        foreach (self::rows($db,'#__minimarket_order_options','id ASC') as $r) {
            $id=(int)$r['id'];$optionIds[$id]='option-'.$id;
            $doc['options'][]=['key'=>$optionIds[$id],'title'=>(string)$r['title'],
                'code'=>(string)$r['code'],'state'=>(int)$r['state']];
        }
        $optionMap=[];
        foreach (self::rows($db,'#__minimarket_order_option_values','option_id ASC, sort_order ASC, id ASC') as $r) {
            $id=(int)$r['option_id'];
            if (!isset($optionIds[$id])) throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            $optionMap[$id][]=['code'=>(string)$r['code'],'title'=>(string)$r['title'],
                'state'=>(int)$r['state'],
                ...((string)($r['color_hex']??'')!==''?
                    ['color_hex'=>(string)$r['color_hex']]:[])];
        }
        foreach ($doc['options'] as &$item) {
            $id=(int)substr($item['key'],strlen('option-'));
            if (!empty($optionMap[$id])) $item['values']=$optionMap[$id];
        }
        unset($item);

        foreach (self::rows($db,'#__minimarket_products','id ASC') as $r) {
            $id=(int)$r['id'];$catId=(int)($r['category_id']??0);
            if (!isset($categoryIds[$catId]) || trim((string)$r['sku'])==='') {
                // Empty base SKU may be valid for variant SKUs; the current
                // import format cannot restore it. Fail rather than invent.
                throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            }
            $productIds[$id]='product-'.$id;
            $doc['products'][]=[
                'key'=>$productIds[$id], 'title'=>(string)$r['title'],
                'alias'=>(string)$r['alias'],'sku'=>(string)$r['sku'],
                'price'=>(string)$r['price'], 'stock'=>(int)$r['stock'],
                'stock_mode'=>(int)$r['option_stock_mode'],
                'category'=>$categoryIds[$catId], 'state'=>(int)$r['state'],
            ];
        }
        foreach (self::rows($db,'#__minimarket_product_groups','id ASC') as $r) {
            $id=(int)$r['id'];
            if ((string)$r['type']!=='similar') {
                // v1 imports links as 'similar' only.
                throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            }
            $groupIds[$id]=count($doc['links']);
            $doc['links'][]=['key'=>'link-'.$id,'title'=>(string)$r['title'],
                'state'=>(int)$r['state'],'members'=>[]];
        }
        foreach (self::rows($db,'#__minimarket_product_group_members','group_id ASC, sort_order ASC, product_id ASC') as $r) {
            $gid=(int)$r['group_id'];$pid=(int)$r['product_id'];
            if (!isset($groupIds[$gid],$productIds[$pid])) {
                throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            }
            $doc['links'][$groupIds[$gid]]['members'][]=$productIds[$pid];
        }
        foreach (self::rows($db,'#__minimarket_category_attributes','category_id ASC, attribute_id ASC') as $r) {
            $catId=(int)$r['category_id'];$attributeId=(int)$r['attribute_id'];
            if (!isset($categoryIds[$catId],$attributeIds[$attributeId])) {
                throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            }
            $mode=(int)$r['enabled']===0?'disabled':
                ((int)$r['required']===1?'required':'optional');
            $doc['category_attributes'][]=[
                'key'=>'catattr-'.$catId.'-'.$attributeId,
                'category'=>$categoryIds[$catId],
                'attribute'=>$attributeIds[$attributeId],'mode'=>$mode,
            ];
        }
        foreach (self::rows($db,'#__minimarket_category_order_options','category_id ASC, option_id ASC') as $r) {
            $catId=(int)$r['category_id'];$optionId=(int)$r['option_id'];
            if (!isset($categoryIds[$catId],$optionIds[$optionId])) {
                throw new \RuntimeException('COM_MINIMARKET_EXPORT_INCOMPATIBLE');
            }
            $mode=match((int)$r['mode']) {0=>'disabled',1=>'optional',2=>'required',default=>'inherit'};
            $doc['category_options'][]=[
                'key'=>'catoption-'.$catId.'-'.$optionId,
                'category'=>$categoryIds[$catId],
                'option'=>$optionIds[$optionId],'mode'=>$mode,
            ];
        }
        self::checkCount($doc);
        // The import engine is the single authority for v1 compatibility.
        return CatalogImport::decode(json_encode($doc,JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
