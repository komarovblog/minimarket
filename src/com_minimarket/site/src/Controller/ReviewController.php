<?php
namespace MiniMarket\Component\Minimarket\Site\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Service\ReviewData;
use MiniMarket\Component\Minimarket\Site\Service\ReviewStorage;

final class ReviewController extends BaseController
{
    public function submit():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $id=$app->input->post->getInt('product_id',0);
        $redirect=$id>0?RouteHelper::product($id):RouteHelper::catalog();
        $params=ComponentHelper::getParams('com_minimarket');
        $user=$app->getIdentity();
        $registered=(int)$user->id>0 && empty($user->block);
        if((int)$params->get('reviews_enabled',0)!==1
            ||((string)$params->get('reviews_author_mode','registered')!=='all'&&!$registered)) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_UNAVAILABLE'),'warning');
            $this->setRedirect($redirect);return;
        }
        $session=$app->getSession();
        if((int)$session->get('com_minimarket.last_review_at',0)>time()-60) {
            $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_COOLDOWN'),'warning');
            $this->setRedirect($redirect);return;
        }
        if(trim((string)$app->input->post->get('website','','string'))!=='') {
            $this->setRedirect($redirect);return;
        }
        try {
            $post=$app->input->post;
            $data=ReviewData::normalize([
               'author_name'=>$post->get('author_name','','string'),
               'rating'=>$post->getInt('rating',0),
               'title'=>$post->get('title','','string'),
               'body'=>$post->get('body','','string')
            ]);
            // Guests always require moderation, even when moderator approval
            // has been disabled for verified, signed-in Joomla users.
            $state=$registered&&(int)$params->get('reviews_moderation',1)===0?1:0;
            ReviewStorage::create(Factory::getContainer()->get(DatabaseInterface::class),
                $id,$data,$registered?(int)$user->id:0,0,'site',$state);
            $session->set('com_minimarket.last_review_at',time());
            $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_SUCCESS'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_FAILED'),'warning');
        }
        $this->setRedirect($redirect.'#mm-product-reviews');
    }
}
