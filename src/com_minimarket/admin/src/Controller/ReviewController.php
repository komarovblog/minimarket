<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\ReviewData;
use MiniMarket\Component\Minimarket\Site\Service\ReviewStorage;

final class ReviewController extends BaseController
{
    public function create():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $user=$app->getIdentity();
        if(!$user->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        require_once JPATH_SITE.'/components/com_minimarket/src/Service/ReviewData.php';
        require_once JPATH_SITE.'/components/com_minimarket/src/Service/ReviewStorage.php';
        try {
            $post=$app->input->post;
            $fields=ReviewData::normalize([
                'author_name'=>$post->get('author_name','','string'),
                'title'=>$post->get('title','','string'),
                'rating'=>$post->getInt('rating',0),
                'body'=>$post->get('body','','string'),
                'photo_path'=>$post->get('photo_path','','string'),
                'video_url'=>$post->get('video_url','','string')
            ],true);
            $id=ReviewStorage::create(Factory::getContainer()->get(DatabaseInterface::class),
                $post->getInt('product_id',0),$fields,0,(int)$user->id,'manual',
                $user->authorise('core.edit.state','com_minimarket')?1:0);
            $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_CREATE_SUCCESS').' #'.$id);
            $this->setRedirect('index.php?option=com_minimarket&view=reviews&filter_status=all');
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_VALIDATION_ERROR'),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view=review&layout=create');
        }
    }
    /** Edit an existing review without changing the original product or author ID. */
    public function update():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $u=$app->getIdentity();
        if(!$u->authorise('core.edit','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $post=$app->input->post;
        $id=$post->getInt('id',0);
        if($id<1)throw new \RuntimeException(Text::_('COM_MINIMARKET_REVIEW_INVALID'),400);
        require_once JPATH_SITE.'/components/com_minimarket/src/Service/ReviewData.php';
        try{
            $data=ReviewData::normalize([
                'author_name'=>$post->getString('author_name',''),
                'title'=>$post->getString('title',''),
                'rating'=>$post->getInt('rating',0),
                'body'=>$post->getString('body',''),
                'photo_path'=>$post->getString('photo_path',''),
                'video_url'=>$post->getString('video_url','')
            ],true);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->getQuery(true)->select('id')
                ->from($db->quoteName('#__minimarket_reviews'))->where('id='.$id));
            if((int)$db->loadResult()!==$id)throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_reviews'));
            foreach($data as $name=>$value)
                $q->set($db->quoteName($name).'='.$db->quote((string)$value));
            if($u->authorise('core.edit.state','com_minimarket')){
                $state=$post->getInt('state',-1);
                if(!in_array($state,[0,1,2],true))
                    throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
                $q->set('state='.(int)$state);
            }
            $q->set('updated_at=UTC_TIMESTAMP()')->where('id='.$id);
            $db->setQuery($q);$db->execute();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_STATUS_SAVED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_VALIDATION_ERROR'),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=review&id='.$id);
    }

    public function moderate():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        $state=$app->input->post->getInt('state',-1);
        if($id<1 || !in_array($state,[0,1,2],true))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_REVIEW_VALIDATION_ERROR'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_reviews'))
            ->set($db->quoteName('state').'='.(int)$state)
            ->set($db->quoteName('updated_at').'='.$db->quote(gmdate('Y-m-d H:i:s')))
            ->where($db->quoteName('id').'='.(int)$id);
        $db->setQuery($q);$db->execute();
        $app->enqueueMessage(Text::_('COM_MINIMARKET_REVIEW_STATUS_SAVED'));
        $this->setRedirect('index.php?option=com_minimarket&view=reviews&filter_status=all');
    }
}
