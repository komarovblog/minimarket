<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins;
use MiniMarket\Component\Minimarket\Administrator\Service\MethodSettings;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;

/** Event permission matrix. Provider secrets remain owned by provider plugins. */
final class NotificationmethodsController extends BaseController
{
    public function save():void
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$user->authorise('core.options','com_minimarket')
            &&!$user->authorise('core.admin','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $method=$app->input->post->getCmd('method','');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try {
            $raw=$app->input->post->get('events',[],'array');
            if(!is_array($raw))throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            $events=NotificationPolicy::normalizeEvents($raw);
            if($method==='email'){
                $enabled=$app->input->post->getInt('enabled',0);
                if(!in_array($enabled,[0,1],true))
                    throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
                $changes=['notification_email_enabled'=>$enabled,
                    'notification_email_events'=>$events];
            }elseif(preg_match('/^plugin_([1-9][0-9]*)$/D',$method,$m)){
                $installed=false;
                foreach(InstalledMethodPlugins::list($db,'notification') as $p)
                    if((int)$p->extension_id===(int)$m[1])$installed=true;
                if(!$installed)throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
                $all=ComponentHelper::getParams('com_minimarket')->get('notification_plugin_events',[]);
                if($all instanceof \Joomla\Registry\Registry)$all=$all->toArray();
                if(!is_array($all))$all=[];
                $all[$method]=$events;
                $changes=['notification_plugin_events'=>$all];
            }else throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            MethodSettings::save($db,$changes);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_NOTIFY_EVENTS_SAVE'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_METHOD_SETTINGS_INVALID'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=notificationmethods&method='.$method);
    }
}
