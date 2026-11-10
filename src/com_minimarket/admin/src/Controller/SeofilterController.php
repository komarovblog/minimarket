<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\SeoFilterData;
final class SeofilterController extends BaseController
{
    public function create():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $post=$app->input->post;
        try{
            $data=SeoFilterData::normalize([
                'category_id'=>$post->getInt('category_id',0),
                'alias'=>$post->get('alias','','string'),
                'title'=>$post->get('title','','string'),
                'heading'=>$post->get('heading','','string'),
                'introtext'=>$post->get('introtext','','string'),
                'description'=>$post->get('description','','string'),
                'meta_description'=>$post->get('meta_description','','string'),
                'meta_robots'=>$post->get('meta_robots','index,follow','string'),
                'filter_query'=>$post->get('filter_query','','string')
            ]);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $q=$db->getQuery(true)->select('id')->from($db->quoteName('#__minimarket_categories'))
                ->where('id='.(int)$data['category_id'])->where('state=1');
            $db->setQuery($q);
            if((int)$db->loadResult()!==(int)$data['category_id'])
                throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            $columns=['category_id','alias','title','heading','introtext','description',
                'meta_description','meta_robots','filters_json','state','created_at'];
            $values=[(string)$data['category_id']];
            foreach(['alias','title','heading','introtext','description','meta_description','meta_robots','filters_json'] as $key){
                $values[]=$db->quote($data[$key]);
            }
            $values[]='0';$values[]=$db->quote(gmdate('Y-m-d H:i:s'));
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_seo_filters'))
                ->columns($db->quoteName($columns))->values(implode(',',$values));
            $db->setQuery($q);$db->execute();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_SEOFILTER_CREATED'));
            $this->setRedirect('index.php?option=com_minimarket&view=seofilters');
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_SEOFILTER_INVALID'),'warning');
            $this->setRedirect('index.php?option=com_minimarket&view=seofilter&layout=create');
        }
    }
    public function state():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$app->getIdentity()->authorise('core.edit.state','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->post->getInt('id',0);
        $state=$app->input->post->getInt('state',-1);
        if($id<1||!in_array($state,[0,1],true))
            throw new \RuntimeException(Text::_('COM_MINIMARKET_SEOFILTER_INVALID'),400);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_seo_filters'))
            ->set($db->quoteName('state').'='.(int)$state)
            ->where($db->quoteName('id').'='.(int)$id);
        $db->setQuery($q);$db->execute();
        $this->setRedirect('index.php?option=com_minimarket&view=seofilters');
    }
}
