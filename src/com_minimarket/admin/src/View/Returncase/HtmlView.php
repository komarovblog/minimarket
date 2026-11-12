<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Returncase;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public bool $isCreate=false,$canEdit=false;
    public ?object $case=null;
    public array $items=[],$orderItems=[],$shipments=[],$selectedOrder=[];
    public int $preselectedOrder=0;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$u=$app->getIdentity();
        if(!$u->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canEdit=$u->authorise('core.edit.state','com_minimarket');
        $this->isCreate=$app->input->getCmd('layout','default')==='create';
        $this->setLayout($this->isCreate?'create':'default');
        if($this->isCreate && !$u->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        if($this->isCreate){
            $this->preselectedOrder=$app->input->getInt('order_id',0);
            if($this->preselectedOrder>0){
                $q=$db->getQuery(true)->select(['id','customer_name','delivery_city'])
                    ->from($db->quoteName('#__minimarket_orders'))
                    ->where('id='.(int)$this->preselectedOrder)->where('deleted_at IS NULL');
                $db->setQuery($q);
                $order=$db->loadObject();
                if($order)$this->selectedOrder[(int)$order->id]='#'.$order->id.' — '.$order->customer_name;
                else $this->preselectedOrder=0;
            }
            if($this->preselectedOrder>0){
                $this->loadOrderItems($db,$this->preselectedOrder);
                $this->loadShipments($db,$this->preselectedOrder);
            }
        }else{
            $id=$app->input->getInt('id',0);
            $q=$db->getQuery(true)->select(['r.*','o.customer_name','o.phone'])
                ->from($db->quoteName('#__minimarket_returns','r'))
                ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=r.order_id')
                ->where('r.id='.(int)$id);
            $db->setQuery($q);$this->case=$db->loadObject()?:null;
            if(!$this->case)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
            $this->loadOrderItems($db,(int)$this->case->order_id);
            $this->loadShipments($db,(int)$this->case->order_id);
            $q=$db->getQuery(true)
                ->select(['ri.order_item_id','ri.quantity','oi.title','oi.sku'])
                ->from($db->quoteName('#__minimarket_return_items','ri'))
                ->join('INNER',$db->quoteName('#__minimarket_order_items','oi').' ON oi.id=ri.order_item_id')
                ->where('ri.return_id='.(int)$id)->order('ri.id ASC');
            $db->setQuery($q);$this->items=(array)$db->loadObjectList();
        }
        ToolbarHelper::title(Text::_($this->isCreate
            ?'COM_MINIMARKET_RETURN_NEW':'COM_MINIMARKET_RETURN_EDIT'));
        parent::display($tpl);
    }
    private function loadOrderItems(DatabaseInterface $db,int $orderId):void
    {
        $q=$db->getQuery(true)->select(['id','title','sku','quantity'])
            ->from($db->quoteName('#__minimarket_order_items'))
            ->where('order_id='.(int)$orderId)->order('id ASC');
        $db->setQuery($q);$this->orderItems=(array)$db->loadObjectList();
    }
    private function loadShipments(DatabaseInterface $db,int $orderId):void
    {
        $q=$db->getQuery(true)->select(['id','reason','status'])
            ->from($db->quoteName('#__minimarket_shipments'))
            ->where('order_id='.(int)$orderId)->order('id DESC');
        $db->setQuery($q);$this->shipments=(array)$db->loadObjectList();
    }
}
