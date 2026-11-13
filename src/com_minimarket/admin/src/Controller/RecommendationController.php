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
  if($source<1||!in_array($similarMode,['auto','manual','hybrid'],true)
   ||!in_array($togetherMode,['auto','manual','hybrid'],true))
    throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
  $parse=static function(string $input)use($source):array {
   $input=trim($input);
   if($input==='')return [];
   if(strlen($input)>340||!preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*){0,29}$/D',$input))
    throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
   $ids=array_map('intval',explode(',',$input));
   if(in_array($source,$ids,true)||count($ids)!==count(array_unique($ids))
      ||in_array(0,$ids,true)||count($ids)>30)
    throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
   return $ids;
  };
  return ['modes'=>['similar'=>$similarMode,'together'=>$togetherMode],
   'similar'=>$parse($similarIds),'together'=>$parse($togetherIds)];
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
    $post->getCmd('similar_mode','auto'),$post->getCmd('together_mode','auto'),
    $post->getString('similar_ids',''),$post->getString('together_ids',''),$id);
   $db=Factory::getContainer()->get(DatabaseInterface::class);
   $db->transactionStart();
   try{
    // Prevent a concurrent product deletion while links are replaced.
    $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_products')
       .' WHERE id='.$id.' FOR UPDATE');
    if((int)$db->loadResult()!==$id)
     throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
    $ids=array_values(array_unique(array_merge($data['similar'],$data['together'])));
    if($ids){
     $db->setQuery('SELECT COUNT(DISTINCT id) FROM '
      .$db->quoteName('#__minimarket_products').' WHERE id IN ('.implode(',',$ids).')');
     if((int)$db->loadResult()!==count($ids))
      throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
    }
    $db->setQuery('INSERT INTO '.$db->quoteName('#__minimarket_recommendation_settings')
     .' (product_id,similar_mode,together_mode,updated_at) VALUES ('.$id.','
     .$db->quote($data['modes']['similar']).','.$db->quote($data['modes']['together'])
     .',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE similar_mode=VALUES(similar_mode),'
     .' together_mode=VALUES(together_mode),updated_at=VALUES(updated_at)');
    $db->execute();
    $db->setQuery('DELETE FROM '.$db->quoteName('#__minimarket_recommendation_links')
      .' WHERE source_product_id='.$id);
    $db->execute();
    foreach(['similar','together'] as $kind){
     foreach($data[$kind] as $position=>$target){
      $db->setQuery('INSERT INTO '.$db->quoteName('#__minimarket_recommendation_links')
        .' (source_product_id,target_product_id,kind,sort_order) VALUES ('.$id.','
        .(int)$target.','.$db->quote($kind).','.(int)$position.')');
      $db->execute();
     }
    }
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
