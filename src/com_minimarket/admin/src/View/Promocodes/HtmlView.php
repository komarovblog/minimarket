<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\${d.n};
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $codes=[];
    public string $search='', $statusFilter='all', $sort='newest';
    public int $total=0, $offset=0, $pageSize=20;
    public bool $canCreate=false, $canUpdate=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $this->canUpdate=$user->authorise('core.edit.state','com_minimarket');
        $raw=$app->input->getCmd('filter_status','all');
        $this->statusFilter=in_array($raw,['all','published','unpublished'],true)?$raw:'all';
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $conditions=[];
        if($this->statusFilter!=='all')
            $conditions[]='p.state='.($this->statusFilter==='published'?1:0);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $terms=['p.code LIKE '.$like];
            if(ctype_digit($this->search))$terms[]='p.id='.(int)$this->search;
            $conditions[]='('.implode(' OR ',$terms).')';
        }
        $base=static function() use($db) {
            return $db->getQuery(true)->from($db->quoteName('#__minimarket_promo_codes','p'));
        };
        $count=$base()->select('COUNT(*)');
        foreach($conditions as $condition)$count->where($condition);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $this->offset=$this->total===0?0:min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        $q=$base()->select(['p.id','p.code','p.amount_cents','p.discount_type','p.percent_value','p.starts_at','p.ends_at','p.usage_limit','p.used_count','p.state','p.created_at'])->order('p.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($conditions as $condition)$q->where($condition);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->codes=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_PROMOCODES_TITLE'));
        if($this->canCreate)ToolbarHelper::addNew('promocodes.add');
        if($this->canUpdate){
            ToolbarHelper::custom('promocodes.publish','publish','',Text::_('COM_MINIMARKET_LIST_PUBLISH'),true);
            ToolbarHelper::custom('promocodes.unpublish','unpublish','',Text::_('COM_MINIMARKET_LIST_UNPUBLISH'),true);
        }
        if($user->authorise('core.options','com_minimarket')||$user->authorise('core.admin','com_minimarket'))
            ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
