<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class MessageTemplates
{
    public const AUDIENCES=['customer','admin','marketing'];
    public const TOKENS=['order_id','customer_name','shop_name','order_link'];
    public const MARKETING_EVENTS=['cart_abandoned','order_delivered','order_created'];

    public static function events(string $audience):array
    {
        return match($audience){
            'customer'=>array_keys(NotificationPolicy::EVENTS),
            'admin'=>array_keys(NotificationPolicy::ADMIN_EVENTS),
            'marketing'=>self::MARKETING_EVENTS,
            default=>[]
        };
    }

    public static function normalize(array $data):array
    {
        $title=trim((string)($data['title']??''));
        $audience=(string)($data['audience']??'customer');
        $event=(string)($data['event_code']??'');
        $subject=trim((string)($data['subject']??''));
        $body=trim(str_replace(["\r\n","\r"],"\n",(string)($data['body']??'')));
        $state=(string)($data['state']??'0');
        if($title===''||mb_strlen($title)>160||!in_array($audience,self::AUDIENCES,true)
          ||!in_array($event,self::events($audience),true)
          ||$subject===''||mb_strlen($subject)>190||preg_match('/[\r\n\x00-\x1F]/',$subject)
          ||$body===''||mb_strlen($body)>6000||!in_array($state,['0','1'],true)
          ||str_contains($body,"\0"))
            throw new \DomainException('COM_MINIMARKET_MESSAGE_TEMPLATE_INVALID');
        foreach([$subject,$body] as $str){
            if(preg_match_all('/\{([^{}]+)\}/u',$str,$matches)){
                foreach($matches[1] as $token)
                    if(!in_array($token,self::TOKENS,true))
                        throw new \DomainException('COM_MINIMARKET_MESSAGE_TEMPLATE_INVALID');
            }
            // No stray braces, hidden expressions or interpolation code.
            $removed=preg_replace('/\{(?:order_id|customer_name|shop_name|order_link)\}/','',$str);
            if(str_contains($removed,'{')||str_contains($removed,'}'))
                throw new \DomainException('COM_MINIMARKET_MESSAGE_TEMPLATE_INVALID');
        }
        return ['title'=>$title,'audience'=>$audience,'event_code'=>$event,
            'channel_code'=>'email','subject'=>$subject,'body'=>$body,'state'=>(int)$state];
    }

    /** Plain-text interpolation only; do not replace with HTML or execute macros. */
    public static function render(string $content,array $values):string
    {
        $map=[];
        foreach(self::TOKENS as $name){
            $value=(string)($values[$name]??'');
            $value=str_replace(["\r","\n","\0"],' ',$value);
            $map['{'.$name.'}']=$value;
        }
        return strtr($content,$map);
    }

    /**
     * Optional service-message customization, after NotificationSender verifies
     * Joomla user, enabled channel and notification preference.
     */
    public static function customerEmail(DatabaseInterface $db,string $event):?array
    {
        if(!in_array($event,array_keys(NotificationPolicy::EVENTS),true))return null;
        $db->setQuery('SELECT subject,body FROM '
            .$db->quoteName('#__minimarket_message_templates')
            .' WHERE audience='.$db->quote('customer')
            .' AND event_code='.$db->quote($event)
            ." AND channel_code='email' AND state=1 ORDER BY id DESC",0,1);
        return $db->loadAssoc()?:null;
    }
}
