<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Personal read receipts for EXISTING unresolved sources.
 * Read is not solved, hidden, assigned, or a customer notification.
 * A new source state/timestamp changes its signature and becomes unread.
 */
final class WorkqueueReads
{
    public const KINDS=['payment','delivery','ticket','returncase'];

    /** Identical source predicates are used for listing and write validation. */
    public static function sourceSql(DatabaseInterface $db,string $kind):string
    {
        return match($kind) {
            'payment' => 'SELECT o.id,o.customer_name AS description,o.created_at AS date_value,'
                .'o.payment_status AS state_code,COALESCE(o.paid_at,o.created_at) AS state_at,'
                .'COALESCE((SELECT MAX(p.id) FROM '.$db->quoteName('#__minimarket_payment_records','p')
                .' WHERE p.order_id=o.id),0) AS event_revision'
                .' FROM '.$db->quoteName('#__minimarket_orders','o')
                ." WHERE o.payment_status IN ('unpaid','processing')"
                ." AND o.status NOT IN ('cancelled','completed') AND o.deleted_at IS NULL",
            'delivery' => 'SELECT s.id,CONCAT('.$db->quote('Order #').',s.order_id,'
                .$db->quote(' · ').',s.problem_code) AS description,'
                .'s.created_at AS date_value,CONCAT(s.problem_status,'
                .$db->quote(':').',s.problem_code) AS state_code,'
                .'COALESCE(s.updated_at,s.created_at) AS state_at,'
                .'COALESCE((SELECT MAX(e.id) FROM '.$db->quoteName('#__minimarket_shipment_events','e')
                .' WHERE e.shipment_id=s.id),0) AS event_revision'
                .' FROM '.$db->quoteName('#__minimarket_shipments','s')
                .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o')
                .' ON o.id=s.order_id'
                ." WHERE s.problem_status='open' AND o.deleted_at IS NULL",
            'ticket' => 'SELECT t.id,t.subject AS description,t.created_at AS date_value,'
                .'CONCAT(t.status,'.$db->quote(':').',t.priority) AS state_code,'
                .'COALESCE(t.updated_at,t.created_at) AS state_at,t.priority,'
                .'COALESCE((SELECT MAX(m.id) FROM '.$db->quoteName('#__minimarket_support_messages','m')
                .' WHERE m.ticket_id=t.id),0) AS event_revision'
                .' FROM '.$db->quoteName('#__minimarket_support_tickets','t')
                ." WHERE t.status IN ('new','in_progress')",
            'returncase' => 'SELECT r.id,CONCAT('.$db->quote('Order #').',r.order_id,'
                .$db->quote(' · ').',r.reason) AS description,'
                .'r.created_at AS date_value,r.status AS state_code,'
                .'COALESCE(r.updated_at,r.created_at) AS state_at,0 AS event_revision'
                .' FROM '.$db->quoteName('#__minimarket_returns','r')
                .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o')
                .' ON o.id=r.order_id'
                ." WHERE r.status IN ('new','approved','received') AND o.deleted_at IS NULL",
            default => throw new \DomainException('COM_MINIMARKET_WORKQUEUE_STALE'),
        };
    }

    public static function signature(string $kind,object $row):string
    {
        if(!in_array($kind,self::KINDS,true)||(int)($row->id??0)<1
          ||!isset($row->state_code,$row->state_at))
            throw new \DomainException('COM_MINIMARKET_WORKQUEUE_STALE');
        return hash('sha256',json_encode([
            $kind,(int)$row->id,(string)$row->state_code,(string)$row->state_at,
            (int)($row->event_revision??0)
        ],JSON_THROW_ON_ERROR));
    }

    /** Batch lookup only for visible rows and the logged-in operator. */
    public static function receipts(DatabaseInterface $db,int $userId,string $kind,array $ids):array
    {
        if($userId<1||!in_array($kind,self::KINDS,true)||!$ids)return [];
        $ids=array_values(array_unique(array_filter(array_map('intval',$ids),static fn($v)=>$v>0)));
        if(!$ids)return [];
        $db->setQuery('SELECT source_id,source_signature FROM '
            .$db->quoteName('#__minimarket_queue_reads')
            .' WHERE user_id='.$userId.' AND source_kind='.$db->quote($kind)
            .' AND source_id IN ('.implode(',',$ids).')');
        $map=[];
        foreach((array)$db->loadAssocList() as $item)
            $map[(int)$item['source_id']]=(string)$item['source_signature'];
        return $map;
    }

    /** Prevent a stale browser form from marking a newer source event as read. */
    public static function save(DatabaseInterface $db,int $userId,string $kind,int $id,
                                string $expectedSignature,bool $read):void
    {
        if($userId<1||$id<1||!in_array($kind,self::KINDS,true)
          ||!preg_match('/^[a-f0-9]{64}$/D',$expectedSignature))
            throw new \DomainException('COM_MINIMARKET_WORKQUEUE_STALE');
        $alias=match($kind){'payment'=>'o','delivery'=>'s','ticket'=>'t','returncase'=>'r'};
        $db->transactionStart();
        try{
            $db->setQuery(self::sourceSql($db,$kind).' AND '.$alias.'.id='.$id.' FOR UPDATE');
            $current=$db->loadObject();
            if(!$current||!hash_equals(self::signature($kind,$current),$expectedSignature))
                throw new \DomainException('COM_MINIMARKET_WORKQUEUE_STALE');
            $table=$db->quoteName('#__minimarket_queue_reads');
            $where='user_id='.$userId.' AND source_kind='.$db->quote($kind)
                .' AND source_id='.$id;
            if($read) {
                $db->setQuery('INSERT INTO '.$table
                    .' (user_id,source_kind,source_id,source_signature,read_at)'
                    .' VALUES('.$userId.','.$db->quote($kind).','.$id.','
                    .$db->quote($expectedSignature).',UTC_TIMESTAMP())'
                    .' ON DUPLICATE KEY UPDATE'
                    .' source_signature=VALUES(source_signature),read_at=VALUES(read_at)');
            }else{
                $db->setQuery('DELETE FROM '.$table.' WHERE '.$where
                    .' AND source_signature='.$db->quote($expectedSignature));
            }
            $db->execute();
            $db->transactionCommit();
        }catch(\Throwable $e){$db->transactionRollback();throw $e;}
    }
}
