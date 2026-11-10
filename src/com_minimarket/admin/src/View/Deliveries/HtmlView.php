<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Deliveries;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** Shipments are separate records linked to real MiniMarket orders. */
final class HtmlView extends BaseHtmlView
{
    public array $deliveries=[];
    public string $status='';
    public int $offset=0;
    public int $total=0;
    public int $pageSize=50;
    public bool $canCreate=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $requested=$app->input->getCmd('filter_status','');
        $this->status=in_array($requested,['new','in_transit','delivered','cancelled'],true)?$requested:'';
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $count=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_shipments','s'))
            ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=s.order_id')
            ->where('o.deleted_at IS NULL');
        if($this->status!=='')$count->where('s.status='.$db->quote($this->status));
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $q=$db->getQuery(true)->select(['s.id','s.order_id','s.carrier','s.tracking_number',
            's.status','s.created_at','o.customer_name','o.phone','o.delivery_city',
            'o.delivery_address','o.delivery_fee_cents'])
            ->from($db->quoteName('#__minimarket_shipments','s'))
            ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=s.order_id')
            ->where('o.deleted_at IS NULL')->order('s.id DESC');
        if($this->status!=='')$q->where('s.status='.$db->quote($this->status));
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->deliveries=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DASHBOARD_DELIVERIES'));
        parent::display($tpl);
    }
}
