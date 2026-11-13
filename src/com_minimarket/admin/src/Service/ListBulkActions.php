<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/**
 * Atomic bulk status updates for custom MiniMarket admin lists.
 * Nothing is physically deleted. All identifiers are server-validated.
 */
final class ListBulkActions
{
    public static function selected(string $permission): array
    {
        $app = Factory::getApplication();
        if ($app->input->getMethod() !== 'POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }
        if (!$app->getIdentity()->authorise($permission, 'com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $raw = $app->input->post->get('cid', [], 'array');
        if (!is_array($raw) || !$raw || count($raw) > 200) {
            throw new \DomainException('COM_MINIMARKET_LIST_SELECT_ROWS');
        }
        $ids = [];
        foreach ($raw as $value) {
            if (!is_scalar($value) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string)$value)) {
                throw new \DomainException('COM_MINIMARKET_LIST_SELECT_ROWS');
            }
            $ids[] = (int)$value;
        }
        $ids = array_values(array_unique($ids));
        return $ids;
    }

    /** Restore selected orders from the reversible trash. */
    public static function restoreOrders(): int
    {
        $ids=self::selected('core.delete');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try {
            $table=$db->quoteName('#__minimarket_orders');
            $where='id IN ('.implode(',',$ids).') AND deleted_at IS NOT NULL';
            $db->setQuery('SELECT id FROM '.$table.' WHERE '.$where.' FOR UPDATE');
            $rows=(array)$db->loadColumn();
            if(count($rows)!==count($ids)) {
                throw new \DomainException('COM_MINIMARKET_LIST_INVALID_TRANSITION');
            }
            $query=$db->getQuery(true)->update($table)->set('deleted_at=NULL')
                ->where($where);
            $db->setQuery($query);$db->execute();
            foreach($ids as $orderId)
                OrderHistory::record($db,$orderId,(int)Factory::getApplication()->getIdentity()->id,
                    'trash','trashed','restored');
            $db->transactionCommit();
            return count($ids);
        } catch(\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
    }

    public static function apply(
        string $table,
        string $field,
        string $target,
        array $eligible,
        bool $onlyLiveOrders = false,
        bool $softDelete = false
    ): int {
        $allowedTables = [
            '#__minimarket_orders' => 'status',
            '#__minimarket_shipments' => 'status',
            '#__minimarket_reviews' => 'state',
            '#__minimarket_seo_filters' => 'state',
            '#__minimarket_promo_codes' => 'state',
            '#__minimarket_auto_discounts' => 'state'
        ];
        if (($allowedTables[$table] ?? null) !== $field) {
            throw new \LogicException('Invalid MiniMarket bulk target');
        }
        $ids = self::selected($softDelete ? 'core.delete' : 'core.edit.state');

        if($table==='#__minimarket_orders'&&!$softDelete&&!OrderLifecycle::manualEnabled())
            throw new \DomainException('COM_MINIMARKET_ORDER_MANUAL_DISABLED');
        sort($ids,SORT_NUMERIC);
        $actor=(int)Factory::getApplication()->getIdentity()->id;
        $reason=trim(strip_tags(Factory::getApplication()->input->post->getString('cancel_reason','')));
        if($table==='#__minimarket_orders' && !$softDelete && $target==='cancelled'
           &&(mb_strlen($reason)<3||mb_strlen($reason)>512
              ||preg_match('/[\x00-\x1f<>]/u',$reason)))
            throw new \DomainException('COM_MINIMARKET_ORDER_CANCEL_REASON_REQUIRED');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try {
            $sql = 'SELECT '.$db->quoteName('id').', '.$db->quoteName($field)
                .($table==='#__minimarket_shipments'?', '.$db->quoteName('order_id'):'')
                .($table==='#__minimarket_orders'?', '.$db->quoteName('payment_status').', '.$db->quoteName('fulfillment_status'):'')
                .' FROM '.$db->quoteName($table)
                .' WHERE '.$db->quoteName('id').' IN ('.implode(',', $ids).')';
            if ($onlyLiveOrders) {
                $sql .= ' AND '.$db->quoteName('deleted_at').' IS NULL';
            }
            $db->setQuery($sql.' FOR UPDATE');
            $rows = (array)$db->loadAssocList();
            if (count($rows) !== count($ids)) {
                throw new \DomainException('COM_MINIMARKET_LIST_INVALID_TRANSITION');
            }
            foreach ($rows as $row) {
                if (!in_array((string)$row[$field], $eligible, true)) {
                    throw new \DomainException('COM_MINIMARKET_LIST_INVALID_TRANSITION');
                }
                if($table==='#__minimarket_orders'&&!$softDelete)
                    OrderLifecycle::validateManual($db,$row,$target);
            }

            if($table==='#__minimarket_orders'&&!$softDelete&&$target==='cancelled'){
                foreach($rows as $row)
                    \MiniMarket\Component\Minimarket\Site\Service\StockReservation::release(
                        $db,(int)$row['id']);
            }
            $query = $db->getQuery(true)->update($db->quoteName($table))
                ->where($db->quoteName('id').' IN ('.implode(',', $ids).')');
            if ($softDelete) {
                $query->set($db->quoteName('deleted_at').'=UTC_TIMESTAMP()');
            } else {
                $query->set($db->quoteName($field).'='.$db->quote($target));
            }

            if($table==='#__minimarket_orders'&&!$softDelete){
                if($target==='confirmed'){
                    $query->set('accepted_at=COALESCE(accepted_at,UTC_TIMESTAMP())');
                    $query->set('accepted_by='.$actor);
                }elseif($target==='cancelled'){
                    $query->set('cancelled_reason='.$db->quote($reason));
                }
            }
            if ($onlyLiveOrders) {
                $query->where($db->quoteName('deleted_at').' IS NULL');
            }
            $db->setQuery($query);
            $db->execute();
            if($table==='#__minimarket_orders'||$table==='#__minimarket_shipments'){
                foreach($rows as $row)
                    OrderHistory::record($db,
                        $table==='#__minimarket_orders'?(int)$row['id']:(int)$row['order_id'],
                        (int)Factory::getApplication()->getIdentity()->id,
                        $softDelete?'trash':($table==='#__minimarket_orders'?'status':'delivery_status'),
                        (string)$row[$field],$softDelete?'trashed':$target);
            }

            if($table==='#__minimarket_orders'&&!$softDelete){
                foreach($rows as $row){
                    if($target==='cancelled')
                        OrderHistory::record($db,(int)$row['id'],$actor,
                            'cancel_reason','',mb_substr($reason,0,32));
                    if($target==='confirmed')
                        OrderLifecycle::refresh($db,(int)$row['id'],$actor);
                }
            }
            if($table==='#__minimarket_shipments'){
                $orderIds=array_values(array_unique(array_map(static fn($row)=>(int)$row['order_id'],$rows)));
                sort($orderIds,SORT_NUMERIC);
                foreach($orderIds as $orderId)
                    OrderLifecycle::refresh($db,$orderId,(int)Factory::getApplication()->getIdentity()->id);
            }
            $db->transactionCommit();
            return count($ids);
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
    }
}
