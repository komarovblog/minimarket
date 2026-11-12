<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Shared immutable audit events for status, shipment and return operations. */
final class OrderHistory
{
    public static function record(
        DatabaseInterface $db, int $orderId, int $actorId,
        string $event, string $before, string $after
    ): void {
        if ($orderId < 1 || !preg_match('/^[a-z][a-z_]{0,31}$/D',$event)) {
            throw new \InvalidArgumentException('Invalid MiniMarket order history event');
        }
        $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_order_events'))
            ->columns($db->quoteName([
                'order_id','actor_user_id','event_type','old_value','new_value','created_at'
            ]))
            ->values(implode(',',[
                (string)$orderId,(string)max(0,$actorId),
                $db->quote($event),$db->quote(mb_substr($before,0,32)),
                $db->quote(mb_substr($after,0,32)),'UTC_TIMESTAMP()'
            ]));
        $db->setQuery($q);
        $db->execute();
    }
}
