<?php
namespace MiniMarket\Component\Minimarket\Site\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\NotificationPreferences;

final class NotificationpreferencesController extends BaseController
{
    public function save():void
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if((int)$user->id<1||!empty($user->block))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_NOTIFY_LOGIN_REQUIRED'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try {
            $allowed=NotificationPreferences::available(
                $db,ComponentHelper::getParams('com_minimarket'));
            $post=$app->input->post->get('preferences',[],'array');
            if(!is_array($post))throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            NotificationPreferences::save($db,(int)$user->id,$post,$allowed);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_NOTIFY_PREF_SAVED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_NOTIFY_PREF_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=notificationpreferences');
    }
}
