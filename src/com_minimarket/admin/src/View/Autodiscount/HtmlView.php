<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Autodiscount;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseView {
 public ?object $rule=null;
 public array $selected=[];
 public bool $canPublish=false;
 public function display($tpl=null) {
  $app=Factory::getApplication();$user=$app->getIdentity();
  $id=$app->input->getInt('id',0);
  if(!$user->authorise($id?'core.edit':'core.create','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $this->canPublish=$user->authorise('core.edit.state','com_minimarket');
  if($id>0){
   $db=Factory::getContainer()->get(DatabaseInterface::class);
   $db->setQuery($db->getQuery(true)->select('*')
    ->from($db->quoteName('#__minimarket_auto_discounts'))->where('id='.(int)$id));
   $this->rule=$db->loadObject()?:null;
   if(!$this->rule)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
   $scope=(string)$this->rule->applies_to;
   $targets=json_decode((string)$this->rule->target_ids_json,true);
   $table=match($scope){
    'product'=>'#__minimarket_products','category'=>'#__minimarket_categories',
    'brand'=>'#__minimarket_brands',default=>null
   };
   if($table!==null&&is_array($targets)&&$targets){
    $ids=array_values(array_unique(array_filter(array_map('intval',$targets),fn($id)=>$id>0)));
    if($ids){
     $q=$db->getQuery(true)->select(['id','title'])->from($db->quoteName($table))
      ->where('id IN ('.implode(',',$ids).')');
     $db->setQuery($q);
     foreach((array)$db->loadAssocList() as $row)$this->selected[(int)$row['id']]=(string)$row['title'];
    }
   }
  }
  $this->setLayout('edit');
  ToolbarHelper::title(Text::_('COM_MINIMARKET_AUTO_DISCOUNTS_TITLE'));
  parent::display($tpl);
 }
}
