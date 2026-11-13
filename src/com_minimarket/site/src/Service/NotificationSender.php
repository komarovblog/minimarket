<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;
use MiniMarket\Component\Minimarket\Administrator\Service\MessageTemplates;

/**
 * Email is the sole built-in sender in v0.6.10. It requires an enabled event
 * and an explicit preference belonging to the real Joomla account.
 * Provider plugin transport dispatch is NOT claimed before a concrete adapter.
 */
final class NotificationSender
{
    /** Safe after-commit hook: notification failures never roll back an order. */
    public static function emit(int $orderId,string $eventCode,string $reference='1'):void
    {
        try {
            if($orderId<1||!isset(NotificationPolicy::EVENTS[$eventCode])
                ||!preg_match('/^[a-zA-Z0-9._-]{1,80}$/D',$reference))return;
            $params=ComponentHelper::getParams('com_minimarket');
            if((int)$params->get('notification_email_enabled',0)!==1
               ||!in_array($eventCode,NotificationPolicy::allowedEvents($params,'email'),true))
                return;
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery('SELECT o.id,o.user_id,o.customer_name,u.email FROM '
                .$db->quoteName('#__minimarket_orders','o')
                .' INNER JOIN '.$db->quoteName('#__users','u').' ON u.id=o.user_id'
                .' WHERE o.id='.$orderId.' AND u.block=0'
                .' AND u.activation='.$db->quote(''));
            $order=$db->loadAssoc();
            if(!$order||!filter_var((string)$order['email'],FILTER_VALIDATE_EMAIL))return;
            $db->setQuery('SELECT enabled FROM '
                .$db->quoteName('#__minimarket_notification_preferences')
                .' WHERE user_id='.(int)$order['user_id']
                .' AND event_code='.$db->quote($eventCode)
                ." AND channel_code='email'");
            if((int)$db->loadResult()!==1)return;
            $db->setQuery('INSERT IGNORE INTO '.$db->quoteName('#__minimarket_notification_log')
                .' (order_id,event_code,channel_code,event_reference,status,created_at)'
                .' VALUES ('.$orderId.','.$db->quote($eventCode)
                .','.$db->quote('email').','.$db->quote($reference)
                .','.$db->quote('pending').',UTC_TIMESTAMP())');
            $db->execute();
            if((int)$db->getAffectedRows()!==1)return;
            $mailer=Factory::getMailer();
            $mailer->addRecipient((string)$order['email']);
            $mailer->isHtml(false);
            // A published event-specific template may override built-in plain text.
            // No template is mandatory: service emails retain their former fallback.
            $template=MessageTemplates::customerEmail($db,$eventCode);
            $vars=['order_id'=>(string)$orderId,
                'customer_name'=>(string)($order['customer_name']??''),
                'shop_name'=>(string)$params->get('shop_name','MiniMarket'),
                'order_link'=>''];
            $subject=$template!==null
                ?MessageTemplates::render((string)$template['subject'],$vars)
                :Text::sprintf('COM_MINIMARKET_NOTIFY_MAIL_SUBJECT',$orderId);
            $body=$template!==null
                ?MessageTemplates::render((string)$template['body'],$vars)
                :Text::sprintf('COM_MINIMARKET_NOTIFY_MAIL_BODY',
                    Text::_(NotificationPolicy::EVENTS[$eventCode]),$orderId);
            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $sent=$mailer->Send()===true;
            $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_notification_log')
                .' SET status='.$db->quote($sent?'sent':'failed')
                .',error_code='.$db->quote($sent?'':'mailer_failed')
                .',sent_at='.($sent?'UTC_TIMESTAMP()':'NULL')
                .' WHERE order_id='.$orderId
                .' AND event_code='.$db->quote($eventCode)
                ." AND channel_code='email' AND event_reference=".$db->quote($reference));
            $db->execute();
        }catch(\Throwable $e){
            // The durable Order must never be undone by SMTP failures.
            try {
                $db=$db??null;
                if($db instanceof DatabaseInterface){
                    $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_notification_log')
                        ." SET status='failed',error_code='mailer_exception'"
                        .' WHERE order_id='.(int)$orderId.' AND event_code='.$db->quote($eventCode)
                        ." AND channel_code='email' AND event_reference=".$db->quote($reference)
                        ." AND status='pending'");
                    $db->execute();
                }
            }catch(\Throwable){}
        }
    }
}
