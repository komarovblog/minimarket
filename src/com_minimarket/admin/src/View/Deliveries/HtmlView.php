<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Deliveries;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $deliveries=[];
    public string $status='', $search='', $carrierFilter='', $reasonFilter='', $problemFilter='', $sort='newest';
    public int $offset=0, $total=0, $pageSize=20;
    public bool $canCreate=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $requested=$app->input->getCmd('filter_status','');
        $this->status=in_array($requested,['awaiting_assembly','assembling','ready','in_transit','awaiting_pickup','delivered','failed','cancelled','new'],true)?$requested:'';
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $carrier=$app->input->getCmd('filter_carrier','');
        $this->carrierFilter=in_array($carrier,['own','cdek','other'],true)?$carrier:'';
        $reason=$app->input->getCmd('filter_reason','');
        $this->reasonFilter=in_array($reason,['initial','redelivery','replacement','other'],true)?$reason:'';
        $problem=$app->input->getCmd('filter_problem','');
        $this->problemFilter=in_array($problem,['open','resolved'],true)?$problem:'';
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=['o.deleted_at IS NULL'];
        if($this->status!=='')$where[]='s.status='.$db->quote($this->status);
        if($this->carrierFilter!=='')$where[]='s.carrier='.$db->quote($this->carrierFilter);
        if($this->reasonFilter!=='')$where[]='s.reason='.$db->quote($this->reasonFilter);
        if($this->problemFilter!=='')$where[]='s.problem_status='.$db->quote($this->problemFilter);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $terms=['o.customer_name LIKE '.$like,'o.phone LIKE '.$like,
                'o.delivery_city LIKE '.$like,'s.tracking_number LIKE '.$like];
            if(ctype_digit($this->search)){
                $terms[]='s.id='.(int)$this->search;
                $terms[]='s.order_id='.(int)$this->search;
            }
            $where[]='('.implode(' OR ',$terms).')';
        }
        $base=static function() use($db) {
            return $db->getQuery(true)
                ->from($db->quoteName('#__minimarket_shipments','s'))
                ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=s.order_id');
        };
        $count=$base()->select('COUNT(*)');
        foreach($where as $condition)$count->where($condition);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $this->offset=$this->total===0?0:min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        $q=$base()->select(['s.id','s.order_id','s.carrier','s.tracking_number',
            's.reason','s.status','s.problem_status','s.problem_code',
            's.created_at','o.customer_name','o.phone','o.delivery_city',
            'o.delivery_address','o.delivery_fee_cents'])
            ->order('s.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $condition)$q->where($condition);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->deliveries=(array)$db->loadObjectList();

        ToolbarHelper::title(Text::_('COM_MINIMARKET_DASHBOARD_DELIVERIES'));
        if($this->canCreate)ToolbarHelper::addNew('deliveries.add');
        if($user->authorise('core.edit.state','com_minimarket')){
            ToolbarHelper::custom('deliveries.deliver','publish','',Text::_('COM_MINIMARKET_DELIVERY_STATUS_DELIVERED'),true);
            ToolbarHelper::custom('deliveries.cancel','unpublish','',Text::_('COM_MINIMARKET_DELIVERY_STATUS_CANCELLED'),true);
        }
        if($user->authorise('core.options','com_minimarket')||$user->authorise('core.admin','com_minimarket'))
            ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
