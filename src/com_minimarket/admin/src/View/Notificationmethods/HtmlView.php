<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Notificationmethods;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins;
use MiniMarket\Component\Minimarket\Administrator\Service\MethodListRows;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;

final class HtmlView extends BaseHtmlView
{
    public $params;
    public array $plugins=[],$methodRows=[],$events=[];
    public string $method='',$search='',$typeFilter='',$statusFilter='all',$sort='name_asc';
    public int $total=0,$offset=0,$pageSize=20;
    public bool $canEdit=false,$canEditPlugins=false,$channelEnabled=false;
    public string $pluginName='';

    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canEdit=$user->authorise('core.options','com_minimarket')
            ||$user->authorise('core.admin','com_minimarket');
        $this->canEditPlugins=$user->authorise('core.edit','com_plugins')
            ||$user->authorise('core.admin','com_plugins');
        $this->params=ComponentHelper::getParams('com_minimarket');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $this->plugins=InstalledMethodPlugins::list($db,'notification');
        $raw=$app->input->getCmd('method','');
        if($raw==='email') {
            $this->method='email';
            $this->channelEnabled=(int)$this->params->get('notification_email_enabled',0)===1;
        }elseif(preg_match('/^plugin_([1-9][0-9]*)$/D',$raw,$m)){
            foreach($this->plugins as $plugin){
                if((int)$plugin->extension_id===(int)$m[1]){
                    $this->method=$raw;
                    $this->channelEnabled=(int)$plugin->enabled===1;
                    $this->pluginName=Text::_((string)$plugin->name);
                    break;
                }
            }
            if($this->method==='')throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        }elseif($raw!=='')throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $this->events=NotificationPolicy::allowedEvents($this->params,$this->method);
        if($this->method===''){
            $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
            $type=$app->input->getCmd('filter_type','');
            $this->typeFilter=in_array($type,['builtin','plugin'],true)?$type:'';
            $state=$app->input->getCmd('filter_status','all');
            $this->statusFilter=in_array($state,['all','enabled','disabled'],true)?$state:'all';
            $sort=$app->input->getCmd('filter_sort','name_asc');
            $this->sort=in_array($sort,['name_asc','name_desc'],true)?$sort:'name_asc';
            $limit=$app->input->getInt('limit',20);
            $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
            $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
            $result=MethodListRows::collect('notification',$this->params,$this->plugins,
                $this->canEdit,$this->canEditPlugins,$this->search,
                $this->typeFilter,$this->statusFilter,$this->sort,
                $this->pageSize,$this->offset);
            $this->methodRows=$result['rows'];
            $this->offset=$result['offset'];$this->total=$result['total'];
            ToolbarHelper::custom('methodsettings.editSelected','edit','',Text::_('JTOOLBAR_EDIT'),true);
        }
        ToolbarHelper::title(Text::_('COM_MINIMARKET_NOTIFICATION_METHODS_TITLE'));
        parent::display($tpl);
    }
}
