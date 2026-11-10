<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
/** No external shipment is created here. Manual only, including CDEK reference. */
final class DeliveryController extends BaseController
{
    private function requirePost(string $permission):void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise($permission,'com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
    }
    private function input():array
    {
        $post=Factory::getApplication()->input->post;
        $carrier=$post->getCmd('carrier','own');
        $status=$post->getCmd('status','new');
        $track=trim($post->getString('tracking_number',''));
        $note=trim($post->getString('note',''));
        if(!in_array($carrier,['own','cdek','other'],true)
         ||!in_array($status,['new','in_transit','delivered','cancelled'],true)
         ||mb_strlen($track)>120||mb_strlen($note)>2000
         ||preg_match('/[\x00-\x1f<>]/u',$track))
           throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
        return compact('carrier','status','track','note');
    }
    public function create():void
    {
        $this->requirePost('core.create');
        $app=Factory::getApplication();$db=Factory::getContainer()->get(DatabaseInterface::class);
        $oid=$app->input->post->getInt('order_id',0);
        try {
            $data=$this->input();
            if($oid<1)throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
            $q=$db->getQuery(true)->select('id')->from($db->quoteName('#__minimarket_orders'))
                ->where('id='.(int)$oid)->where("delivery_method='courier'")
                ->where("status<>'cancelled'")->where('deleted_at IS NULL');
            $db->setQuery($q);
            if((int)$db->loadResult()!==$oid)
                throw new \DomainException('COM_MINIMARKET_DELIVERY_INVALID');
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_shipments'))
                ->columns($db->quoteName(['order_id','carrier','tracking_number','status',
                     'note','created_by','created_at']))
                ->values(implode(',',[(string)$oid,$db->quote($data['carrier']),
                    $db->quote($data['track']),$db->quote('new'),$db->quote($data['note']),
                    (string)(int)$app->getIdentity()->id,'UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();
            $id=(int)$db->insertid();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DELIVERY_SAVED'));
            $this->setRedirect('index.php?option=com_minimarket&view=delivery&id='.$id);
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view=delivery&layout=create&order_id='.$oid);
        }catch(\Throwable $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DELIVERY_DUPLICATE'),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view=deliveries');
        }
    }
    public function update():void
    {
        $this->requirePost('core.edit.state');
        $app=Factory::getApplication();$id=$app->input->post->getInt('id',0);
        if($id<1)throw new \RuntimeException(Text::_('COM_MINIMARKET_DELIVERY_INVALID'),400);
        try{
            $d=$this->input();
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_shipments'))
                ->set($db->quoteName('carrier').'='.$db->quote($d['carrier']))
                ->set($db->quoteName('tracking_number').'='.$db->quote($d['track']))
                ->set($db->quoteName('note').'='.$db->quote($d['note']))
                ->set($db->quoteName('status').'='.$db->quote($d['status']))
                ->set($db->quoteName('updated_at').'=UTC_TIMESTAMP()')
                ->where('id='.(int)$id);
            $db->setQuery($q);$db->execute();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_DELIVERY_SAVED'));
        }catch(\DomainException $e){$app->enqueueMessage(Text::_($e->getMessage()),'warning');}
        $this->setRedirect('index.php?option=com_minimarket&view=delivery&id='.$id);
    }
}
