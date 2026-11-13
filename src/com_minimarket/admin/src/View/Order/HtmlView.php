<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Order;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $order=null;
    public ?object $linkedUser=null;
    public bool $canViewUsers=false;
    public bool $canViewFinance=false;
    public array $items=[];
    public bool $hasDigitalItems=false;
    public array $customFields=[];
    public bool $canUpdate=false;
    public bool $manualStatusEnabled=true;
    public ?string $refundAt=null;
    public bool $canDelete=false;
    public bool $canCreateDelivery=false;
    public ?int $shipmentId=null;
    public array $shipments=[],$returns=[];
    public bool $canCreateReturn=false;
    public bool $isCreate=false;
    public array $productChoices=[];
    public array $pickerCategories=[];
    public array $deliveryMethods=[];
    public array $paymentMethods=[];
    public array $cityRates=[];
    public bool $zonesMode=false;
    public array $zoneCities=[];

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $identity=$app->getIdentity();
        if (!$identity->authorise('core.manage','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $this->canUpdate=$identity->authorise('core.edit.state','com_minimarket');
        $this->manualStatusEnabled=\MiniMarket\Component\Minimarket\Administrator\Service\OrderLifecycle::manualEnabled();
        $this->canDelete=$identity->authorise('core.delete','com_minimarket');
        $this->canCreateDelivery=$identity->authorise('core.create','com_minimarket');
        $this->canViewUsers=$identity->authorise('core.manage','com_users');
        $this->canViewFinance=$identity->authorise('core.admin','com_minimarket');
        $this->canCreateReturn=$identity->authorise('core.create','com_minimarket');
        $this->isCreate=$app->input->getCmd('layout','default')==='create';
        if ($this->isCreate) {
            $this->setLayout('create');
            if (!$identity->authorise('core.create','com_minimarket')) {
                throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
            }
            foreach (['CartState','CartStock','CheckoutDelivery'] as $name) {
                require_once JPATH_SITE.'/components/com_minimarket/src/Service/'.$name.'.php';
            }
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $params=\Joomla\CMS\Component\ComponentHelper::getParams('com_minimarket');
            $this->productChoices=[]; // AJAX SKU picker, no catalog preload.
            $cats=$db->getQuery(true)->select(['id','title'])
                ->from($db->quoteName('#__minimarket_categories'))
                ->where('state=1')->order('title ASC,id ASC');
            $db->setQuery($cats,0,1000);
            $this->pickerCategories=(array)$db->loadObjectList();
            $this->deliveryMethods=\MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery::methods($params);
            $this->paymentMethods=\MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery::payments($params);
            $this->cityRates=\MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery::rates($params);
            $this->zonesMode=(string)$params->get('delivery_rate_source','directory')==='zones';
            if($this->zonesMode){
                $this->zoneCities=\MiniMarket\Component\Minimarket\Site\Service\DeliveryZones::cities($db);
            }
            $app->getDocument()->getWebAssetManager()
                ->registerAndUseScript('com_minimarket.manual-order',
                    'media/com_minimarket/js/manual-order.js',[],['defer'=>true])
                ->registerAndUseScript('com_minimarket.payment-compatibility',
                    'media/com_minimarket/js/payment-compatibility.js',[],['defer'=>true]);
            ToolbarHelper::title(Text::_('COM_MINIMARKET_MANUAL_ORDER_TITLE'));
            parent::display($tpl);
            return;
        }
        $id=$app->input->getInt('id',0);
        if ($id<1) throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select('*')
            ->from($db->quoteName('#__minimarket_orders'))
            ->where($db->quoteName('id').'='.(int)$id);
        $db->setQuery($q);
        $this->order=$db->loadObject() ?: null;
        if ($this->order===null) throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        // Customer contact fields are immutable order snapshots. The account
        // relation uses the explicitly saved Joomla user_id, never name/email.
        if((int)($this->order->user_id??0)>0){
            $q=$db->getQuery(true)->select(['id','name'])
                ->from($db->quoteName('#__users'))
                ->where('id='.(int)$this->order->user_id);
            $db->setQuery($q);
            $this->linkedUser=$db->loadObject() ?: null;
        }
        $q=$db->getQuery(true)->select('*')
            ->from($db->quoteName('#__minimarket_order_items'))
            ->where($db->quoteName('order_id').'='.(int)$id)
            ->order('id ASC');
        $db->setQuery($q);
        $this->items=(array)$db->loadObjectList();
        $this->hasDigitalItems=count(array_filter($this->items,
            static fn($i)=>(string)($i->delivery_type??'physical')==='digital'))>0;
        $q=$db->getQuery(true)->select(['field_title','field_value'])
            ->from($db->quoteName('#__minimarket_order_custom_values'))
            ->where('order_id='.(int)$id)->order('field_code ASC');
        $db->setQuery($q);
        $this->customFields=(array)$db->loadObjectList();
        $q=$db->getQuery(true)->select(['id','order_id','carrier','tracking_number',
                'reason','status','actual_cost_cents','created_at'])
            ->from($db->quoteName('#__minimarket_shipments'))
            ->where('order_id='.(int)$id)->order('created_at DESC,id DESC');
        $db->setQuery($q);
        $this->shipments=(array)$db->loadObjectList();
        $this->shipmentId=$this->shipments?(int)$this->shipments[0]->id:null;
        $q=$db->getQuery(true)->select(['id','order_id','shipment_id','reason',
                'status','resolution','created_at'])
            ->from($db->quoteName('#__minimarket_returns'))
            ->where('order_id='.(int)$id)->order('created_at DESC,id DESC');
        $db->setQuery($q);
        $this->returns=(array)$db->loadObjectList();
        if((string)$this->order->payment_status==='refunded'){
            $db->setQuery('SELECT MAX(created_at) FROM '.$db->quoteName('#__minimarket_order_events')
                ." WHERE order_id=".(int)$id." AND event_type='payment' AND new_value='refunded'");
            $this->refundAt=$db->loadResult()?:null;
        }
        ToolbarHelper::title(Text::sprintf('COM_MINIMARKET_ORDER_HEADING',$id));
        parent::display($tpl);
    }
}
