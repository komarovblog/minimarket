<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/**
 * Source of immutable purchase-cost snapshots for newly created orders.
 * A missing procurement cost is NULL, not a fictional zero profit/loss.
 * Virtual bundles derive cost from their component quantities recursively.
 * This is not stock accounting and never mutates inventory.
 */
final class PurchaseCost
{
    public static function cents(?string $amount):?int
    {
        if($amount===null||trim($amount)==='')return null;
        $value=trim($amount);
        if(!preg_match('/^(0|[1-9][0-9]{0,12})(?:\.([0-9]{1,2}))?$/D',$value,$m))
            throw new \InvalidArgumentException('Invalid procurement amount');
        return (int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
    }

    /** @return array<int,int|null> Map product ID to per-unit cost. */
    public static function snapshot(DatabaseInterface $db,array $lines):array
    {
        $cache=[];
        $resolve=static function(int $productId,int $depth,array $path)use($db,&$cache,&$resolve):?int {
            if($productId<1||$depth>8||isset($path[$productId]))return null;
            if(array_key_exists($productId,$cache))return $cache[$productId];
            $path[$productId]=true;
            $db->setQuery('SELECT product_type,cost_price FROM '
                .$db->quoteName('#__minimarket_products').' WHERE id='.$productId);
            $product=$db->loadAssoc();
            if(!$product)return $cache[$productId]=null;
            if((string)$product['product_type']!=='bundle')
                return $cache[$productId]=self::cents($product['cost_price']);
            $db->setQuery('SELECT component_product_id,quantity FROM '
                .$db->quoteName('#__minimarket_bundle_items')
                .' WHERE bundle_product_id='.$productId.' ORDER BY id ASC');
            $parts=(array)$db->loadAssocList();
            if(!$parts||count($parts)>40)return $cache[$productId]=null;
            $total=0;
            foreach($parts as $part){
                $qty=(int)$part['quantity'];
                $unit=$resolve((int)$part['component_product_id'],$depth+1,$path);
                if($qty<1||$qty>1000||$unit===null)return $cache[$productId]=null;
                $total+=$unit*$qty;
                if($total>999999999999999)return $cache[$productId]=null;
            }
            return $cache[$productId]=$total;
        };
        foreach($lines as $line){
            $id=(int)$line['product_id'];
            $resolve($id,0,[]);
        }
        return $cache;
    }
}
