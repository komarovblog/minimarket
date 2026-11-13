<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Marketingflows;
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
    public int $total=0,$pageSize=20,$offset=0;
    public string $search='',$sort='newest';
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        NativeListTools::importRequest();
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);$this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where='';
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $where=' WHERE m.title LIKE '.$like.(ctype_digit($this->search)?' OR m.id='.(int)$this->search:'');
        }
        $table=$db->quoteName('#__minimarket_marketing_flows','m');
        $db->setQuery('SELECT COUNT(*) FROM '.$table.$where);
        $this->total=(int)$db->loadResult();
        $this->offset=$this->total?min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize)):0;
        $db->setQuery('SELECT m.id,m.title,m.trigger_code,m.state,m.stop_on_order,'
            .'COUNT(s.id) AS step_count FROM '.$table
            .' LEFT JOIN '.$db->quoteName('#__minimarket_marketing_flow_steps','s')
            .' ON s.flow_id=m.id'.$where
            .' GROUP BY m.id,m.title,m.trigger_code,m.state,m.stop_on_order'
            .' ORDER BY m.id '.($this->sort==='oldest'?'ASC':'DESC'),
            $this->offset,$this->pageSize);
        $this->items=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_MARKETING_FLOWS_TITLE'));
        if($user->authorise('core.create','com_minimarket'))
            ToolbarHelper::addNew('marketingflows.add');
        parent::display($tpl);
    }
}
