<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Orders;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** Native-style orders overview with bounded, parameter-validated filtering. */
final class HtmlView extends BaseHtmlView
{
    public array $orders=[];
    public int $total=0;
    public int $offset=0;
    public int $pageSize=50;
    public string $search='';
    public string $productSearch='';
    public string $statusFilter='';
    public string $deliveryFilter='';
    public bool $canCreate=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if (!$user->authorise('core.manage','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $this->productSearch=mb_substr(trim($app->input->getString('filter_product','')),0,100);
        $status=$app->input->getCmd('filter_status','');
        $this->statusFilter=in_array($status,
            ['new','confirmed','completed','cancelled'],true)?$status:'';
        $delivery=$app->input->getCmd('filter_delivery','');
        $this->deliveryFilter=in_array($delivery,['pickup','courier'],true)?$delivery:'';
        $where=[];
        if ($this->statusFilter!=='') {
            $where[]='o.status='.$db->quote($this->statusFilter);
        }
        if ($this->deliveryFilter!=='') {
            $where[]='o.delivery_method='.$db->quote($this->deliveryFilter);
        }
        if ($this->search!=='') {
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $parts=['o.customer_name LIKE '.$like,'o.phone LIKE '.$like,'o.email LIKE '.$like];
            if (ctype_digit($this->search))$parts[]='o.id='.(int)$this->search;
            $where[]='('.implode(' OR ',$parts).')';
        }
        if ($this->productSearch!=='') {
            $like=$db->quote('%'.$db->escape($this->productSearch,true).'%',false);
            $terms=['oi.title LIKE '.$like,'oi.sku LIKE '.$like];
            if (ctype_digit($this->productSearch))$terms[]='oi.product_id='.(int)$this->productSearch;
            $where[]='EXISTS (SELECT 1 FROM '.$db->quoteName('#__minimarket_order_items','oi')
                .' WHERE oi.order_id=o.id AND ('.implode(' OR ',$terms).'))';
        }
        $count=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_orders','o'));
        foreach($where as $condition)$count->where($condition);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $q=$db->getQuery(true)->select(['o.id','o.customer_name','o.phone','o.status',
            'o.delivery_method','o.payment_method','o.order_source','o.total_cents',
            'o.item_count','o.created_at'])
            ->from($db->quoteName('#__minimarket_orders','o'))
            ->order('o.id DESC');
        foreach($where as $condition)$q->where($condition);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->orders=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_ORDERS_TITLE'));
        parent::display($tpl);
    }
}
