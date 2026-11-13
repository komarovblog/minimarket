<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\BundleStock;

/**
 * Physical stock allocation is committed with OrderStorage::create().
 * Product/variant stock is the FREE quantity; the ledger is the source of
 * committed quantities. Never use the sales order's payment flag to release it.
 */
final class StockReservation
{
    /** Build a deduplicated demand vector for direct variants and nested bundles. */
    public static function demand(array $lines, array $snapshot): array
    {
        $products=$snapshot['products']??[];
        $variants=$snapshot['variants']??[];
        $hasVariants=$snapshot['hasVariants']??[];
        $bundles=$snapshot['items']??[];
        $demand=[];

        $leaf=static function (int $pid,int $vid,int $qty) use(
            &$demand,$products,$variants,$hasVariants
        ):void {
            $p=$products[$pid]??null;
            if(!$p||(int)$p['state']!==1||($p['product_type']??'')!=='simple'
                ||$qty<1||$qty>500000)
                throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
            if(($p['delivery_type']??'physical')==='digital')return;
            if($vid>0){
                $v=$variants[$vid]??null;
                if(!$v||(int)$v['product_id']!==$pid||(int)$v['state']!==1)
                    throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
                $key=(int)$p['option_stock_mode']===0?'product:'.$pid:'variant:'.$vid;
            }else{
                if(isset($hasVariants[$pid]))
                    throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
                $key='product:'.$pid;
            }
            $demand[$key]=($demand[$key]??0)+$qty;
            if($demand[$key]>500000)throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
        };
        $walk=static function (int $bid,int $qty,int $depth,array $visited) use(
            &$walk,$leaf,$bundles,$products
        ):void{
            if($depth>8||isset($visited[$bid])
               ||($products[$bid]['product_type']??'')!=='bundle'
               ||(int)($products[$bid]['state']??0)!==1)
                throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
            $rows=$bundles[$bid]??[];
            if(!$rows||count($rows)>40)throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
            $visited[$bid]=true;
            foreach($rows as $row){
                $pid=(int)$row['component_product_id'];
                $units=(int)$row['quantity'];
                $need=$qty*$units;
                if($units<1||$need>500000)throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
                if(($products[$pid]['product_type']??'')==='bundle'){
                    if($row['component_variant_id']!==null)
                        throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
                    $walk($pid,$need,$depth+1,$visited);
                }else{
                    $leaf($pid,(int)($row['component_variant_id']??0),$need);
                }
            }
        };
        foreach($lines as $line){
            if(($line['delivery_type']??'physical')==='digital')continue;
            $pid=(int)($line['product_id']??0);
            $vid=(int)($line['variant_id']??0);
            $qty=(int)($line['quantity']??0);
            if($qty<1||$qty>500000)throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
            if(($products[$pid]['product_type']??'')==='bundle'){
                if($vid!==0)throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
                $walk($pid,$qty,0,[]);
            }else $leaf($pid,$vid,$qty);
        }
        ksort($demand,SORT_STRING);
        return $demand;
    }

    /** Caller transaction owns the order row and snapshots. */
    public static function allocate(DatabaseInterface $db,int $orderId,array $lines,bool $pickup):void
    {
        if((int)ComponentHelper::getParams('com_minimarket')->get('inventory_enabled',1)!==1){
            return; // Inventory disabled: do not pretend that units are reserved.
        }
        $snapshot=BundleStock::snapshot($db);
        $need=self::demand($lines,$snapshot);
        if(!$need)return;
        foreach($need as $key=>$quantity){
            [$kind,$rawId]=explode(':',$key,2);
            $sourceId=(int)$rawId;
            $table=$kind==='product'?'#__minimarket_products':'#__minimarket_product_variants';
            $db->setQuery('UPDATE '.$db->quoteName($table)
               .' SET stock=stock-'.$quantity.' WHERE id='.$sourceId
               .' AND state=1 AND stock>='.$quantity);
            $db->execute();
            if((int)$db->getAffectedRows()!==1)
                throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
            $db->setQuery('INSERT INTO '.$db->quoteName('#__minimarket_stock_allocations')
                .' (order_id,source_kind,source_id,quantity,status,created_at) VALUES ('
                .$orderId.','.$db->quote($kind).','.$sourceId.','.$quantity
                .','.$db->quote('held').',UTC_TIMESTAMP())');
            $db->execute();
        }
        $days=self::days('pickup_unpaid_reserve_days',3);
        $until=$pickup?'DATE_ADD(UTC_TIMESTAMP(), INTERVAL '.$days.' DAY)':'NULL';
        $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
            .' SET reservation_state='.$db->quote('held')
            .', reserved_until='.$until.' WHERE id='.$orderId);
        $db->execute();
    }

    public static function days(string $name,int $default):int
    {
        $raw=(int)ComponentHelper::getParams('com_minimarket')->get($name,$default);
        return max(1,min(90,$raw));
    }

    /** Locks order before allocation rows. Must run inside one DB transaction. */
    public static function release(DatabaseInterface $db,int $orderId):void
    {
        $db->setQuery('SELECT status,reservation_state,fulfillment_status FROM '
            .$db->quoteName('#__minimarket_orders').' WHERE id='.$orderId.' FOR UPDATE');
        $order=$db->loadAssoc();
        if(!$order)throw new \DomainException('COM_MINIMARKET_ORDER_STATUS_INVALID');
        if($order['reservation_state']!=='held')return; // historical records: no invented release
        $db->setQuery('SELECT id,source_kind,source_id,quantity,status FROM '
            .$db->quoteName('#__minimarket_stock_allocations')
            .' WHERE order_id='.$orderId.' ORDER BY source_kind,source_id FOR UPDATE');
        $rows=(array)$db->loadAssocList();
        foreach($rows as $a){
            if($a['status']!=='held')continue;
            $id=(int)$a['id'];
            $kind=(string)$a['source_kind'];
            $table=$kind==='product'?'#__minimarket_products':'#__minimarket_product_variants';
            if(!in_array($kind,['product','variant'],true))
                throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
            $qty=(int)$a['quantity'];$source=(int)$a['source_id'];
            $db->setQuery('UPDATE '.$db->quoteName($table).' SET stock=stock+'.$qty
                .' WHERE id='.$source);
            $db->execute();
            if((int)$db->getAffectedRows()!==1)
                throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
            $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_stock_allocations')
                .' SET status='.$db->quote('released').', released_at=UTC_TIMESTAMP()'
                .' WHERE id='.$id." AND status='held'");
            $db->execute();
        }
        $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
            .' SET reservation_state='.$db->quote('released')
            .', reserved_until=NULL WHERE id='.$orderId);
        $db->execute();
    }

    /** Delivery is confirmed; hold becomes fulfilled, not released to stock. */
    public static function fulfill(DatabaseInterface $db,int $orderId):void
    {
        $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_stock_allocations')
            ." SET status='fulfilled' WHERE order_id=".$orderId." AND status='held'");
        $db->execute();
        $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
            ." SET reservation_state='fulfilled', reserved_until=NULL"
            ." WHERE id=".$orderId." AND reservation_state='held'");
        $db->execute();
    }
}
