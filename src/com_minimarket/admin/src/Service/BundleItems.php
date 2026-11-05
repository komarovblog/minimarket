<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

final class BundleItems
{
    public const MAX_ITEMS=40;
    public const MAX_DEPTH=8;

    public static function formRows(DatabaseInterface $db,int $productId):array
    {
        if ($productId<1) return [];
        $q=$db->getQuery(true)->select(['component_product_id','component_variant_id','quantity'])
            ->from($db->quoteName('#__minimarket_bundle_items'))
            ->where('bundle_product_id='.$productId)->order('sort_order ASC,id ASC');
        $db->setQuery($q); $out=[];
        foreach ((array)$db->loadAssocList() as $row) {
            $out['row'.count($out)]=[
                'component'=>$row['component_variant_id']===null
                    ? 'product:'.(int)$row['component_product_id']
                    : 'variant:'.(int)$row['component_variant_id'],
                'quantity'=>(int)$row['quantity']
            ];
        }
        return $out;
    }

    /** @return array<int,array{kind:string,id:int,quantity:int}> */
    public static function normalize(mixed $raw):array
    {
        if ($raw===null || $raw==='') return [];
        if (!is_array($raw) || count($raw)>self::MAX_ITEMS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_BUNDLE_INVALID');
        }
        $out=[]; $seen=[];
        foreach (array_values($raw) as $row) {
            if (!is_array($row)) throw new \InvalidArgumentException('COM_MINIMARKET_BUNDLE_INVALID');
            $choice=trim((string)($row['component']??''));
            $qty=(string)($row['quantity']??'');
            if (!preg_match('/^(product|variant):([1-9][0-9]*)$/D',$choice,$m)
                || strlen($m[2])>10 || (float)$m[2]>4294967295
                || !preg_match('/^[1-9][0-9]{0,3}$/D',$qty)
                || (int)$qty>1000 || isset($seen[$choice])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_BUNDLE_INVALID');
            }
            $seen[$choice]=true;
            $out[]=['kind'=>$m[1],'id'=>(int)$m[2],'quantity'=>(int)$qty];
        }
        return $out;
    }

    /** Validate membership, whole-product/SKU choice and absence of cycles. */
    public static function validate(DatabaseInterface $db,int $bundleId,array $rows):array
    {
        if ($bundleId<1 || count($rows)>self::MAX_ITEMS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_BUNDLE_INVALID');
        }
        $q=$db->getQuery(true)->select(['id','product_type'])
            ->from($db->quoteName('#__minimarket_products'));
        $db->setQuery($q); $products=[];
        foreach ((array)$db->loadAssocList() as $p) $products[(int)$p['id']]=$p['product_type'];
        $q=$db->getQuery(true)->select(['id','product_id'])
            ->from($db->quoteName('#__minimarket_product_variants'));
        $db->setQuery($q); $variants=[]; $hasVariants=[];
        foreach ((array)$db->loadAssocList() as $v) {
            $variants[(int)$v['id']]=(int)$v['product_id'];
            $hasVariants[(int)$v['product_id']]=true;
        }
        $resolved=[];
        foreach ($rows as $r) {
            $id=(int)$r['id']; $variant=null;
            if ($r['kind']==='variant') {
                $variant=$id; $id=$variants[$variant]??0;
            }
            if ($id<1 || !isset($products[$id]) || $id===$bundleId
                || ($variant!==null && $products[$id]!=='simple')
                || ($variant===null && $products[$id]==='simple' && isset($hasVariants[$id]))) {
                throw new \InvalidArgumentException('COM_MINIMARKET_BUNDLE_COMPONENT_INVALID');
            }
            $resolved[]=['product_id'=>$id,'variant_id'=>$variant,'quantity'=>(int)$r['quantity']];
        }
        $q=$db->getQuery(true)->select(['bundle_product_id','component_product_id'])
            ->from($db->quoteName('#__minimarket_bundle_items'));
        $db->setQuery($q); $edges=[];
        foreach ((array)$db->loadAssocList() as $r) {
            $edges[(int)$r['bundle_product_id']][]=(int)$r['component_product_id'];
        }
        $edges[$bundleId]=array_column($resolved,'product_id');
        $walk=function(int $id,int $depth,array $path) use (&$walk,$edges,$products):void {
            if ($depth>self::MAX_DEPTH || isset($path[$id])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_BUNDLE_CYCLE');
            }
            if (($products[$id]??'simple')!=='bundle') return;
            $path[$id]=true;
            foreach (($edges[$id]??[]) as $child) $walk($child,$depth+1,$path);
        };
        $walk($bundleId,0,[]);
        return $resolved;
    }

    /** ProductModel owns transaction. FK prevents removing a referenced item. */
    public static function replace(DatabaseInterface $db,int $bundleId,array $resolved):void
    {
        $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_bundle_items'))
            ->where('bundle_product_id='.$bundleId);
        $db->setQuery($q); $db->execute();
        foreach ($resolved as $position=>$row) {
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_bundle_items'))
                ->columns($db->quoteName(['bundle_product_id','component_product_id',
                    'component_variant_id','quantity','sort_order']))
                ->values($bundleId.','.(int)$row['product_id'].','.
                    ($row['variant_id']===null?'NULL':(int)$row['variant_id']).','.
                    (int)$row['quantity'].','.$position);
            $db->setQuery($q); $db->execute();
        }
    }
}
