<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\WorkqueueReads;

final class WorkqueueController extends BaseController
{
    public function setRead():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $post=$app->input->post;
        $kind=$post->getCmd('kind','');
        $id=$post->getInt('id',0);
        $read=$post->getInt('read',-1);
        try{
            if(!in_array($read,[0,1],true))
                throw new \DomainException('COM_MINIMARKET_WORKQUEUE_STALE');
            WorkqueueReads::save(
                Factory::getContainer()->get(DatabaseInterface::class),
                (int)$app->getIdentity()->id,$kind,$id,
                $post->getString('signature',''),$read===1);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_WORKQUEUE_READ_SAVED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_WORKQUEUE_STALE'),'warning');
        }
        $return=$post->getCmd('return_view','workqueue');
        $return=in_array($return,['workqueue','notifications'],true)?$return:'workqueue';
        $this->setRedirect('index.php?option=com_minimarket&view='.$return);
    }
}
