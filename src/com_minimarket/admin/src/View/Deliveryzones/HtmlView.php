<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Deliveryzones;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
 public array $zones=[];
 public string $search='', $sort='newest';
 public int $pageSize=20,$offset=0,$total=0;
 public function display($tpl=null)
 {
  $app=Factory::getApplication();$u=$app->getIdentity();
  if(!$u->authorise('core.manage','com_minimarket'))
    throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
  $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
  $limit=$app->input->getInt('limit',20);
  $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
  $this->offset=max(0,$app->input->getInt('limitstart',0));
  $db=Factory::getContainer()->get(DatabaseInterface::class);
  $q=$db->getQuery(true)->select('*')->from($db->quoteName('#__minimarket_delivery_zones'))
    ->order('id '.($this->sort==='oldest'?'ASC':'DESC'));
  if($this->search!==''){
   $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
   $q->where('(title LIKE '.$like.' OR cities LIKE '.$like.' OR region LIKE '.$like.')');
  }
  $db->setQuery($q,0,2000);$all=(array)$db->loadObjectList();
  $this->total=count($all);$this->zones=array_slice($all,$this->offset,$this->pageSize);
  ToolbarHelper::title(Text::_('COM_MINIMARKET_ZONES_TITLE'));
  if($u->authorise('core.create','com_minimarket'))ToolbarHelper::addNew('deliveryzones.add');
  parent::display($tpl);
 }
}