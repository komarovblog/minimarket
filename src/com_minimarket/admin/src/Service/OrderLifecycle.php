<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;

/** Independent Order / Payment / Fulfillment lifecycle. No invented provider events. */
final class OrderLifecycle
{
    public static function manualEnabled(): bool
    {
        return (int) ComponentHelper::getParams('com_minimarket')
            ->get('order_manual_status_enabled', 1) === 1;
    }

    public static function validateManual(DatabaseInterface $db, array $order, string $next): void
    {
        if (!self::manualEnabled()) {
            throw new \DomainException('COM_MINIMARKET_ORDER_MANUAL_DISABLED');
        }
        $current = (string) ($order['status'] ?? '');
        if ($current === $next) return;
        $allowed = ['new'=>['confirmed','cancelled'],
                    'confirmed'=>['completed','cancelled'],
                    'completed'=>[], 'cancelled'=>[]];
        if (!in_array($next, $allowed[$current] ?? [], true))
            throw new \DomainException('COM_MINIMARKET_ORDER_TRANSITION_DENIED');
        if ($next === 'completed'
            && (($order['payment_status'] ?? '') !== 'paid'
                || ($order['fulfillment_status'] ?? '') !== 'delivered'))
            throw new \DomainException('COM_MINIMARKET_ORDER_TRANSITION_DENIED');
        if ($next === 'cancelled') {
            if (($order['payment_status'] ?? '') === 'paid'
                || in_array((string)($order['fulfillment_status'] ?? ''),
                    ['shipped','delivered'],true))
                throw new \DomainException('COM_MINIMARKET_ORDER_TRANSITION_DENIED');
            $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_shipments')
                .' WHERE order_id='.(int)($order['id'] ?? 0)
                ." AND status IN ('in_transit','delivered')");
            if ((int)$db->loadResult() > 0)
                throw new \DomainException('COM_MINIMARKET_ORDER_TRANSITION_DENIED');
        }
    }

    /**
     * Caller has an active transaction. Creating a delivery moves Order to work,
     * dispatch moves fulfillment to shipped, full delivery + payment completes it.
     * Open returns hold an uncompleted order; closed orders never reopen.
     */
    public static function refresh(DatabaseInterface $db, int $orderId, int $actorId = 0): void
    {
        $db->setQuery('SELECT id,status,payment_status,fulfillment_status,delivery_method,deleted_at'
            .' FROM '.$db->quoteName('#__minimarket_orders')
            .' WHERE id='.(int)$orderId.' FOR UPDATE');
        $o = $db->loadAssoc();
        if (!$o || $o['deleted_at'] !== null
            || in_array((string)$o['status'],['cancelled','completed'],true)) return;

        $oldStatus=(string)$o['status'];
        $oldFulfillment=(string)$o['fulfillment_status'];
        $status=$oldStatus; $fulfillment=$oldFulfillment;
        if ($o['delivery_method']==='courier') {
            $db->setQuery('SELECT status FROM '.$db->quoteName('#__minimarket_shipments')
                .' WHERE order_id='.(int)$orderId);
            $all=(array)$db->loadColumn();
            $effective=array_values(array_filter($all, static fn($v)=>$v!=='cancelled'));
            $progress=count(array_filter($effective,
                static fn($v)=>in_array($v,['in_transit','delivered'],true)))>0;
            $delivered=$effective!==[]
                &&count(array_filter($effective,static fn($v)=>$v==='delivered'))===count($effective);
            $fulfillment=$delivered?'delivered':($progress?'shipped':'pending');
            if ($all && $status==='new') $status='confirmed';
        } elseif($o['delivery_method']==='pickup' && $fulfillment==='delivered' && $status==='new') {
            $status='confirmed';
        }
        // Pending-method and digital orders keep their existing fulfillment contract.
        if ($status==='confirmed' && $o['payment_status']==='paid' && $fulfillment==='delivered') {
            $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_returns')
                .' WHERE order_id='.(int)$orderId
                ." AND status IN ('new','approved','received')");
            if ((int)$db->loadResult()===0) $status='completed';
        }
        if ($fulfillment===$oldFulfillment && $status===$oldStatus) return;
        $set=[];
        if ($fulfillment!==$oldFulfillment) {
            $set[]='fulfillment_status='.$db->quote($fulfillment);
            $set[]='fulfilled_at='.($fulfillment==='delivered'?'UTC_TIMESTAMP()':'NULL');
        }
        if ($status!==$oldStatus) $set[]='status='.$db->quote($status);
        $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
            .' SET '.implode(',',$set).' WHERE id='.(int)$orderId);
        $db->execute();
        if ($fulfillment!==$oldFulfillment)
            OrderHistory::record($db,$orderId,$actorId,'fulfillment',$oldFulfillment,$fulfillment);
        if ($status!==$oldStatus)
            OrderHistory::record($db,$orderId,$actorId,'status',$oldStatus,$status);
    }
}
