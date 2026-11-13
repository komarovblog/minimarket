<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Only unresolved business facts appear as attention items.
 * Reading an event or opening a record never dismisses an attention item.
 * A source status change is the only way to clear it.
 */
final class AttentionInbox
{
    private const SOURCES=[
        'orders'=>['orders',"status='new' AND deleted_at IS NULL",'order','COM_MINIMARKET_ORDERS_TITLE'],
        'payments'=>['orders',"payment_status='processing' AND status NOT IN ('new','cancelled','completed') AND deleted_at IS NULL",'order','COM_MINIMARKET_PAYMENTS_TITLE'],
        'deliveries'=>['shipments',"(status='new' OR problem_status='open' OR (status='awaiting_pickup' AND COALESCE(updated_at,created_at)<=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 3 DAY)))",'delivery','COM_MINIMARKET_DASHBOARD_DELIVERIES'],
        'returns'=>['returns',"status='new'",'returncase','COM_MINIMARKET_RETURNS_TITLE'],
        'tickets'=>['support_tickets',"status='new'",'ticket','COM_MINIMARKET_TICKETS_TITLE'],
        'fiscalization'=>['fiscal_operations',"status='failed'",'fiscalization','COM_MINIMARKET_CASH_REGISTER_TITLE']
    ];

    public static function count(DatabaseInterface $db):int
    {
        $total=0;
        foreach(self::SOURCES as [$table,$predicate]){
            $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_'.$table)
                .' WHERE '.$predicate);
            $total+=(int)$db->loadResult();
        }
        return $total;
    }

    public static function load(DatabaseInterface $db,int $perGroup=50):array
    {
        $perGroup=max(1,min(100,$perGroup));
        $groups=[];$total=0;
        foreach(self::SOURCES as $kind=>[$table,$predicate,$view,$label]){
            $source=$db->quoteName('#__minimarket_'.$table);
            $db->setQuery('SELECT COUNT(*) FROM '.$source.' WHERE '.$predicate);
            $count=(int)$db->loadResult();
            $total+=$count;
            if(!$count)continue;
            $db->setQuery('SELECT id FROM '.$source.' WHERE '.$predicate.' ORDER BY id DESC',0,$perGroup);
            $groups[]=[
                'kind'=>$kind,'label'=>$label,'view'=>$view,'count'=>$count,
                'ids'=>array_map('intval',(array)$db->loadColumn()),
                'capped'=>$count>$perGroup
            ];
        }
        return ['groups'=>$groups,'total'=>$total];
    }
}
