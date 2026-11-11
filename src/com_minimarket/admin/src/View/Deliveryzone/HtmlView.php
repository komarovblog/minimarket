<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Deliveryzone;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
 public ?object $zone=null;
 public function display($tpl=null)
 {
  $app=Factory::getApplication();$id=$app->input->getInt('id',0);
  if(!$app->getIdentity()->authorise($id?'core.edit':'core.create','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  if($id){
   $db=Factory::getContainer()->get(DatabaseInterface::class);
   $db->setQuery($db->getQuery(true)->select('*')
     ->from($db->quoteName('#__minimarket_delivery_zones'))->where('id='.(int)$id));
   $this->zone=$db->loadObject()?:null;
   if(!$this->zone)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
  }
  $this->setLayout('edit');
  ToolbarHelper::title(Text::_('COM_MINIMARKET_ZONES_TITLE'));
  parent::display($tpl);
 }
}