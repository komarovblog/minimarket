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
    public bool $hasPriorShipment=false;
    public array $linkedReturns=[];
    public bool $canViewFinance=false;
    public array $costRevisions=[];
    public array $shipmentEvents=[];
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$u=$app->getIdentity();
        if(!$u->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canViewFinance=$u->authorise('core.admin','com_minimarket');
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
                ->where('o.deleted_at IS NULL')->where("o.delivery_method='courier'")
                ->where("o.status<>'cancelled'")->order('o.id DESC');
            if($this->preselectedOrder>0){$q->where('o.id='.(int)$this->preselectedOrder);$db->setQuery($q,0,1);$this->orders=(array)$db->loadObjectList();}
            if($this->preselectedOrder>0&&$this->orders){
                $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_shipments')
                    .' WHERE order_id='.(int)$this->preselectedOrder);
                $this->hasPriorShipment=(int)$db->loadResult()>0;
            }
        }else{
            $id=$app->input->getInt('id',0);
            $q=$db->getQuery(true)->select(['s.*','o.customer_name','o.delivery_city','o.delivery_address'])
                ->from($db->quoteName('#__minimarket_shipments','s'))
                ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=s.order_id')
                ->where('s.id='.(int)$id);
            $db->setQuery($q);$this->shipment=$db->loadObject()?:null;
            if(!$this->shipment)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
            $q=$db->getQuery(true)->select(['id','reason','status'])
                ->from($db->quoteName('#__minimarket_returns'))
                ->where('shipment_id='.(int)$id)->order('id DESC');
            $db->setQuery($q);$this->linkedReturns=(array)$db->loadObjectList();
            $q=$db->getQuery(true)->select(['event_code','problem_code','note',
                'occurred_at','created_at','source','actor_user_id'])
                ->from($db->quoteName('#__minimarket_shipment_events'))
                ->where('shipment_id='.(int)$id)->order('occurred_at DESC,id DESC');
            $db->setQuery($q,0,200);
            $this->shipmentEvents=(array)$db->loadObjectList();
            if($this->canViewFinance){
                $q=$db->getQuery(true)->select(['previous_cost_cents','confirmed_cost_cents',
                    'evidence_reference','actor_user_id','created_at'])
                    ->from($db->quoteName('#__minimarket_shipment_cost_revisions'))
                    ->where('shipment_id='.(int)$id)->order('id DESC');
                $db->setQuery($q,0,50);
                $this->costRevisions=(array)$db->loadObjectList();
            }
        }
        ToolbarHelper::title(Text::_($this->isCreate?'COM_MINIMARKET_DELIVERY_NEW':'COM_MINIMARKET_DASHBOARD_DELIVERIES'));
        parent::display($tpl);
    }
}
