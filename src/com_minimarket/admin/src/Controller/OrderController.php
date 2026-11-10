<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/** Operators may change order status, never line snapshots or prices. */
final class OrderController extends BaseController
{
    public function status(): void
    {
        $app=Factory::getApplication();
        if ($app->input->getMethod()!=='POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        }
        if (!$app->getIdentity()->authorise('core.edit.state','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $id=$app->input->post->getInt('id',0);
        $status=$app->input->post->getCmd('status','');
        if ($id<1 || !in_array($status,['new','confirmed','completed','cancelled'],true)) {
            throw new \RuntimeException(Text::_('COM_MINIMARKET_ORDER_STATUS_INVALID'),400);
        }
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_orders'))
            ->set($db->quoteName('status').'='.$db->quote($status))
            ->where($db->quoteName('id').'='.(int)$id);
        $db->setQuery($q);$db->execute();
        $app->enqueueMessage(Text::_('COM_MINIMARKET_ORDER_STATUS_SAVED'));
        $this->setRedirect('index.php?option=com_minimarket&view=order&id='.$id);
    }
}
