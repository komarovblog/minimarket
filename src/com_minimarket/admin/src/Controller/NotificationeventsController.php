<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins;
use MiniMarket\Component\Minimarket\Administrator\Service\MethodSettings;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;

/** Event-first editor shares the existing customer channel settings. */
final class NotificationeventsController extends BaseController
{
    public function save():void
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$user->authorise('core.admin','com_minimarket')
          &&!$user->authorise('core.options','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try{
            $known=['email'=>true];
            foreach(InstalledMethodPlugins::list($db,'notification') as $plugin)
                $known['plugin_'.(int)$plugin->extension_id]=true;
            $customer=$app->input->post->get('customer',[],'array');
            $admin=$app->input->post->get('admin',[],'array');
            if(!is_array($customer)||!is_array($admin))
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            foreach(array_keys($customer+$admin) as $channel)
                if(!isset($known[$channel]))
                    throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            $params=ComponentHelper::getParams('com_minimarket');
            $cPlugins=$params->get('notification_plugin_events',[]);
            $aPlugins=$params->get('notification_admin_plugin_events',[]);
            if($cPlugins instanceof Registry)$cPlugins=$cPlugins->toArray();
            if($aPlugins instanceof Registry)$aPlugins=$aPlugins->toArray();
            if(!is_array($cPlugins))$cPlugins=[];
            if(!is_array($aPlugins))$aPlugins=[];
            $changes=[];
            foreach(array_keys($known) as $code){
                $customerEvents=$customer[$code]??[];
                $adminEvents=$admin[$code]??[];
                if(!is_array($customerEvents)||!is_array($adminEvents))
                    throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
                $c=NotificationPolicy::normalizeEvents($customerEvents);
                $a=NotificationPolicy::normalizeAdminEvents($adminEvents);
                if($code==='email'){
                    $changes['notification_email_events']=$c;
                    $changes['notification_admin_email_events']=$a;
                }else{
                    $cPlugins[$code]=$c;$aPlugins[$code]=$a;
                }
            }
            $changes['notification_plugin_events']=$cPlugins;
            $changes['notification_admin_plugin_events']=$aPlugins;
            MethodSettings::save($db,$changes);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_NOTIFY_EVENTS_SAVE'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=notificationevents');
    }
}
