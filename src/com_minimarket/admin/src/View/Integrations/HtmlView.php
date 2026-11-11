<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Integrations;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** Only actually installed MiniMarket plugins; settings remain in com_plugins. */
final class HtmlView extends BaseHtmlView
{
    public array $plugins=[];
    public bool $canManagePlugins=false;
    public string $search='', $statusFilter='all', $sort='newest';
    public int $total=0, $offset=0, $pageSize=20;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canManagePlugins=$user->authorise('core.manage','com_plugins');
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $requested=$app->input->getCmd('filter_status','all');
        $this->statusFilter=in_array($requested,['all','enabled','disabled'],true)?$requested:'all';
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=['type='.$db->quote('plugin'),'folder='.$db->quote('minimarket')];
        if($this->statusFilter!=='all')$where[]='enabled='.($this->statusFilter==='enabled'?1:0);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $where[]='(name LIKE '.$like.' OR element LIKE '.$like.')';
        }
        $count=$db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__extensions'));
        foreach($where as $v)$count->where($v);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $this->offset=$this->total===0?0:min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        $query=$db->getQuery(true)->select(['extension_id','name','element','enabled'])
            ->from($db->quoteName('#__extensions'))
            ->order('extension_id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $v)$query->where($v);
        $db->setQuery($query,$this->offset,$this->pageSize);
        $this->plugins=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_INTEGRATIONS_TITLE'));
        if($user->authorise('core.options','com_minimarket')||$user->authorise('core.admin','com_minimarket'))
            ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
