<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Messagetemplates;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools;

final class HtmlView extends BaseHtmlView
{
    public array $items=[];
    public int $total=0,$offset=0,$pageSize=20;
    public string $search='',$audience='',$sort='newest';

    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        NativeListTools::importRequest();
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $type=$app->input->getCmd('filter_audience','');
        $this->audience=in_array($type,['customer','admin','marketing'],true)?$type:'';
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $lim=$app->input->getInt('limit',20);
        $this->pageSize=in_array($lim,[20,50,100],true)?$lim:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=[];
        if($this->audience!=='')$where[]='audience='.$db->quote($this->audience);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $where[]='(title LIKE '.$like.(ctype_digit($this->search)?' OR id='.(int)$this->search:'').')';
        }
        $table=$db->quoteName('#__minimarket_message_templates');
        $q=$db->getQuery(true)->select('COUNT(*)')->from($table);
        foreach($where as $w)$q->where($w);
        $db->setQuery($q);$this->total=(int)$db->loadResult();
        $this->offset=$this->total?min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize)):0;
        $q=$db->getQuery(true)->select(['id','title','audience','event_code','channel_code','state','updated_at','created_at'])
            ->from($table)->order('id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $w)$q->where($w);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->items=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_MESSAGE_TEMPLATES_TITLE'));
        if($user->authorise('core.create','com_minimarket'))
            ToolbarHelper::addNew('messagetemplates.add');
        parent::display($tpl);
    }
}
