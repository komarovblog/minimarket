<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Notificationevents;
\defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;

final class HtmlView extends BaseHtmlView
{
    public array $channels=[],$customer=[],$admin=[];
    public bool $canEdit=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canEdit=$user->authorise('core.options','com_minimarket')
            ||$user->authorise('core.admin','com_minimarket');
        $params=ComponentHelper::getParams('com_minimarket');
        $this->channels[]=['code'=>'email',
            'name'=>Text::_('COM_MINIMARKET_NOTIFY_EMAIL'),
            'enabled'=>(int)$params->get('notification_email_enabled',0)===1];
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        foreach(InstalledMethodPlugins::list($db,'notification') as $plugin){
            $this->channels[]=['code'=>'plugin_'.(int)$plugin->extension_id,
                'name'=>Text::_((string)$plugin->name),
                'enabled'=>(int)$plugin->enabled===1];
        }
        foreach($this->channels as $channel){
            $code=$channel['code'];
            $this->customer[$code]=NotificationPolicy::allowedEvents($params,$code);
            $this->admin[$code]=NotificationPolicy::allowedAdminEvents($params,$code);
        }
        ToolbarHelper::title(Text::_('COM_MINIMARKET_NOTIFICATION_EVENTS_TITLE'));
        parent::display($tpl);
    }
}
