<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Tickets;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $tickets=[];
    public int $total=0,$offset=0,$pageSize=20;
    public string $search='',$statusFilter='',$sort='newest';
    public bool $canCreate=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$u=$app->getIdentity();
        if(!$u->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$u->authorise('core.create','com_minimarket');
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $state=$app->input->getCmd('filter_status','');
        $this->statusFilter=in_array($state,['new','in_progress','closed'],true)?$state:'';
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=[];
        if($this->statusFilter!=='')$where[]='t.status='.$db->quote($this->statusFilter);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $search=['t.subject LIKE '.$like];
            if(ctype_digit($this->search))$search[]='t.id='.(int)$this->search;
            $where[]='('.implode(' OR ',$search).')';
        }
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_support_tickets','t'));
        foreach($where as $term)$q->where($term);
        $db->setQuery($q);$this->total=(int)$db->loadResult();
        $this->offset=$this->total?min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize)):0;
        $q=$db->getQuery(true)->select(['t.id','t.subject','t.status',
            't.order_id','t.user_id','t.created_at','t.updated_at'])
            ->from($db->quoteName('#__minimarket_support_tickets','t'))
            ->order('t.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $term)$q->where($term);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->tickets=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_TICKETS_TITLE'));
        parent::display($tpl);
    }
}
