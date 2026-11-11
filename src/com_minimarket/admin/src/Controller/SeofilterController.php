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
    public function create():void { $this->save(); }

    public function save():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $post=$app->input->post;
        $id=$post->getInt('id',0);
        if(!$app->getIdentity()->authorise($id>0?'core.edit':'core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $redirect='index.php?option=com_minimarket&view=seofilter'.($id>0?'&id='.$id:'&layout=create');
        try {
            // The visual editor sends the same validated mmf query as catalog filters.
            $data=SeoFilterData::normalize([
                'category_id'=>$post->getInt('category_id',0),
                'alias'=>$post->get('alias','','string'),
                'title'=>$post->get('title','','string'),
                'heading'=>$post->get('heading','','string'),
                'introtext'=>$post->get('introtext','','raw'),
                'description'=>$post->get('description','','raw'),
                'meta_description'=>$post->get('meta_description','','string'),
                'image'=>$post->get('image','','string'),
                'image_alt'=>$post->get('image_alt','','string'),
                'og_title'=>$post->get('og_title','','string'),
                'og_description'=>$post->get('og_description','','string'),
                'og_image'=>$post->get('og_image','','string'),
                'meta_robots'=>$post->get('meta_robots','index,follow','string'),
                'filter_query'=>$post->get('filter_query','','string')
            ]);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $q=$db->getQuery(true)->select('id')->from($db->quoteName('#__minimarket_categories'))
                ->where('id='.(int)$data['category_id'])->where('state=1');
            $db->setQuery($q);
            if((int)$db->loadResult()!==(int)$data['category_id'])
                throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            if($id>0){
                $db->setQuery($db->getQuery(true)->select('id')
                    ->from($db->quoteName('#__minimarket_seo_filters'))->where('id='.(int)$id));
                if((int)$db->loadResult()!==$id)throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_seo_filters'));
                foreach($data as $column=>$value){
                    $q->set($db->quoteName($column).'='.($column==='category_id'?(int)$value:$db->quote((string)$value)));
                }
                $q->where('id='.(int)$id);
            }else{
                $columns=array_keys($data);
                $values=[];
                foreach($data as $column=>$value){
                    $values[]=$column==='category_id'?(string)(int)$value:$db->quote((string)$value);
                }
                $columns[]='state';$columns[]='created_at';
                $values[]='0';$values[]='UTC_TIMESTAMP()';
                $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_seo_filters'))
                    ->columns($db->quoteName($columns))->values(implode(',',$values));
            }
            $db->setQuery($q);$db->execute();
            if($id===0)$id=(int)$db->insertid();
            $app->enqueueMessage(Text::_('COM_MINIMARKET_SEOFILTER_CREATED'));
            $this->setRedirect($post->getInt('save_close',0)===1
                ?'index.php?option=com_minimarket&view=seofilters'
                :'index.php?option=com_minimarket&view=seofilter&id='.$id);
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_SEOFILTER_INVALID'),'warning');
            $this->setRedirect($redirect);
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
