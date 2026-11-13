<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Immutable operational facts for a shipment. Never changes inventory.
 * A transport event is not a second order or a second shipment.
 */
final class ShipmentEvents
{
    public const EVENTS=[
        'handed_to_carrier','arrived_at_pickup','delivered',
        'failed_attempt','pickup_expired','returned_by_carrier',
        'problem_opened','problem_resolved','note'
    ];
    public const PROBLEMS=['delay','lost','damaged','address','recipient','other'];

    /** Validated UTC timestamp, event nonce, source and optional issue details. */
    public static function normalize(array $input,int $now=0):array
    {
        $code=(string)($input['event_code']??'');
        $issue=(string)($input['problem_code']??'');
        $note=trim(strip_tags((string)($input['note']??'')));
        $key=(string)($input['event_key']??'');
        $time=(string)($input['occurred_at']??'');
        if(!in_array($code,self::EVENTS,true)
            ||!preg_match('/^[a-f0-9]{32}$/D',$key)
            ||mb_strlen($note)>2000
            ||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f<>]/u',$note)
            ||($code==='problem_opened'&&!in_array($issue,self::PROBLEMS,true))
            ||($code!=='problem_opened'&&$issue!=='')
            ||(in_array($code,['problem_opened','note'],true)&&mb_strlen($note)<3))
            throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_INVALID');
        $utc=new \DateTimeZone('UTC');
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$time,$utc);
        if(!$date||$date->format('Y-m-d\TH:i')!==$time
            ||$date->getTimestamp()>($now?:time())+300
            ||$date->getTimestamp()<($now?:time())-10*365*86400)
            throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_INVALID');
        return ['event_code'=>$code,'problem_code'=>$issue,'note'=>$note,
            'event_key'=>$key,'occurred_at'=>$date->format('Y-m-d H:i:s')];
    }

    /**
     * Checks a real transition and the current issue state under a shipment
     * row lock. Status is independent from an open delivery problem.
     */
    public static function apply(array $before,array $event):array
    {
        $status=(string)($before['status']??'');
        $issueState=(string)($before['problem_status']??'none');
        $issueCode=(string)($before['problem_code']??'');
        $code=$event['event_code'];
        $transitions=[
            'handed_to_carrier'=>[['ready','failed'],'in_transit'],
            'arrived_at_pickup'=>[['ready','in_transit'],'awaiting_pickup'],
            'delivered'=>[['ready','in_transit','awaiting_pickup'],'delivered'],
            'failed_attempt'=>[['in_transit','awaiting_pickup'],'failed'],
            'pickup_expired'=>[['awaiting_pickup'],'failed'],
            'returned_by_carrier'=>[['in_transit','awaiting_pickup','failed'],'failed']
        ];
        if(isset($transitions[$code])){
            [$from,$target]=$transitions[$code];
            if(!in_array($status,$from,true))
                throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_TRANSITION_INVALID');
            $status=$target;
        }elseif($code==='problem_opened'){
            if($issueState==='open'||$status==='cancelled')
                throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_TRANSITION_INVALID');
            $issueState='open';
            $issueCode=$event['problem_code'];
        }elseif($code==='problem_resolved'){
            if($issueState!=='open')
                throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_TRANSITION_INVALID');
            $issueState='resolved';
        }elseif($code==='note' && $status==='cancelled'){
            throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_TRANSITION_INVALID');
        }
        return ['status'=>$status,'problem_status'=>$issueState,'problem_code'=>$issueCode];
    }

    public static function record(
        DatabaseInterface $db,int $shipmentId,array $data,int $actorId
    ):void {
        $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_shipment_events'))
            ->columns($db->quoteName(['shipment_id','event_key','source',
                'source_reference','event_code','problem_code','note',
                'occurred_at','created_at','actor_user_id']))
            ->values(implode(',',[(string)$shipmentId,$db->quote($data['event_key']),
                $db->quote('manual'),$db->quote($data['event_key']),
                $db->quote($data['event_code']),$db->quote($data['problem_code']),
                $db->quote($data['note']),$db->quote($data['occurred_at']),
                'UTC_TIMESTAMP()',(string)$actorId]));
        $db->setQuery($q);$db->execute();
    }
}
