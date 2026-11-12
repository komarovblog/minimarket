<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\SeoFilterData;
use MiniMarket\Component\Minimarket\Administrator\Service\SeoCategoryChoices;
use MiniMarket\Component\Minimarket\Administrator\Service\SeoLandingRules;
use MiniMarket\Component\Minimarket\Administrator\Service\SeoLandingResolver;
final class SeofilterController extends BaseController
{
    public function create():void { $this->save(); }

    /** Only published category-bound choices. No store-wide dictionary leak. */
    public function choices():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='GET'||!Session::checkToken('get'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $user=$app->getIdentity();
        if(!$user->authorise('core.edit','com_minimarket')
            && !$user->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $category=$app->input->getInt('category_id',0);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $groups=SeoCategoryChoices::groups($db,$category);
        $app->setHeader('Content-Type','application/json; charset=UTF-8',true);
        $app->setHeader('Cache-Control','no-store',true);
        $app->sendHeaders();
        echo json_encode(['groups'=>$groups],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
        $app->close();
    }

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
                'name'=>$post->get('name','','string'),
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
                'filter_query'=>$post->get('filter_query','','string'),
                'conditions_json'=>$post->get('conditions_json','','raw')
            ]);
            if($app->getIdentity()->authorise('core.edit.state','com_minimarket')){
                $postedState=$post->getInt('state',-1);
                if($postedState!==-1){
                    if(!in_array($postedState,[0,1],true))
                        throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                    $data['state']=$postedState;
                }
            }
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $modern=trim($post->get('conditions_json','','raw'))!=='';
            $rules=$modern ? SeoLandingRules::normalize(
                json_decode((string)$data['conditions_json'],true)) : [];
            $db->transactionStart();
            try {
                // Serialise saves/publication within a category. Prevent two
                // editors from creating equal-rank overlapping pages.
                $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_categories')
                    .' WHERE id='.(int)$data['category_id'].' AND state=1 FOR UPDATE');
                if((int)$db->loadResult()!==(int)$data['category_id'])
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                if($modern)
                    SeoCategoryChoices::validateConditions($db,(int)$data['category_id'],$rules);
                else
                    SeoCategoryChoices::validate($db,(int)$data['category_id'],
                        (string)$data['filters_json']);
                $existing=null;
                if($id>0){
                    $q=$db->getQuery(true)->select(['id','state'])
                        ->from($db->quoteName('#__minimarket_seo_filters'))
                        ->where('id='.(int)$id);
                    $db->setQuery($q);
                    $existing=$db->loadAssoc();
                    if(!$existing)throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                }
                $publish=(int)($data['state']??($existing['state']??0))===1;
                if($publish && $modern)
                    SeoLandingResolver::assertUnambiguous(
                        $db,(int)$data['category_id'],$rules,$id);
                if($id>0){
                    $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_seo_filters'));
                    foreach($data as $column=>$value){
                        $q->set($db->quoteName($column).'='.
                            ($column==='category_id'?(int)$value:$db->quote((string)$value)));
                    }
                    $q->where('id='.(int)$id);
                }else{
                    $columns=array_keys($data);
                    $values=[];
                    foreach($data as $column=>$value){
                        $values[]=$column==='category_id'?(string)(int)$value:$db->quote((string)$value);
                    }
                    if(!array_key_exists('state',$data)){
                        $columns[]='state';$values[]='0';
                    }
                    $columns[]='created_at';$values[]='UTC_TIMESTAMP()';
                    $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_seo_filters'))
                        ->columns($db->quoteName($columns))->values(implode(',',$values));
                }
                $db->setQuery($q);$db->execute();
                if($id===0)$id=(int)$db->insertid();
                $db->transactionCommit();
            } catch (\Throwable $e) {
                $db->transactionRollback();
                throw $e;
            }
            $app->enqueueMessage(Text::_('COM_MINIMARKET_SEOFILTER_CREATED'));
            $this->setRedirect($post->getInt('save_close',0)===1
                ?'index.php?option=com_minimarket&view=seofilters'
                :'index.php?option=com_minimarket&view=seofilter&id='.$id);
        }catch(\DomainException $e){
            $key=$e->getMessage()==='COM_MINIMARKET_SEOFILTER_CONFLICT'
                ?'COM_MINIMARKET_SEOFILTER_CONFLICT'
                :($e->getMessage()==='COM_MINIMARKET_SEOFILTER_NEEDS_EXACT'
                    ?'COM_MINIMARKET_SEOFILTER_NEEDS_EXACT'
                    :'COM_MINIMARKET_SEOFILTER_INVALID');
            $app->enqueueMessage(Text::_($key),'warning');
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
        if($state===1){
            $db->setQuery('SELECT category_id,conditions_json FROM '.
                $db->quoteName('#__minimarket_seo_filters').' WHERE id='.(int)$id);
            $page=$db->loadAssoc();
            if(!$page)throw new \RuntimeException(Text::_('COM_MINIMARKET_SEOFILTER_INVALID'),404);
            $category=(int)$page['category_id'];
            $db->transactionStart();
            try {
                $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_categories')
                    .' WHERE id='.$category.' AND state=1 FOR UPDATE');
                if((int)$db->loadResult()!==$category)
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $conditions=json_decode((string)$page['conditions_json'],true);
                if(is_array($conditions)&&$conditions){
                    $rules=SeoLandingRules::normalize($conditions);
                    SeoCategoryChoices::validateConditions($db,$category,$rules);
                    SeoLandingResolver::assertUnambiguous($db,$category,$rules,$id);
                }
                $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_seo_filters'))
                    ->set($db->quoteName('state').'=1')
                    ->where($db->quoteName('id').'='.(int)$id);
                $db->setQuery($q);$db->execute();
                $db->transactionCommit();
            }catch(\DomainException $e){
                $db->transactionRollback();
                $app->enqueueMessage(Text::_($e->getMessage()),'warning');
                $this->setRedirect('index.php?option=com_minimarket&view=seofilters');
                return;
            }catch(\Throwable $e){
                $db->transactionRollback();throw $e;
            }
        }else{
            $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_seo_filters'))
                ->set($db->quoteName('state').'=0')
                ->where($db->quoteName('id').'='.(int)$id);
            $db->setQuery($q);$db->execute();
        }
        $this->setRedirect('index.php?option=com_minimarket&view=seofilters');
    }
}
