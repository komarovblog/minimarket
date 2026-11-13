<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;

/** No phantom providers: only enabled installed plugins + built-in email. */
final class NotificationPreferences
{
    public static function available(DatabaseInterface $db,Registry $params):array
    {
        $result=[];
        $emailEvents=NotificationPolicy::allowedEvents($params,'email');
        if((int)$params->get('notification_email_enabled',0)===1&&$emailEvents){
            $result['email']=['label'=>'COM_MINIMARKET_NOTIFY_EMAIL','events'=>$emailEvents];
        }
        $db->setQuery('SELECT extension_id,name FROM '.$db->quoteName('#__extensions')
            .' WHERE type='.$db->quote('plugin')
            .' AND folder='.$db->quote('minimarket')
            .' AND element LIKE '.$db->quote('notification\\_%')
            .' AND enabled=1 ORDER BY extension_id ASC');
        foreach((array)$db->loadAssocList() as $entry){
            $code='plugin_'.(int)$entry['extension_id'];
            $events=NotificationPolicy::allowedEvents($params,$code);
            if($events)$result[$code]=['label'=>(string)$entry['name'],'events'=>$events];
        }
        return $result;
    }

    public static function load(DatabaseInterface $db,int $userId):array
    {
        if($userId<=0)return [];
        $db->setQuery('SELECT event_code,channel_code,enabled FROM '
            .$db->quoteName('#__minimarket_notification_preferences')
            .' WHERE user_id='.$userId);
        $result=[];
        foreach((array)$db->loadAssocList() as $r)
            $result[(string)$r['event_code']][(string)$r['channel_code']]=(int)$r['enabled']===1;
        return $result;
    }

    /** Replace ONLY current user's preferences in one atomic transaction. */
    public static function save(DatabaseInterface $db,int $userId,array $posted,array $available):void
    {
        if($userId<1)throw new \DomainException('COM_MINIMARKET_NOTIFY_LOGIN_REQUIRED');
        foreach($posted as $event=>$channels){
            if(!is_string($event)||!isset(NotificationPolicy::EVENTS[$event])||!is_array($channels))
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            foreach($channels as $channel=>$enabled){
                if(!isset($available[$channel])||!in_array($event,$available[$channel]['events'],true)
                   ||(string)$enabled!=='1')
                    throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            }
        }
        $db->transactionStart();
        try{
            $db->setQuery('DELETE FROM '
                .$db->quoteName('#__minimarket_notification_preferences')
                .' WHERE user_id='.$userId);
            $db->execute();
            foreach($available as $channel=>$detail){
                foreach($detail['events'] as $event){
                    $enabled=!empty($posted[$event][$channel])?1:0;
                    $db->setQuery('INSERT INTO '
                        .$db->quoteName('#__minimarket_notification_preferences')
                        .' (user_id,event_code,channel_code,enabled,updated_at) VALUES ('
                        .$userId.','.$db->quote($event).','.$db->quote($channel).','
                        .$enabled.',UTC_TIMESTAMP())');
                    $db->execute();
                }
            }
            $db->transactionCommit();
        }catch(\Throwable $e){$db->transactionRollback();throw $e;}
    }
}
