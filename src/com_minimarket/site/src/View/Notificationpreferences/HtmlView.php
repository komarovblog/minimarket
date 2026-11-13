<?php
namespace MiniMarket\Component\Minimarket\Site\View\Notificationpreferences;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\NotificationPreferences;

final class HtmlView extends BaseHtmlView
{
    public array $channels=[],$preferences=[];
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if((int)$user->id<1||!empty($user->block))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_NOTIFY_LOGIN_REQUIRED'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $params=ComponentHelper::getParams('com_minimarket');
        $this->channels=NotificationPreferences::available($db,$params);
        $this->preferences=NotificationPreferences::load($db,(int)$user->id);
        $app->setHeader('Cache-Control','private, no-store',true);
        $this->getDocument()->setTitle(Text::_('COM_MINIMARKET_NOTIFY_PREFERENCES_TITLE'));
        $this->getDocument()->setMetaData('robots','noindex,nofollow');
        parent::display($tpl);
    }
}
