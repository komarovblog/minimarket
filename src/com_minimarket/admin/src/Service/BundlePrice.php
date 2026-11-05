<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/** Read-only calculation; no writes, no reservation, no stored price overrides. */
final class BundlePrice
{
    public static function snapshot(DatabaseInterface $db):array
    {
        $q=$db->getQuery(true)->select(['id','price','product_type','bundle_price_mode',
            'bundle_discount_pct'])
            ->from($db->quoteName('#__minimarket_products'));
        $db->setQuery($q);
        $products=[];
        foreach((array)$db->loadAssocList() as $r)$products[(int)$r['id']]=$r;
        $q=$db->getQuery(true)->select(['bundle_product_id','component_product_id','quantity'])
            ->from($db->quoteName('#__minimarket_bundle_items'));
        $db->setQuery($q);$items=[];
        foreach((array)$db->loadAssocList() as $r){
            $items[(int)$r['bundle_product_id']][]=$r;
        }
        return ['products'=>$products,'items'=>$items];
    }

    /** Returns price and component total in integer cents, with cycle guard. */
    public static function calculate(int $productId,array $snapshot):array
    {
        $products=$snapshot['products']??[]; $items=$snapshot['items']??[];
        $walk=function(int $id,int $depth,array $seen) use (&$walk,$products,$items):array {
            if($depth>8 || isset($seen[$id]) || !isset($products[$id]))
                return ['cents'=>0,'components'=>0,'valid'=>false];
            $p=$products[$id];
            $own=max(0,(int)round((float)$p['price']*100));
            if(($p['product_type']??'simple')!=='bundle')
                return ['cents'=>$own,'components'=>$own,'valid'=>true];
            $seen[$id]=true; $sum=0;
            foreach($items[$id]??[] as $row) {
                $qty=(int)$row['quantity'];
                if($qty<1 || $qty>1000)return ['cents'=>0,'components'=>0,'valid'=>false];
                $child=$walk((int)$row['component_product_id'],$depth+1,$seen);
                if(!$child['valid'])return ['cents'=>0,'components'=>0,'valid'=>false];
                $sum += $child['cents']*$qty;
                if($sum > 9007199254740991)return ['cents'=>0,'components'=>0,'valid'=>false];
            }
            if(($p['bundle_price_mode']??'fixed')==='discount') {
                $percent=max(0,min(100,(float)($p['bundle_discount_pct']??0)));
                $cents=(int)round($sum*(100-$percent)/100);
            } else {
                $cents=$own;
            }
            return ['cents'=>max(0,$cents),'components'=>$sum,'valid'=>true];
        };
        $result=$walk($productId,0,[]);
        return [
            'price'=>number_format($result['cents']/100,2,'.',''),
            'component_total'=>number_format($result['components']/100,2,'.',''),
            'discount_amount'=>number_format(max(0,$result['components']-$result['cents'])/100,2,'.',''),
            'valid'=>$result['valid'],
        ];
    }
}
