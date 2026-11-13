<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderHistory;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderLifecycle;

/**
 * Called by Joomla's independent Scheduled Tasks plugin.
 * Never auto-cancel paid orders, uncertain online transfers or unready orders.
 * All due checks are evaluated against UTC database time.
 */
final class ReservationMonitor
{
    public static function run(DatabaseInterface $db,int $batch=75):array
    {
        $params=ComponentHelper::getParams('com_minimarket');
        $batch=max(1,min(200,$batch));
        $first=max(1,min(30,(int)$params->get('pickup_remind_before_days',3)));
        $final=min($first,max(1,min(30,(int)$params->get('pickup_remind_final_days',1))));
        $db->setQuery('SELECT id,reserved_until FROM '.$db->quoteName('#__minimarket_orders')
            ." WHERE delivery_method='pickup' AND reservation_state='held'"
            ." AND status IN ('new','confirmed') AND ready_at IS NOT NULL"
            ." AND fulfillment_status='ready'"
            ." AND reserved_until>UTC_TIMESTAMP()"
            .' AND reserved_until<=DATE_ADD(UTC_TIMESTAMP(), INTERVAL '.$first.' DAY)'
            .' ORDER BY reserved_until,id');
        $toRemind=(array)$db->loadAssocList();
        $reminders=0;
        foreach($toRemind as $order){
            $id=(int)$order['id'];
            $ref='deadline_'.str_replace([' ',':','-'],'_',(string)$order['reserved_until']);
            $db->setQuery('SELECT IF(reserved_until<=DATE_ADD(UTC_TIMESTAMP(),'
                .'INTERVAL '.$final.' DAY),1,0) FROM '
                .$db->quoteName('#__minimarket_orders').' WHERE id='.$id
                .' AND reservation_state='.$db->quote('held'));
            $finalWindow=(int)$db->loadResult()===1;
            // One reminder tier per evaluation, even if the scheduler was
            // unavailable before the final reminder threshold.
            NotificationSender::emit($id,$finalWindow?'pickup_final':'pickup_reminder',$ref);
            ++$reminders;
        }

        $canceled=0;
        if((int)$params->get('pickup_auto_cancel_unpaid',0)===1){
            // Only objectively abandoned, ready-for-collection POSTPAID
            // orders. Never automatically cancel a bank transfer.
            $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_orders')
                ." WHERE delivery_method='pickup' AND reservation_state='held'"
                ." AND payment_method='on_receipt' AND payment_status='unpaid'"
                ." AND fulfillment_status='ready' AND ready_at IS NOT NULL"
                ." AND status IN ('new','confirmed') AND reserved_until<UTC_TIMESTAMP()"
                .' ORDER BY reserved_until,id LIMIT '.$batch);
            $ids=array_map('intval',(array)$db->loadColumn());
            foreach($ids as $id){
                $db->transactionStart();
                try{
                    $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_orders')
                        .' WHERE id='.$id.' AND deleted_at IS NULL FOR UPDATE');
                    $o=$db->loadAssoc();
                    if(!$o||$o['reservation_state']!=='held'
                       ||$o['delivery_method']!=='pickup'
                       ||$o['payment_method']!=='on_receipt'
                       ||$o['payment_status']!=='unpaid'
                       ||$o['fulfillment_status']!=='ready'
                       ||!$o['ready_at']||!$o['reserved_until']
                       ||!in_array($o['status'],['new','confirmed'],true)){
                        $db->transactionCommit();continue;
                    }
                    $db->setQuery('SELECT UTC_TIMESTAMP()>'.$db->quote($o['reserved_until']));
                    if((int)$db->loadResult()!==1){$db->transactionCommit();continue;}
                    OrderLifecycle::assertCancelable($db,$o);
                    StockReservation::release($db,$id);
                    $reason='AUTO_UNCLAIMED_PICKUP';
                    $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_orders')
                        .' SET status='.$db->quote('cancelled')
                        .',cancelled_reason='.$db->quote($reason)
                        .' WHERE id='.$id);
                    $db->execute();
                    OrderHistory::record($db,$id,0,'status',(string)$o['status'],'cancelled');
                    OrderHistory::record($db,$id,0,'cancel_reason','',$reason);
                    $db->transactionCommit();
                    ++$canceled;
                    NotificationSender::emit($id,'order_cancelled','auto');
                }catch(\Throwable){
                    $db->transactionRollback();
                    // A conflicting payment or shipment must be reviewed, not
                    // silently made into a cancellation. Next run retries.
                }
            }
        }
        return ['reminder_candidates'=>count($toRemind),
            'reminders_checked'=>$reminders,'auto_cancelled'=>$canceled];
    }
}
