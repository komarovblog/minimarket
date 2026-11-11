<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Seofilters;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $pages=[];
    public string $search='', $statusFilter='all', $sort='newest';
    public int $total=0, $offset=0, $pageSize=20;
    public bool $canCreate=false, $canPublish=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $this->canPublish=$user->authorise('core.edit.state','com_minimarket');
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
            $conditions[]='s.state='.($this->statusFilter==='published'?1:0);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $terms=['s.title LIKE '.$like];
            if(ctype_digit($this->search))$terms[]='s.id='.(int)$this->search;
            $conditions[]='('.implode(' OR ',$terms).')';
        }
        $base=static function() use($db) {
            return $db->getQuery(true)->from($db->quoteName('#__minimarket_seo_filters','s'))
                ->join('INNER',$db->quoteName('#__minimarket_categories','c').' ON c.id=s.category_id');
        };
        $count=$base()->select('COUNT(*)');
        foreach($conditions as $condition)$count->where($condition);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $this->offset=$this->total===0?0:min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        $q=$base()->select(['s.id','s.category_id','s.alias','s.title','s.heading','s.state','c.title AS category_title'])->order('s.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($conditions as $condition)$q->where($condition);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->pages=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_SEOFILTERS_TITLE'));
        if($this->canCreate)ToolbarHelper::addNew('seofilters.add');
        if($this->canPublish){
            ToolbarHelper::custom('seofilters.publish','publish','',Text::_('COM_MINIMARKET_LIST_PUBLISH'),true);
            ToolbarHelper::custom('seofilters.unpublish','unpublish','',Text::_('COM_MINIMARKET_LIST_UNPUBLISH'),true);
        }
        if($user->authorise('core.options','com_minimarket')||$user->authorise('core.admin','com_minimarket'))
            ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
