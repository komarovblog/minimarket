<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Autodiscounts;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseView {
 public array $discounts=[];
 public string $search='',$statusFilter='all',$sort='newest';
 public int $offset=0,$total=0,$pageSize=20;
 public function display($tpl=null) {
  $app=Factory::getApplication();$user=$app->getIdentity();
  if(!$user->authorise('core.manage','com_minimarket'))throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
  $this->statusFilter=$app->input->getCmd('filter_status','all');
  if(!in_array($this->statusFilter,['all','published','unpublished'],true))$this->statusFilter='all';
  $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
  $limit=$app->input->getInt('limit',20);$this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
  $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
  $db=Factory::getContainer()->get(DatabaseInterface::class);
  $where=[];
  if($this->statusFilter!=='all')$where[]='state='.($this->statusFilter==='published'?1:0);
  if($this->search!==''){
   $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
   $where[]='(title LIKE '.$like.(ctype_digit($this->search)?' OR id='.(int)$this->search:'').')';
  }
  $table=$db->quoteName('#__minimarket_auto_discounts');
  $q=$db->getQuery(true)->select('COUNT(*)')->from($table);
  foreach($where as $w)$q->where($w);
  $db->setQuery($q);$this->total=(int)$db->loadResult();
  $this->offset=$this->total?min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize)):0;
  $q=$db->getQuery(true)->select('*')->from($table)->order('id '.($this->sort==='oldest'?'ASC':'DESC'));
  foreach($where as $w)$q->where($w);
  $db->setQuery($q,$this->offset,$this->pageSize);
  $this->discounts=(array)$db->loadObjectList();
  ToolbarHelper::title(Text::_('COM_MINIMARKET_AUTO_DISCOUNTS_TITLE'));
  if($user->authorise('core.create','com_minimarket'))ToolbarHelper::addNew('autodiscounts.add');
  if($user->authorise('core.edit.state','com_minimarket')){
   ToolbarHelper::custom('autodiscounts.publish','publish','',Text::_('COM_MINIMARKET_LIST_PUBLISH'),true);
   ToolbarHelper::custom('autodiscounts.unpublish','unpublish','',Text::_('COM_MINIMARKET_LIST_UNPUBLISH'),true);
  }
  parent::display($tpl);
 }
}
