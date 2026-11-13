<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderHistory;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderLifecycle;

/** Local delivery record only. No CDEK shipment is created via an external API. */
final class DeliveryController extends BaseController
{
    private function requirePost(string $permission): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise($permission,'com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
    }

    private function input(): array
    {
        $post=Factory::getApplication()->input->post;
        $carrier=$post->getCmd('carrier','own');
        $status=$post->getCmd('status','awaiting_assembly');
        $reason=$post->getCmd('reason','initial');
        $track=trim($post->getString('tracking_number',''));
        $note=trim($post->getString('note',''));
        if(!in_array($carrier,['own','cdek','other'],true)
            ||!in_array($status,['awaiting_assembly','assembling','ready','in_transit','awaiting_pickup','delivered','failed','cancelled'],true)
            ||!in_array($reason,['initial','redelivery','replacement','other'],true)
            ||mb_strlen($track)>120||mb_strlen($note)>2000
            ||preg_match('/[\x00-\x1f<>]/u',$track))
            throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
        return compact('carrier','status','reason','track','note');
    }

    public function create(): void
    {
        $this->requirePost('core.create');
        $app=Factory::getApplication();
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $oid=$app->input->post->getInt('order_id',0);
        try {
            $data=$this->input();
            if($oid<1)throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
            $db->transactionStart();
            try {
                // Lock order row: two operators cannot create competing initial deliveries.
                $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_orders')
                    .' WHERE id='.$oid." AND delivery_method='courier'"
                    ." AND status<>'cancelled' AND deleted_at IS NULL FOR UPDATE");
                if((int)$db->loadResult()!==$oid)
                    throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
                $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_shipments')
                    .' WHERE order_id='.$oid." AND reason='initial'");
                if($data['reason']==='initial'&&(int)$db->loadResult()>0)
                    throw new \DomainException('COM_MINIMARKET_DELIVERY_INITIAL_EXISTS');
                $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_shipments'))
                    ->columns($db->quoteName(['order_id','carrier','tracking_number','reason',
                        'status','note','created_by','created_at']))
                    ->values(implode(',',[(string)$oid,$db->quote($data['carrier']),
                        $db->quote($data['track']),$db->quote($data['reason']),
                        $db->quote('awaiting_assembly'),$db->quote($data['note']),
                        (string)(int)$app->getIdentity()->id,'UTC_TIMESTAMP()']));
                $db->setQuery($q);$db->execute();
                $id=(int)$db->insertid();
                OrderHistory::record($db,$oid,(int)$app->getIdentity()->id,
                    'delivery_created','',$data['reason'].' #'.$id);
                OrderLifecycle::refresh($db,$oid,(int)$app->getIdentity()->id);
                $db->transactionCommit();
            } catch (\Throwable $e) {
                $db->transactionRollback();
                throw $e;
            }
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DELIVERY_SAVED'));
            $this->setRedirect('index.php?option=com_minimarket&view=delivery&id='.$id);
        } catch (\Throwable $e) {
            $app->enqueueMessage(Text::_($e instanceof \DomainException
                ?$e->getMessage():'COM_MINIMARKET_DELIVERY_INVALID'),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view=delivery&layout=create&order_id='.$oid);
        }
    }

    public function update(): void
    {
        $this->requirePost('core.edit.state');
        $app=Factory::getApplication();
        $id=$app->input->post->getInt('id',0);
        if($id<1)throw new \RuntimeException(Text::_('COM_MINIMARKET_DELIVERY_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try {
            $data=$this->input();
            $db->transactionStart();
            try {
                $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_shipments')
                    .' WHERE id='.$id.' FOR UPDATE');
                $before=$db->loadAssoc();
                if(!$before)throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
                $allowed=[
                    'new'=>['awaiting_assembly','assembling','ready','in_transit','delivered','cancelled'],
                    'awaiting_assembly'=>['awaiting_assembly','assembling','ready','in_transit','delivered','cancelled'],
                    'assembling'=>['assembling','ready','in_transit','delivered','cancelled'],
                    'ready'=>['ready','in_transit','awaiting_pickup','delivered','cancelled'],
                    'in_transit'=>['in_transit','awaiting_pickup','delivered','failed'],
                    'awaiting_pickup'=>['awaiting_pickup','delivered','failed'],
                    'failed'=>['failed','ready','in_transit','awaiting_pickup','delivered'],
                    'delivered'=>['delivered'],'cancelled'=>['cancelled']
                ];
                if(!in_array($data['status'],$allowed[(string)$before['status']]??[],true))
                    throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
                $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_orders')
                    .' WHERE id='.(int)$before['order_id']." AND status<>'cancelled'"
                    .' AND deleted_at IS NULL');
                if(!(int)$db->loadResult())throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
                if($data['reason']==='initial'&&$before['reason']!=='initial'){
                    $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_shipments')
                        .' WHERE order_id='.(int)$before['order_id']." AND reason='initial' AND id<>".$id);
                    if((int)$db->loadResult()>0)
                        throw new \DomainException('COM_MINIMARKET_DELIVERY_INITIAL_EXISTS');
                }
                $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_shipments'))
                    ->set('carrier='.$db->quote($data['carrier']))
                    ->set('tracking_number='.$db->quote($data['track']))
                    ->set('note='.$db->quote($data['note']))
                    ->set('reason='.$db->quote($data['reason']))
                    ->set('status='.$db->quote($data['status']))
                    ->set('updated_at=UTC_TIMESTAMP()')->where('id='.$id);
                $db->setQuery($q);$db->execute();
                foreach(['status'=>'delivery_status','reason'=>'delivery_reason',
                    'carrier'=>'delivery_carrier','tracking_number'=>'delivery_track'] as $column=>$event) {
                    $after=$column==='tracking_number'?$data['track']:$data[$column];
                    if((string)$before[$column]!==$after)
                        OrderHistory::record($db,(int)$before['order_id'],(int)$app->getIdentity()->id,
                            $event,(string)$before[$column],$after);
                }
                OrderLifecycle::refresh($db,(int)$before['order_id'],(int)$app->getIdentity()->id);
                $db->transactionCommit();
                if((string)$before['status']!==$data['status'])
                    \MiniMarket\Component\Minimarket\Site\Service\NotificationSender::emit(
                        (int)$before['order_id'],'delivery_updated',
                        'shipment_'.$id.'_'.$data['status']);
            }catch(\Throwable $e){
                $db->transactionRollback();
                throw $e;
            }
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DELIVERY_SAVED'));
        }catch(\Throwable $e){
            $app->enqueueMessage(Text::_($e instanceof \DomainException
                ?$e->getMessage():'COM_MINIMARKET_DELIVERY_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=delivery&id='.$id);
    }
    public function cost(): void
    {
        // Procurement/shipping financial data are administrator-only until
        // a dedicated finance ACL is designed. This checks on the SERVER.
        $this->requirePost('core.admin');
        $app=Factory::getApplication();
        $id=$app->input->post->getInt('id',0);
        $raw=trim($app->input->post->getString('actual_cost',''));
        $reference=trim($app->input->post->getString('cost_reference',''));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try {
            if($id<1||!preg_match('/^(0|[1-9][0-9]{0,12})(?:\.([0-9]{1,2}))?$/D',$raw,$m)
                ||mb_strlen($reference)<3||mb_strlen($reference)>128
                ||preg_match('/[\x00-\x1f<>]/u',$reference))
                throw new \DomainException('COM_MINIMARKET_DELIVERY_COST_INVALID');
            $cents=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
            $db->transactionStart();
            try {
                $db->setQuery('SELECT order_id,actual_cost_cents,cost_reference FROM '
                    .$db->quoteName('#__minimarket_shipments').' WHERE id='.$id.' FOR UPDATE');
                $previous=$db->loadAssoc();
                if(!$previous)throw new \DomainException('COM_MINIMARKET_DELIVERY_COST_INVALID');
                if($previous['actual_cost_cents']!==null
                    &&(int)$previous['actual_cost_cents']===$cents
                    &&(string)$previous['cost_reference']===$reference){
                    $db->transactionCommit();
                }else{
                    $actor=(int)$app->getIdentity()->id;
                    $before=$previous['actual_cost_cents'];
                    $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_shipments'))
                        ->set('actual_cost_cents='.$cents)
                        ->set('cost_reference='.$db->quote($reference))
                        ->set('cost_confirmed_at=UTC_TIMESTAMP()')
                        ->set('cost_confirmed_by='.$actor)
                        ->set('updated_at=UTC_TIMESTAMP()')->where('id='.$id);
                    $db->setQuery($q);$db->execute();
                    $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_shipment_cost_revisions'))
                        ->columns($db->quoteName(['shipment_id','previous_cost_cents',
                            'confirmed_cost_cents','evidence_reference','actor_user_id','created_at']))
                        ->values(implode(',',[(string)$id,$before===null?'NULL':(string)(int)$before,
                            (string)$cents,$db->quote($reference),(string)$actor,'UTC_TIMESTAMP()']));
                    $db->setQuery($q);$db->execute();
                    OrderHistory::record($db,(int)$previous['order_id'],$actor,
                        'delivery_cost',$before===null?'unknown':(string)$before,(string)$cents);
                    $db->transactionCommit();
                }
            }catch(\Throwable $e){$db->transactionRollback();throw $e;}
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DELIVERY_COST_SAVED'));
        }catch(\Throwable $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DELIVERY_COST_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=delivery&id='.$id);
    }

    /**
     * Add a confirmed operational fact. The browser never chooses the resulting
     * status: it is derived by ShipmentEvents::apply under a row lock.
     * Repeated manual POSTs with the same nonce are idempotent.
     */
    public function event():void
    {
        $this->requirePost('core.edit.state');
        $app=Factory::getApplication();
        $post=$app->input->post;
        $id=$post->getInt('id',0);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try {
            $data=\MiniMarket\Component\Minimarket\Administrator\Service\ShipmentEvents::normalize([
                'event_code'=>$post->getCmd('event_code',''),
                'problem_code'=>$post->getCmd('problem_code',''),
                'note'=>$post->getString('event_note',''),
                'event_key'=>$post->getCmd('event_key',''),
                'occurred_at'=>$post->getString('occurred_at','')
            ]);
            if($id<1)throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_INVALID');
            $actor=(int)$app->getIdentity()->id;
            $changed=false;
            $orderId=0;
            $db->transactionStart();
            try {
                $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_shipments')
                    .' WHERE id='.$id.' FOR UPDATE');
                $before=$db->loadAssoc();
                if(!$before)throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_INVALID');
                $orderId=(int)$before['order_id'];
                $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_orders')
                    .' WHERE id='.$orderId." AND status<>'cancelled' AND deleted_at IS NULL");
                if((int)$db->loadResult()!==$orderId)
                    throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_INVALID');
                $db->setQuery('SELECT event_code,occurred_at,problem_code,note FROM '
                    .$db->quoteName('#__minimarket_shipment_events')
                    .' WHERE shipment_id='.$id.' AND event_key='.$db->quote($data['event_key']));
                $existing=$db->loadAssoc();
                if($existing){
                    if((string)$existing['event_code']!==$data['event_code']
                        ||(string)$existing['occurred_at']!==$data['occurred_at']
                        ||(string)$existing['problem_code']!==$data['problem_code']
                        ||(string)$existing['note']!==$data['note'])
                        throw new \DomainException('COM_MINIMARKET_SHIPMENT_EVENT_INVALID');
                    $db->transactionCommit();
                }else{
                    $after=\MiniMarket\Component\Minimarket\Administrator\Service\ShipmentEvents::apply($before,$data);
                    $changed=$after['status']!==(string)$before['status'];
                    $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_shipments'))
                        ->set('status='.$db->quote($after['status']))
                        ->set('problem_status='.$db->quote($after['problem_status']))
                        ->set('problem_code='.$db->quote($after['problem_code']))
                        ->set('updated_at=UTC_TIMESTAMP()')->where('id='.$id);
                    $db->setQuery($q);$db->execute();
                    \MiniMarket\Component\Minimarket\Administrator\Service\ShipmentEvents::record(
                        $db,$id,$data,$actor);
                    OrderHistory::record($db,$orderId,$actor,'delivery_event','',$data['event_code']);
                    if($changed)OrderHistory::record($db,$orderId,$actor,'delivery_status',
                        (string)$before['status'],$after['status']);
                    OrderLifecycle::refresh($db,$orderId,$actor);
                    $db->transactionCommit();
                }
            }catch(\Throwable $e){$db->transactionRollback();throw $e;}
            if($changed){
                \MiniMarket\Component\Minimarket\Site\Service\NotificationSender::emit(
                    $orderId,'delivery_updated','shipment_event_'.$id.'_'.$data['event_key']);
            }
            $app->enqueueMessage(Text::_('COM_MINIMARKET_SHIPMENT_EVENT_SAVED'));
        }catch(\Throwable $e){
            $app->enqueueMessage(Text::_($e instanceof \DomainException
                ?$e->getMessage():'COM_MINIMARKET_SHIPMENT_EVENT_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=delivery&id='.$id);
    }
}
