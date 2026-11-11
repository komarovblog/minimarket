<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Orders;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** Standard Joomla actions with a shared MiniMarket filter bar. */
final class HtmlView extends BaseHtmlView
{
    public array $orders=[];
    public int $total=0, $offset=0, $pageSize=20;
    public string $search='', $productSearch='', $statusFilter='', $deliveryFilter='', $sort='newest';
    public bool $canCreate=false, $showTrash=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);

        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $this->showTrash=$app->input->getInt('filter_trash',0)===1;
        $this->pageSize=in_array($app->input->getInt('limit',20),[20,50,100],true)
            ?$app->input->getInt('limit',20):20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $this->productSearch=mb_substr(trim($app->input->getString('filter_product','')),0,100);
        $status=$app->input->getCmd('filter_status','');
        $this->statusFilter=in_array($status,['new','confirmed','completed','cancelled'],true)?$status:'';
        $delivery=$app->input->getCmd('filter_delivery','');
        $this->deliveryFilter=in_array($delivery,['pickup','courier'],true)?$delivery:'';

        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=['o.deleted_at IS '.($this->showTrash?'NOT NULL':'NULL')];
        if($this->statusFilter!=='')$where[]='o.status='.$db->quote($this->statusFilter);
        if($this->deliveryFilter!=='')$where[]='o.delivery_method='.$db->quote($this->deliveryFilter);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $parts=['o.customer_name LIKE '.$like,'o.phone LIKE '.$like,'o.email LIKE '.$like];
            if(ctype_digit($this->search))$parts[]='o.id='.(int)$this->search;
            $where[]='('.implode(' OR ',$parts).')';
        }
        if($this->productSearch!==''){
            $like=$db->quote('%'.$db->escape($this->productSearch,true).'%',false);
            $terms=['oi.title LIKE '.$like,'oi.sku LIKE '.$like];
            if(ctype_digit($this->productSearch))$terms[]='oi.product_id='.(int)$this->productSearch;
            $where[]='EXISTS (SELECT 1 FROM '.$db->quoteName('#__minimarket_order_items','oi')
                .' WHERE oi.order_id=o.id AND ('.implode(' OR ',$terms).'))';
        }
        $count=$db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__minimarket_orders','o'));
        foreach($where as $condition)$count->where($condition);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $this->offset=$this->total===0?0:min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        $query=$db->getQuery(true)->select([
            'o.id','o.customer_name','o.phone','o.status','o.delivery_method',
            'o.payment_method','o.order_source','o.total_cents','o.item_count','o.created_at'
        ])->from($db->quoteName('#__minimarket_orders','o'))
          ->order('o.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $condition)$query->where($condition);
        $db->setQuery($query,$this->offset,$this->pageSize);
        $this->orders=(array)$db->loadObjectList();

        ToolbarHelper::title(Text::_('COM_MINIMARKET_ORDERS_TITLE'));
        if($this->canCreate)ToolbarHelper::addNew('order.add');
        if(!$this->showTrash && $user->authorise('core.edit.state','com_minimarket')){
            ToolbarHelper::custom('orders.confirm','publish','',Text::_('COM_MINIMARKET_ORDER_STATUS_CONFIRMED'),true);
            ToolbarHelper::custom('orders.cancel','unpublish','',Text::_('COM_MINIMARKET_ORDER_STATUS_CANCELLED'),true);
        }
        if(!$this->showTrash && $user->authorise('core.delete','com_minimarket')){
            ToolbarHelper::deleteList(Text::_('COM_MINIMARKET_ORDER_TRASH_CONFIRM'),'orders.trash');
        }
        if($user->authorise('core.options','com_minimarket')||$user->authorise('core.admin','com_minimarket'))
            ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
