<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderHistory;

/** Return cases never alter order snapshots or execute external refunds. */
final class ReturncaseController extends BaseController
{
    private function requirePost(string $permission): void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise($permission,'com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
    }

    public function add(): void
    {
        if(!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->setRedirect('index.php?option=com_minimarket&view=returncase&layout=create');
    }

    private static function checkLine(DatabaseInterface $db,int $orderId,int $lineId,int $qty): void
    {
        $q=$db->getQuery(true)->select('quantity')
            ->from($db->quoteName('#__minimarket_order_items'))
            ->where('order_id='.(int)$orderId)->where('id='.(int)$lineId);
        $db->setQuery($q);
        $purchased=$db->loadResult();
        if($lineId<1||$purchased===null||$qty<1||$qty>(int)$purchased)
            throw new \DomainException('COM_MINIMARKET_RETURN_INVALID');
    }

    public function create(): void
    {
        $this->requirePost('core.create');
        $app=Factory::getApplication();
        $post=$app->input->post;
        $orderId=$post->getInt('order_id',0);
        $shipId=$post->getInt('shipment_id',0);
        $lineId=$post->getInt('order_item_id',0);
        $quantity=$post->getInt('quantity',1);
        $reason=$post->getCmd('reason','defective');
        $note=trim($post->getString('note',''));
        if($orderId<1||!in_array($reason,['defective','damaged','wrong_item','not_fit','other'],true)
            ||mb_strlen($note)>2000||($lineId===0&&$quantity!==1))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_RETURN_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try{
            $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_orders')
                .' WHERE id='.$orderId.' AND deleted_at IS NULL FOR UPDATE');
            if((int)$db->loadResult()!==$orderId)
                throw new \DomainException('COM_MINIMARKET_RETURN_INVALID');
            if($shipId>0){
                $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_shipments')
                    .' WHERE id='.$shipId.' AND order_id='.$orderId);
                if((int)$db->loadResult()!==$shipId)
                    throw new \DomainException('COM_MINIMARKET_RETURN_INVALID');
            }
            if($lineId>0)self::checkLine($db,$orderId,$lineId,$quantity);
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_returns'))
                ->columns($db->quoteName(['order_id','shipment_id','reason','status','note',
                    'resolution','created_by','created_at']))
                ->values(implode(',',[(string)$orderId,$shipId>0?(string)$shipId:'NULL',
                    $db->quote($reason),$db->quote('new'),$db->quote($note),
                    $db->quote('pending'),(string)(int)$app->getIdentity()->id,'UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();$id=(int)$db->insertid();
            if($lineId>0){
                $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_return_items'))
                    ->columns($db->quoteName(['return_id','order_item_id','quantity']))
                    ->values($id.','.$lineId.','.$quantity);
                $db->setQuery($q);$db->execute();
            }
            OrderHistory::record($db,$orderId,(int)$app->getIdentity()->id,
                'return_created','',$reason.' #'.$id);
            $db->transactionCommit();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_RETURN_SAVED'));
            $this->setRedirect('index.php?option=com_minimarket&view=returncase&id='.$id);
        }catch(\Throwable $e){
            $db->transactionRollback();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_RETURN_INVALID'),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view=returncase&layout=create'
                .($orderId>0?'&order_id='.$orderId:''));
        }
    }

    public function addItem(): void
    {
        $this->requirePost('core.edit.state');
        $app=Factory::getApplication();$post=$app->input->post;
        $id=$post->getInt('id',0);$line=$post->getInt('order_item_id',0);
        $qty=$post->getInt('quantity',0);
        if($id<1||$line<1)throw new \RuntimeException(Text::_('COM_MINIMARKET_RETURN_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try{
            $db->setQuery('SELECT order_id,status FROM '.$db->quoteName('#__minimarket_returns')
                .' WHERE id='.$id.' FOR UPDATE');
            $current=$db->loadAssoc();
            if(!$current||!in_array($current['status'],['new','approved'],true))
                throw new \DomainException('COM_MINIMARKET_RETURN_INVALID');
            self::checkLine($db,(int)$current['order_id'],$line,$qty);
            $q=$db->getQuery(true)->select('id')->from($db->quoteName('#__minimarket_return_items'))
                ->where('return_id='.$id)->where('order_item_id='.$line);
            $db->setQuery($q);
            if($db->loadResult()!==null)throw new \DomainException('COM_MINIMARKET_RETURN_INVALID');
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_return_items'))
                ->columns($db->quoteName(['return_id','order_item_id','quantity']))
                ->values($id.','.$line.','.$qty);
            $db->setQuery($q);$db->execute();
            OrderHistory::record($db,(int)$current['order_id'],(int)$app->getIdentity()->id,
                'return_item','',$id.' item '.$line);
            $db->transactionCommit();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_RETURN_SAVED'));
        }catch(\Throwable $e){
            $db->transactionRollback();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_RETURN_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=returncase&id='.$id);
    }

    public function status(): void
    {
        $this->requirePost('core.edit.state');
        $app=Factory::getApplication();
        $id=$app->input->post->getInt('id',0);
        $status=$app->input->post->getCmd('status','');
        $resolution=$app->input->post->getCmd('resolution','pending');
        $transitions=[
            'new'=>['approved','rejected'],'approved'=>['received','rejected'],
            'received'=>['closed'],'rejected'=>[],'closed'=>[]
        ];
        if($id<1||!in_array($status,['new','approved','received','rejected','closed'],true)
            ||!in_array($resolution,['pending','replacement','refund','no_action'],true))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_RETURN_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try{
            $db->setQuery('SELECT order_id,status,resolution FROM '.$db->quoteName('#__minimarket_returns')
                .' WHERE id='.$id.' FOR UPDATE');
            $before=$db->loadAssoc();
            if(!$before||($before['status']!==$status&&!in_array($status,$transitions[$before['status']]??[],true)))
                throw new \DomainException('COM_MINIMARKET_RETURN_INVALID');
            if($status==='closed'&&$resolution==='pending')
                throw new \DomainException('COM_MINIMARKET_RETURN_INVALID');
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_returns'))
                ->set('status='.$db->quote($status))
                ->set('resolution='.$db->quote($resolution))
                ->set('updated_at=UTC_TIMESTAMP()')->where('id='.$id);
            $db->setQuery($q);$db->execute();
            if($before['status']!==$status)
                OrderHistory::record($db,(int)$before['order_id'],(int)$app->getIdentity()->id,
                    'return_status',(string)$before['status'],$status);
            if($before['resolution']!==$resolution)
                OrderHistory::record($db,(int)$before['order_id'],(int)$app->getIdentity()->id,
                    'return_resolution',(string)$before['resolution'],$resolution);
            \MiniMarket\Component\Minimarket\Administrator\Service\OrderLifecycle::refresh(
                $db,(int)$before['order_id'],(int)$app->getIdentity()->id);
            $db->transactionCommit();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_RETURN_SAVED'));
        }catch(\Throwable $e){
            $db->transactionRollback();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_RETURN_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=returncase&id='.$id);
    }
}
