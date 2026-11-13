<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminInbox;

final class NotificationsController extends BaseController
{
    public function markRead():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $actor=$app->getIdentity();
        if(!$actor->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $post=$app->input->post;$read=$post->getInt('read',-1);
        try{
            if(!in_array($read,[0,1],true))
                throw new \DomainException('COM_MINIMARKET_INBOX_STALE');
            AdminInbox::mark(Factory::getContainer()->get(DatabaseInterface::class),
                (int)$actor->id,$post->getCmd('kind',''),$post->getInt('event_id',0),$read===1);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_INBOX_READ_SAVED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_INBOX_STALE'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=notifications');
    }
}
