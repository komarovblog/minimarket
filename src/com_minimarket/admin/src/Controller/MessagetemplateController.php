<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\MessageTemplates;

final class MessagetemplateController extends BaseController
{
    public function save():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $post=$app->input->post;$id=$post->getInt('id',0);
        $identity=$app->getIdentity();
        if(!$identity->authorise($id?'core.edit':'core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $back='index.php?option=com_minimarket&view=messagetemplate'.($id?'&id='.$id:'&layout=edit');
        try{
            $raw=['title'=>$post->getString('title',''),
                'audience'=>$post->getCmd('audience','customer'),
                'event_code'=>$post->getCmd('event_code',''),
                'subject'=>$post->getString('subject',''),
                'body'=>$post->getString('body',''),
                'state'=>$identity->authorise('core.edit.state','com_minimarket')
                    ?(string)$post->getInt('state',0):'0'];
            $data=MessageTemplates::normalize($raw);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $db->transactionStart();
            try{
                $table=$db->quoteName('#__minimarket_message_templates');
                if($id){
                    $db->setQuery('SELECT state FROM '.$table.' WHERE id='.$id.' FOR UPDATE');
                    $old=$db->loadResult();
                    if($old===null)throw new \DomainException('COM_MINIMARKET_MESSAGE_TEMPLATE_INVALID');
                    if(!$identity->authorise('core.edit.state','com_minimarket'))
                        $data['state']=(int)$old;
                    $pairs=[];
                    foreach($data as $k=>$v)$pairs[]=$db->quoteName($k).'='.$db->quote((string)$v);
                    $db->setQuery('UPDATE '.$table.' SET '.implode(',',$pairs)
                        .',updated_at=UTC_TIMESTAMP() WHERE id='.$id);
                }else{
                    $keys=array_keys($data);
                    $values=array_map(static fn($v)=>$db->quote((string)$v),array_values($data));
                    $db->setQuery('INSERT INTO '.$table
                        .' ('.implode(',',$db->quoteName(array_merge($keys,['created_by','created_at']))).')'
                        .' VALUES('.implode(',',array_merge($values,[(string)(int)$identity->id,'UTC_TIMESTAMP()'])).')');
                }
                $db->execute();
                if(!$id)$id=(int)$db->insertid();
                $db->transactionCommit();
            }catch(\Throwable $e){$db->transactionRollback();throw $e;}
            $app->enqueueMessage(Text::_('COM_MINIMARKET_MESSAGE_TEMPLATE_SAVED'));
            $this->setRedirect($post->getInt('save_close',0)
                ?'index.php?option=com_minimarket&view=messagetemplates'
                :'index.php?option=com_minimarket&view=messagetemplate&id='.$id);
        }catch(\Throwable $e){
            $app->enqueueMessage(Text::_('COM_MINIMARKET_MESSAGE_TEMPLATE_INVALID'),'warning');
            $this->setRedirect($back);
        }
    }
}
