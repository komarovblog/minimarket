<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\PickupPoints;
final class PickuppointController extends BaseController
{
    public function save():void
    {
        $app=Factory::getApplication();
        if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $post=$app->input->post;$id=$post->getInt('id',0);
        if(!$app->getIdentity()->authorise($id>0?'core.edit':'core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        try {
            $data=PickupPoints::validate([
                'title'=>$post->getString('title',''),'address'=>$post->getString('address',''),
                'schedule'=>$post->getString('schedule',''),
                'map_url'=>$post->getString('map_url',''),
                'fee'=>$post->getString('fee','0'),'state'=>$post->getString('state','1')
            ]);
            $db->transactionStart();
            try{
                $table=$db->quoteName('#__minimarket_pickup_points');
                $sets=[];
                foreach($data as $key=>$value)$sets[]=$db->quoteName($key).'='
                    .(is_int($value)?(string)$value:$db->quote($value));
                if($id>0){
                    $db->setQuery('SELECT id FROM '.$table.' WHERE id='.$id.' FOR UPDATE');
                    if((int)$db->loadResult()!==$id)
                        throw new \DomainException('COM_MINIMARKET_PICKUP_POINT_INVALID');
                    $db->setQuery('UPDATE '.$table.' SET '.implode(',',$sets)
                        .',updated_at=UTC_TIMESTAMP() WHERE id='.$id);
                }else{
                    $sets[]='created_at=UTC_TIMESTAMP()';
                    $db->setQuery('INSERT INTO '.$table.' SET '.implode(',',$sets));
                }
                $db->execute();
                $db->transactionCommit();
            }catch(\Throwable $e){$db->transactionRollback();throw $e;}
            $app->enqueueMessage(Text::_('COM_MINIMARKET_PICKUP_POINT_SAVED'));
        }catch(\DomainException $e){
            $app->enqueueMessage(Text::_($e->getMessage()),'warning');
        }
        $this->setRedirect('index.php?option=com_minimarket&view=deliverymethods&method=pickup&tab=points');
    }
}
