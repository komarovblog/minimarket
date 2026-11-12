<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
final class DeliveryzonesController extends BaseController
{
 private function setSelectedState(int $state):void
 {
  $app=Factory::getApplication();
  if($app->input->getMethod()!=='POST'||!\Joomla\CMS\Session\Session::checkToken('post'))
   throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
  if(!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $posted=$app->input->post->get('cid',[],'array');
  $ids=array_values(array_unique(array_filter(array_map('intval',
   is_array($posted)?$posted:[]),static fn(int $id):bool=>$id>0)));
  if(!$ids||count($ids)>100)throw new \RuntimeException(Text::_('COM_MINIMARKET_ZONE_INVALID'),400);
  $db=Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
  $db->setQuery($db->getQuery(true)
   ->update($db->quoteName('#__minimarket_delivery_zones'))
   ->set($db->quoteName('state').'='.(int)$state)
   ->set('updated_at=UTC_TIMESTAMP()')
   ->where($db->quoteName('id').' IN ('.implode(',',$ids).')'));
  $db->execute();
  $app->enqueueMessage(Text::_('COM_MINIMARKET_ZONE_SAVED'));
  $this->setRedirect('index.php?option=com_minimarket&view=deliveryzones');
 }
 public function publish():void { $this->setSelectedState(1); }
 public function unpublish():void { $this->setSelectedState(0); }
 public function add():void {
  if(!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $this->setRedirect('index.php?option=com_minimarket&view=deliveryzone&layout=create');
 }
}