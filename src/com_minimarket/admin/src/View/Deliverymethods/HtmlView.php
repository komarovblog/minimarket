<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Deliverymethods;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins;
use MiniMarket\Component\Minimarket\Administrator\Service\MethodListRows;
use MiniMarket\Component\Minimarket\Administrator\Service\PluginMethodSettings;

final class HtmlView extends BaseHtmlView
{
    public $params;
    public array $plugins=[];
    public ?object $pluginMethod=null;
    public $pluginForm=null;
    public array $methodRows=[];
    public string $search='', $typeFilter='', $statusFilter='all', $sort='name_asc';
    public int $total=0, $offset=0, $pageSize=20;
    public bool $canEdit=false,$canEditPlugins=false;
    public string $method='';
    public int $zones=0;
    public array $zoneRows=[];
    public ?object $zone=null;
    public bool $embeddedZoneEditor=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket')){
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $raw=$app->input->getCmd('method','');
        $this->method=in_array($raw,['pickup','courier'],true)?$raw:'';
        $this->canEdit=$user->authorise('core.options','com_minimarket')
            || $user->authorise('core.admin','com_minimarket');
        $this->canEditPlugins=$user->authorise('core.edit','com_plugins')
            || $user->authorise('core.admin','com_plugins');
        $this->params=ComponentHelper::getParams('com_minimarket');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $this->plugins=InstalledMethodPlugins::list($db,'delivery');
        if(preg_match('/^plugin_([1-9][0-9]*)$/D',($raw??''),$m)){
            $detail=PluginMethodSettings::load($db,'delivery',(int)$m[1]);
            if($detail===null)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
            $this->pluginMethod=$detail['plugin'];
            $this->pluginForm=$this->canEditPlugins?$detail['form']:null;
            $this->method=$raw;
        }
        if($this->method===''){
            $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
            $type=$app->input->getCmd('filter_type','');
            $this->typeFilter=in_array($type,['builtin','plugin'],true)?$type:'';
            $status=$app->input->getCmd('filter_status','all');
            $this->statusFilter=in_array($status,['all','enabled','disabled'],true)?$status:'all';
            $sort=$app->input->getCmd('filter_sort','name_asc');
            $this->sort=in_array($sort,['name_asc','name_desc'],true)?$sort:'name_asc';
            $limit=$app->input->getInt('limit',20);
            $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
            $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
            $list=MethodListRows::collect('delivery',$this->params,$this->plugins,
                $this->canEdit,$this->canEditPlugins,$this->search,
                $this->typeFilter,$this->statusFilter,$this->sort,
                $this->pageSize,$this->offset);
            $this->methodRows=$list['rows'];
            $this->total=$list['total'];
            $this->offset=$list['offset'];
        }
        $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_delivery_zones'));
        $this->zones=(int)$db->loadResult();
        if($this->method==='courier') {
            $q=$db->getQuery(true)->select(['id','title','cities','rate_mode','base_fee_cents',
                'free_from_cents','state'])
                ->from($db->quoteName('#__minimarket_delivery_zones'))
                ->order('priority ASC,id ASC');
            $db->setQuery($q,0,500);
            $this->zoneRows=(array)$db->loadObjectList();
            $editingId=max(0,$app->input->getInt('zone_id',0));
            $new=$app->input->getInt('zone_new',0)===1;
            if($editingId>0 || $new) {
                if(!$user->authorise($editingId?'core.edit':'core.create','com_minimarket'))
                    throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
                $this->embeddedZoneEditor=true;
                if($editingId>0){
                    $db->setQuery($db->getQuery(true)->select('*')
                        ->from($db->quoteName('#__minimarket_delivery_zones'))
                        ->where('id='.(int)$editingId));
                    $this->zone=$db->loadObject()?:null;
                    if($this->zone===null)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
                }
            }
        }
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DELIVERY_METHODS_TITLE'));
        if($this->canEdit)ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
