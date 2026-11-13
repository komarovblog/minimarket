<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/** Read-only event stream assembled from immutable business records.
 * Does NOT represent work to be done. Personal read status is a separate table.
 */
final class AdminInbox
{
    public const KINDS=['order','payment','shipment','ticket','returncase'];
    public const AGE_DAYS=30;
    public const LIMIT=100;

    private static function sourceSql(DatabaseInterface $db,string $kind):string
    {
        $fresh='DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.self::AGE_DAYS.' DAY)';
        return match($kind){
            'order'=>'SELECT o.id AS event_id,o.id AS target_id,o.created_at AS occurred_at,'
                .'o.customer_name AS description FROM '.$db->quoteName('#__minimarket_orders','o')
                .' WHERE o.created_at>='.$fresh.' AND o.deleted_at IS NULL',
            'payment'=>'SELECT p.id AS event_id,p.order_id AS target_id,p.confirmed_at AS occurred_at,'
                .'p.external_reference AS description FROM '.$db->quoteName('#__minimarket_payment_records','p')
                .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o')
                .' ON o.id=p.order_id WHERE p.confirmed_at>='.$fresh.' AND o.deleted_at IS NULL',
            'shipment'=>'SELECT e.id AS event_id,e.shipment_id AS target_id,e.created_at AS occurred_at,'
                .'e.event_code AS description FROM '.$db->quoteName('#__minimarket_shipment_events','e')
                .' INNER JOIN '.$db->quoteName('#__minimarket_shipments','s')
                .' ON s.id=e.shipment_id INNER JOIN '.$db->quoteName('#__minimarket_orders','o')
                .' ON o.id=s.order_id WHERE e.created_at>='.$fresh.' AND o.deleted_at IS NULL',
            'ticket'=>'SELECT t.id AS event_id,t.id AS target_id,t.created_at AS occurred_at,'
                .'t.subject AS description FROM '.$db->quoteName('#__minimarket_support_tickets','t')
                .' WHERE t.created_at>='.$fresh,
            'returncase'=>'SELECT r.id AS event_id,r.id AS target_id,r.created_at AS occurred_at,'
                .'r.reason AS description FROM '.$db->quoteName('#__minimarket_returns','r')
                .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o')
                .' ON o.id=r.order_id WHERE r.created_at>='.$fresh.' AND o.deleted_at IS NULL',
            default=>throw new \DomainException('COM_MINIMARKET_INBOX_STALE')
        };
    }

    public static function load(DatabaseInterface $db,int $userId,int $limit=self::LIMIT):array
    {
        if($userId<1)return ['rows'=>[],'capped'=>false,'unread'=>0];
        $limit=max(1,min(250,$limit));$rows=[];$capped=false;
        foreach(self::KINDS as $kind){
            $sql=self::sourceSql($db,$kind);
            $db->setQuery($sql.' ORDER BY occurred_at DESC,event_id DESC',0,$limit+1);
            $events=(array)$db->loadAssocList();
            if(count($events)>$limit){$capped=true;$events=array_slice($events,0,$limit);}
            foreach($events as $event){
                $event['kind']=$kind;
                $event['event_id']=(int)$event['event_id'];
                $event['target_id']=(int)$event['target_id'];
                $rows[]=$event;
            }
        }
        $reads=[];
        $db->setQuery('SELECT event_kind,event_id FROM '
            .$db->quoteName('#__minimarket_admin_inbox_reads')
            .' WHERE user_id='.$userId);
        foreach((array)$db->loadAssocList() as $r)
            $reads[(string)$r['event_kind'].':'.(int)$r['event_id']]=true;
        foreach($rows as &$row)$row['read']=isset($reads[$row['kind'].':'.$row['event_id']]);
        unset($row);
        usort($rows,static fn($a,$b)=>strcmp((string)$b['occurred_at'],(string)$a['occurred_at'])
            ?:($b['event_id']<=>$a['event_id']));
        $unread=count(array_filter($rows,static fn($r)=>!$r['read']));
        if(count($rows)>$limit){$capped=true;$rows=array_slice($rows,0,$limit);}
        return ['rows'=>$rows,'unread'=>$unread,'capped'=>$capped];
    }

    public static function mark(DatabaseInterface $db,int $userId,string $kind,int $eventId,bool $read):void
    {
        if($userId<1||$eventId<1||!in_array($kind,self::KINDS,true))
            throw new \DomainException('COM_MINIMARKET_INBOX_STALE');
        $db->transactionStart();
        try{
            // The immutable event must still exist; no cross-component free-form rows.
            $db->setQuery('SELECT event_id FROM ('.self::sourceSql($db,$kind)
                .') AS event_view WHERE event_id='.$eventId);
            if((int)$db->loadResult()!==$eventId)
                throw new \DomainException('COM_MINIMARKET_INBOX_STALE');
            $table=$db->quoteName('#__minimarket_admin_inbox_reads');
            if($read)$db->setQuery('INSERT INTO '.$table
                .' (user_id,event_kind,event_id,read_at) VALUES('.$userId.','
                .$db->quote($kind).','.$eventId.',UTC_TIMESTAMP())'
                .' ON DUPLICATE KEY UPDATE read_at=VALUES(read_at)');
            else $db->setQuery('DELETE FROM '.$table.' WHERE user_id='.$userId
                .' AND event_kind='.$db->quote($kind).' AND event_id='.$eventId);
            $db->execute();$db->transactionCommit();
        }catch(\Throwable $e){$db->transactionRollback();throw $e;}
    }
}
