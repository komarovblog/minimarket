<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Returns;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $returns=[];
    public int $total=0,$offset=0,$pageSize=20;
    public string $search='',$statusFilter='',$reasonFilter='',$sort='newest';
    public bool $canCreate=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $u=$app->getIdentity();
        if(!$u->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$u->authorise('core.create','com_minimarket');
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $status=$app->input->getCmd('filter_status','');
        $reason=$app->input->getCmd('filter_reason','');
        $this->statusFilter=in_array($status,['new','approved','received','rejected','closed'],true)?$status:'';
        $this->reasonFilter=in_array($reason,['defective','damaged','wrong_item','not_fit','other'],true)?$reason:'';
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=['o.deleted_at IS NULL'];
        if($this->statusFilter!=='')$where[]='r.status='.$db->quote($this->statusFilter);
        if($this->reasonFilter!=='')$where[]='r.reason='.$db->quote($this->reasonFilter);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $terms=['o.customer_name LIKE '.$like,'o.phone LIKE '.$like];
            if(ctype_digit($this->search)){
                $terms[]='r.id='.(int)$this->search;
                $terms[]='r.order_id='.(int)$this->search;
            }
            $where[]='('.implode(' OR ',$terms).')';
        }
        $base=static fn()=> $db->getQuery(true)
            ->from($db->quoteName('#__minimarket_returns','r'))
            ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=r.order_id');
        $q=$base()->select('COUNT(*)');
        foreach($where as $condition)$q->where($condition);
        $db->setQuery($q);$this->total=(int)$db->loadResult();
        $this->offset=$this->total===0?0:min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        $q=$base()->select(['r.id','r.order_id','r.shipment_id','r.reason',
            'r.status','r.resolution','r.created_at','o.customer_name'])
            ->order('r.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $condition)$q->where($condition);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->returns=(array)$db->loadObjectList();

        ToolbarHelper::title(Text::_('COM_MINIMARKET_RETURNS_TITLE'));
        if($this->canCreate)ToolbarHelper::addNew('returncase.add');
        if($u->authorise('core.edit.state','com_minimarket')){
            ToolbarHelper::custom('returns.approve','publish','',
                Text::_('COM_MINIMARKET_RETURN_STATUS_APPROVED'),true);
            ToolbarHelper::custom('returns.receive','checkmark','',
                Text::_('COM_MINIMARKET_RETURN_STATUS_RECEIVED'),true);
            ToolbarHelper::custom('returns.reject','unpublish','',
                Text::_('COM_MINIMARKET_RETURN_STATUS_REJECTED'),true);
        }
        parent::display($tpl);
    }
}
