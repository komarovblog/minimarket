<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Recommendations;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView {
 public array $items=[];
 public int $total=0,$offset=0,$pageSize=20;
 public string $search='',$sort='newest';
 public function display($tpl=null) {
  $app=Factory::getApplication();$user=$app->getIdentity();
  if(!$user->authorise('core.manage','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
  $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
  $size=$app->input->getInt('limit',20);
  $this->pageSize=in_array($size,[20,50,100],true)?$size:20;
  $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
  $db=Factory::getContainer()->get(DatabaseInterface::class);
  $predicate='';
  if($this->search!==''){
   $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
   $predicate=' AND (p.title LIKE '.$like.' OR p.sku LIKE '.$like
    .(ctype_digit($this->search)?' OR p.id='.(int)$this->search:'').')';
  }
  $table=$db->quoteName('#__minimarket_products');
  $db->setQuery('SELECT COUNT(*) FROM '.$table.' AS p WHERE 1=1'.$predicate);
  $this->total=(int)$db->loadResult();
  if($this->total===0)$this->offset=0;
  else $this->offset=min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
  $db->setQuery('SELECT p.id,p.title,p.sku,p.product_type,p.state,'
   ."COALESCE(s.similar_mode,'auto') AS similar_mode,"
   ."COALESCE(s.together_mode,'auto') AS together_mode"
   .' FROM '.$table.' AS p LEFT JOIN '
   .$db->quoteName('#__minimarket_recommendation_settings').' AS s ON s.product_id=p.id'
   .' WHERE 1=1'.$predicate.' ORDER BY p.id '.($this->sort==='oldest'?'ASC':'DESC'),
   $this->offset,$this->pageSize);
  $this->items=(array)$db->loadObjectList();
  ToolbarHelper::title(Text::_('COM_MINIMARKET_RECOMMENDATIONS_TITLE'));
  parent::display($tpl);
 }
}
