<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * A single event vocabulary, independent of transport plugins. A configured
 * event is a policy, not proof that a provider has actually sent a message.
 */
final class NotificationPolicy
{
    public const EVENTS=[
        'order_created'=>'COM_MINIMARKET_NOTIFY_EVENT_ORDER_CREATED',
        'order_accepted'=>'COM_MINIMARKET_NOTIFY_EVENT_ORDER_ACCEPTED',
        'pickup_ready'=>'COM_MINIMARKET_NOTIFY_EVENT_PICKUP_READY',
        'pickup_reminder'=>'COM_MINIMARKET_NOTIFY_EVENT_PICKUP_REMINDER',
        'pickup_final'=>'COM_MINIMARKET_NOTIFY_EVENT_PICKUP_FINAL',
        'order_cancelled'=>'COM_MINIMARKET_NOTIFY_EVENT_ORDER_CANCELLED',
        'delivery_updated'=>'COM_MINIMARKET_NOTIFY_EVENT_DELIVERY_UPDATED',
        'refund_updated'=>'COM_MINIMARKET_NOTIFY_EVENT_REFUND_UPDATED',
    ];

    public static function normalizeEvents(array $raw):array
    {
        if(count($raw)>count(self::EVENTS))
            throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
        $events=[];
        foreach($raw as $event){
            if(!is_string($event)||!isset(self::EVENTS[$event]))
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            $events[$event]=$event;
        }
        return array_values($events);
    }

    /** Return configured allowed event codes for a single installed channel. */
    public static function allowedEvents(Registry $params,string $channel):array
    {
        if($channel==='email'){
            $raw=$params->get('notification_email_events',[]);
        }elseif(preg_match('/^plugin_[1-9][0-9]*$/D',$channel)){
            $all=$params->get('notification_plugin_events',[]);
            if($all instanceof Registry)$all=$all->toArray();
            $raw=is_array($all)?($all[$channel]??[]):[];
        }else return [];
        if($raw instanceof Registry)$raw=$raw->toArray();
        if(!is_array($raw))return [];
        try{return self::normalizeEvents($raw);}catch(\DomainException){return [];}
    }
}
