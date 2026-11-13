<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use MiniMarket\Component\Minimarket\Administrator\Service\ListBulkActions;
final class AutodiscountsController extends BaseController {
 public function add():void{
  if(!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $this->setRedirect('index.php?option=com_minimarket&view=autodiscount&layout=create');
 }
 public function publish():void{$this->change('1');}
 public function unpublish():void{$this->change('0');}
 private function change(string $state):void{
  $app=Factory::getApplication();
  try{
   $n=ListBulkActions::apply('#__minimarket_auto_discounts','state',$state,['0','1'],false,false);
   $app->enqueueMessage(Text::sprintf('COM_MINIMARKET_LIST_BULK_DONE',$n));
  }catch(\DomainException $e){$app->enqueueMessage(Text::_($e->getMessage()),'warning');}
  $this->setRedirect('index.php?option=com_minimarket&view=autodiscounts');
 }
}
