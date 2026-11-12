<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/** Derived bundle availability; does not reserve stock or change existing SKU. */
final class BundleStock
{
    public static function snapshot(DatabaseInterface $db):array
    {
        $q=$db->getQuery(true)->select(['id','category_id','product_type','delivery_type','state','stock','option_stock_mode'])
            ->from($db->quoteName('#__minimarket_products'));
        $db->setQuery($q); $products=[];
        foreach ((array)$db->loadAssocList() as $r) $products[(int)$r['id']]=$r;

        $q=$db->getQuery(true)->select(['id','parent_id','state'])
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q); $categories=[];
        foreach ((array)$db->loadAssocList() as $r) $categories[(int)$r['id']]=$r;

        $q=$db->getQuery(true)->select(['id','product_id','stock','state'])
            ->from($db->quoteName('#__minimarket_product_variants'));
        $db->setQuery($q); $variants=[]; $hasVariants=[];
        foreach ((array)$db->loadAssocList() as $r) {
            $variants[(int)$r['id']]=$r;
            $hasVariants[(int)$r['product_id']]=true;
        }

        $q=$db->getQuery(true)->select(['bundle_product_id','component_product_id','component_variant_id','quantity'])
            ->from($db->quoteName('#__minimarket_bundle_items'))->order('sort_order ASC,id ASC');
        $db->setQuery($q); $items=[];
        foreach ((array)$db->loadAssocList() as $r) $items[(int)$r['bundle_product_id']][]=$r;
        return compact('products','categories','variants','hasVariants','items');
    }

    /** Aggregates repeated leaf demand, including across nested bundles. */
    public static function available(int $id,array $data):int
    {
        $p=$data['products']??[]; $cats=$data['categories']??[];
        $v=$data['variants']??[]; $hasV=$data['hasVariants']??[]; $items=$data['items']??[];
        if (($p[$id]['product_type']??'')!=='bundle') return 0;
        $visible=function(int $productId) use ($p,$cats):bool {
            $product=$p[$productId]??null;
            if (!$product || (int)$product['state']!==1) return false;
            $catId=(int)$product['category_id']; $seen=[];
            while($catId>0){
                if (isset($seen[$catId])) return false;
                $seen[$catId]=true; $c=$cats[$catId]??null;
                if (!$c || (int)$c['state']!==1) return false;
                $catId=(int)$c['parent_id'];
            }
            return true;
        };
        if (!$visible($id)) return 0;
        $demand=[]; $invalid=false;
        $walk=function(int $bundle,int $factor,int $depth,array $path)
            use (&$walk,&$invalid,&$demand,$p,$v,$hasV,$items,$visible):void {
            if ($invalid || $depth>8 || isset($path[$bundle])) { $invalid=true; return; }
            $rows=$items[$bundle]??[];
            if (!$rows || count($rows)>40){$invalid=true;return;}
            $path[$bundle]=true;
            foreach($rows as $r){
                $pid=(int)$r['component_product_id']; $qty=(int)$r['quantity'];
                if ($qty<1 || $qty>1000 || !$visible($pid)) {$invalid=true;return;}
                $needed=min(10000000000,$factor*$qty);
                if (($p[$pid]['product_type']??'simple')==='bundle') {
                    if ($r['component_variant_id']!==null){$invalid=true;return;}
                    $walk($pid,(int)$needed,$depth+1,$path);
                } else {
                    $vid=(int)($r['component_variant_id']??0);
                    if ($vid>0) {
                        $variant=$v[$vid]??null;
                        if (!$variant || (int)$variant['state']!==1 ||
                            (int)$variant['product_id']!==$pid){$invalid=true;return;}
                        $key=(int)$p[$pid]['option_stock_mode']===0 ? 'p:'.$pid : 'v:'.$vid;
                    } else {
                        if (isset($hasV[$pid])){$invalid=true;return;}
                        $key='p:'.$pid;
                    }
                    $demand[$key]=min(10000000000,($demand[$key]??0)+(int)$needed);
                }
                if($invalid)return;
            }
        };
        $walk($id,1,0,[]);
        if($invalid || !$demand)return 0;
        $stock=4294967295;
        foreach($demand as $key=>$qty){
            [$kind,$sid]=explode(':',$key,2);
            $onHand=$kind==='v'?(int)($v[(int)$sid]['stock']??0):(int)($p[(int)$sid]['stock']??0);
            $stock=min($stock,(int)floor($onHand/$qty));
        }
        return max(0,$stock);
    }

    public static function components(DatabaseInterface $db,int $bundleId):array
    {
        $q=$db->getQuery(true)->select(['i.component_product_id','i.component_variant_id',
            'i.quantity','p.title','p.sku','p.state','p.category_id','p.product_type',
            'v.sku AS variant_sku'])
            ->from($db->quoteName('#__minimarket_bundle_items','i'))
            ->join('INNER',$db->quoteName('#__minimarket_products','p').' ON p.id=i.component_product_id')
            ->join('LEFT',$db->quoteName('#__minimarket_product_variants','v').' ON v.id=i.component_variant_id')
            ->where('i.bundle_product_id='.(int)$bundleId)->order('i.sort_order ASC,i.id ASC');
        $db->setQuery($q); return (array)$db->loadAssocList();
    }
}
