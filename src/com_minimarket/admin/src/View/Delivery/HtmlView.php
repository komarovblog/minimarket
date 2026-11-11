<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Delivery;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public bool $isCreate=false;
    public ?object $shipment=null;
    public array $orders=[];
    public int $preselectedOrder=0;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$u=$app->getIdentity();
        if(!$u->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->isCreate=$app->input->getCmd('layout','default')==='create';
        $this->setLayout($this->isCreate?'create':'default');
        if($this->isCreate && !$u->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        if(!$this->isCreate && !$u->authorise('core.edit.state','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        if($this->isCreate){
            $this->preselectedOrder=$app->input->getInt('order_id',0);
            $q=$db->getQuery(true)->select(['o.id','o.customer_name','o.delivery_city','o.created_at'])
                ->from($db->quoteName('#__minimarket_orders','o'))
                ->join('LEFT',$db->quoteName('#__minimarket_shipments','s').' ON s.order_id=o.id')
                ->where('o.deleted_at IS NULL')->where("o.delivery_method='courier'")
                ->where("o.status<>'cancelled'")->where('s.id IS NULL')->order('o.id DESC');
            if($this->preselectedOrder>0){$q->where('o.id='.(int)$this->preselectedOrder);$db->setQuery($q,0,1);$this->orders=(array)$db->loadObjectList();}
        }else{
            $id=$app->input->getInt('id',0);
            $q=$db->getQuery(true)->select(['s.*','o.customer_name','o.delivery_city','o.delivery_address'])
                ->from($db->quoteName('#__minimarket_shipments','s'))
                ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=s.order_id')
                ->where('s.id='.(int)$id);
            $db->setQuery($q);$this->shipment=$db->loadObject()?:null;
            if(!$this->shipment)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        }
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DELIVERY_NEW'));
        parent::display($tpl);
    }
}
