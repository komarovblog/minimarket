<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\StockReservation;

/**
 * One primary Order status, independent Payment and Fulfillment facts.
 * Manual acceptance and documented cancellation are BUSINESS OPERATIONS,
 * not arbitrary status overrides. They must always remain available to staff.
 */
final class OrderLifecycle
{
    public const STATES=['new','confirmed','completed','cancelled'];
    public const FULFILLMENT=['awaiting_assembly','assembling','ready',
        'in_transit','awaiting_pickup','delivered','failed'];

    /** Legacy setting now controls exceptional status override (not ordinary operations). */
    public static function manualEnabled(): bool
    {
        return (int)ComponentHelper::getParams('com_minimarket')
            ->get('order_manual_status_enabled',1)===1;
    }

    public static function assertCancelable(DatabaseInterface $db,array $o):void
    {
        if(!in_array((string)($o['status']??''),['new','confirmed'],true))
            throw new \DomainException('COM_MINIMARKET_ORDER_TRANSITION_DENIED');
        // A payment attempt with unknown outcome must be reconciled first.
        if(($o['payment_status']??'')==='processing')
            throw new \DomainException('COM_MINIMARKET_ORDER_PAYMENT_PROCESSING');
        if(in_array((string)($o['fulfillment_status']??''),
            ['shipped','in_transit','awaiting_pickup','delivered','failed'],true))
            throw new \DomainException('COM_MINIMARKET_ORDER_ALREADY_DISPATCHED');
        $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_shipments')
            .' WHERE order_id='.(int)$o['id']
            ." AND status IN ('in_transit','awaiting_pickup','delivered','failed')");
        if((int)$db->loadResult()>0)
            throw new \DomainException('COM_MINIMARKET_ORDER_ALREADY_DISPATCHED');
    }

    /** The same transitions apply to normal actions regardless of override setting. */
    public static function validateManual(DatabaseInterface $db,array $o,string $next):void
    {
        $previous=(string)($o['status']??'');
        if($previous===$next)return;
        if($previous==='new'&&$next==='confirmed')return;
        if(in_array($previous,['new','confirmed'],true)&&$next==='cancelled'){
            self::assertCancelable($db,$o);
            return;
        }
        // 'completed' is ALWAYS derived from proven money and handing-over.
        throw new \DomainException('COM_MINIMARKET_ORDER_TRANSITION_DENIED');
    }

    /**
     * Caller transaction protects order first, then shipment and stock rows.
     * Only original/re-delivery shipments affect initial Order completion.
     * Replacement shipments belong to Return cases and never reopen an Order.
     */
    public static function refresh(DatabaseInterface $db,int $orderId,int $actorId=0):void
    {
        $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_orders')
            .' WHERE id='.(int)$orderId.' FOR UPDATE');
        $o=$db->loadAssoc();
        if(!$o||$o['deleted_at']!==null
           ||in_array((string)$o['status'],['cancelled','completed'],true))return;
        $fromStatus=(string)$o['status'];
        $fromFulfillment=(string)$o['fulfillment_status'];
        $status=$fromStatus;
        $fulfillment=$fromFulfillment;
        if($o['delivery_method']==='courier'){
            $db->setQuery('SELECT status FROM '.$db->quoteName('#__minimarket_shipments')
                .' WHERE order_id='.(int)$orderId
                ." AND reason IN ('initial','redelivery','other') AND status<>'cancelled'");
            $states=(array)$db->loadColumn();
            if($states){
                // A failed previous attempt does not block a successful re-delivery.
                $viable=array_values(array_filter($states,static fn($s)=>$s!=='failed'));
                if(!$viable) $fulfillment='failed';
                elseif(!array_diff($viable,['delivered']))$fulfillment='delivered';
                elseif(in_array('in_transit',$viable,true))$fulfillment='in_transit';
                elseif(in_array('awaiting_pickup',$viable,true))$fulfillment='awaiting_pickup';
                elseif(in_array('ready',$viable,true))$fulfillment='ready';
                elseif(in_array('assembling',$viable,true))$fulfillment='assembling';
                else $fulfillment='awaiting_assembly';
            }
        }elseif($o['delivery_method']==='pickup'){
            // Fulfillment comes from a confirmed store-handling action, not a calendar.
            if($fulfillment==='pending')$fulfillment='awaiting_assembly';
        }
        // A paid order remains New until an employee explicitly accepts it.
        if($status==='confirmed'&&$o['payment_status']==='paid'
           &&$fulfillment==='delivered')$status='completed';
        if($status===$fromStatus&&$fulfillment===$fromFulfillment){
            if(in_array($fulfillment,['in_transit','awaiting_pickup','delivered'],true))
                StockReservation::fulfill($db,$orderId);
            return;
        }

        $sets=[];
        if($status!==$fromStatus)$sets[]='status='.$db->quote($status);
        if($fulfillment!==$fromFulfillment){
            $sets[]='fulfillment_status='.$db->quote($fulfillment);
            $sets[]='fulfilled_at='.($fulfillment==='delivered'
                ?'COALESCE(fulfilled_at,UTC_TIMESTAMP())':'NULL');
        }
        $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
            .' SET '.implode(',',$sets).' WHERE id='.(int)$orderId);
        $db->execute();
        if($fulfillment!==$fromFulfillment)
            OrderHistory::record($db,$orderId,$actorId,'fulfillment',
                $fromFulfillment,$fulfillment);
        if($status!==$fromStatus)
            OrderHistory::record($db,$orderId,$actorId,'status',$fromStatus,$status);

        // Hand-over consumes the held stock; never releases it into resale.
        if($fulfillment==='delivered'||in_array($fulfillment,['in_transit','awaiting_pickup'],true))
            StockReservation::fulfill($db,$orderId);
    }
}
