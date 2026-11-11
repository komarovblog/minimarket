<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;

/** Feed configuration uses explicit ACL and CSRF for all writes. */
final class FeedController extends BaseController
{
    private function permitted(): void
    {
        $user=Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.manage','com_minimarket') ||
            !$user->authorise('core.edit','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
    }

    public function add(): void
    {
        $this->permitted();
        $this->setRedirect('index.php?option=com_minimarket&view=feed');
    }

    public function save(): void
    {
        Session::checkToken('post') or die(Text::_('JINVALID_TOKEN'));
        $this->permitted();
        $app=Factory::getApplication();
        $input=$app->input->post->get('feed',[],'array');
        if (!is_array($input))throw new \InvalidArgumentException('COM_MINIMARKET_FEED_INVALID');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        [$cats,$products]=FeedConfig::catalog($db);
        try {
            $id=FeedConfig::save($db,$input,$cats,$products);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_FEED_SAVED'),'message');
            $this->setRedirect(Route::_('index.php?option=com_minimarket&view=feed&id='.$id,false));
        } catch (\InvalidArgumentException $error) {
            $app->enqueueMessage(Text::_($error->getMessage()),'error');
            $this->setRedirect(Route::_('index.php?option=com_minimarket&view=feeds',false));
        }
    }

    public function delete(): void
    {
        Session::checkToken('post') or die(Text::_('JINVALID_TOKEN'));
        $this->permitted();
        $id=$this->input->getInt('id');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        if (!FeedConfig::find($db,$id)) {
            throw new \RuntimeException(Text::_('COM_MINIMARKET_FEED_NOT_FOUND'),404);
        }
        FeedConfig::delete($db,$id);
        Factory::getApplication()->enqueueMessage(Text::_('COM_MINIMARKET_FEED_DELETED'));
        $this->setRedirect(Route::_('index.php?option=com_minimarket&view=feeds',false));
    }
}
