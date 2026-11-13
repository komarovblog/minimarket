<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
final class RecommendationController extends BaseController
{
 public static function normalize(string $similarMode,string $togetherMode,string $similarIds,string $togetherIds,int $source):array
 {
  return \MiniMarket\Component\Minimarket\Administrator\Service\RecommendationSettings::normalize(
   $similarMode,$togetherMode,$similarIds,$togetherIds,$source);
 }

 public function save():void
 {
  $app=Factory::getApplication();
  if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
   throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
  if(!$app->getIdentity()->authorise('core.edit','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $post=$app->input->post;$id=$post->getInt('product_id',0);
  $url='index.php?option=com_minimarket&view=recommendation&product_id='.$id;
  try {
   $data=self::normalize(
    $post->getCmd('similar_mode','inherit'),$post->getCmd('together_mode','inherit'),
    $post->getString('similar_ids',''),$post->getString('together_ids',''),$id);
   $db=Factory::getContainer()->get(DatabaseInterface::class);
   $db->transactionStart();
   try{
    \MiniMarket\Component\Minimarket\Administrator\Service\RecommendationSettings::save(
     $db,$id,$data);
    $db->transactionCommit();
   }catch(\Throwable $e){$db->transactionRollback();throw $e;}
   $app->enqueueMessage(Text::_('COM_MINIMARKET_RECOMMENDATIONS_SAVED'));
   $this->setRedirect($post->getInt('save_close',0)
    ?'index.php?option=com_minimarket&view=recommendations':$url);
  }catch(\Throwable $e){
   $app->enqueueMessage(Text::_('COM_MINIMARKET_RECOMMENDATIONS_INVALID'),'warning');
   $this->setRedirect($url);
  }
 }
}
