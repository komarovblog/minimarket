<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\MarketingFlows;

final class MarketingflowController extends BaseController
{
    public function save():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $post=$app->input->post;$id=$post->getInt('id',0);
        $user=$app->getIdentity();
        if(!$user->authorise($id?'core.edit':'core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $redirect='index.php?option=com_minimarket&view=marketingflow'
            .($id?'&id='.$id:'&layout=edit');
        try{
            $steps=$post->get('steps',[],'array');
            $data=MarketingFlows::normalize([
                'title'=>$post->getString('title',''),
                'trigger_code'=>$post->getCmd('trigger_code',''),
                'stop_on_order'=>(string)$post->getInt('stop_on_order',1),
                'steps'=>$steps
            ]);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $id=MarketingFlows::save($db,$id,(int)$user->id,$data);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_FLOW_SAVED'));
            $this->setRedirect($post->getInt('save_close',0)
                ?'index.php?option=com_minimarket&view=marketingflows'
                :'index.php?option=com_minimarket&view=marketingflow&id='.$id);
        }catch(\Throwable $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_FLOW_INVALID'),'warning');
            $this->setRedirect($redirect);
        }
    }
}
