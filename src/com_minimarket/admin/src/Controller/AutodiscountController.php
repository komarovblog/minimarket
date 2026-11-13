<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\AutoDiscount;
final class AutodiscountController extends BaseController {
 public function save():void {
  $app=Factory::getApplication();
  if($app->input->getMethod()!=='POST'||!Session::checkToken('post'))
   throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
  $post=$app->input->post;$id=$post->getInt('id',0);
  if(!$app->getIdentity()->authorise($id?'core.edit':'core.create','com_minimarket'))
   throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
  $redirect='index.php?option=com_minimarket&view=autodiscount'.($id?'&id='.$id:'&layout=create');
  try{
   $scope=$post->getCmd('applies_to','all');
   $field=match($scope){'product'=>'product_ids','category'=>'category_ids','brand'=>'brand_ids',default=>''};
   $raw=['title'=>$post->getString('title',''),
    'discount_type'=>$post->getCmd('discount_type','percent'),
    'amount'=>$post->getString('amount',''),
    'percent'=>$post->getString('percent',''),
    'applies_to'=>$scope,'target_ids'=>$field?$post->getString($field,''):'',
    'starts_at'=>$post->getString('starts_at',''),
    'ends_at'=>$post->getString('ends_at','')];
   $user=$app->getIdentity();$db=Factory::getContainer()->get(DatabaseInterface::class);
   $db->transactionStart();
   try{
    $current=null;
    if($id){
     $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_auto_discounts')
      .' WHERE id='.$id.' FOR UPDATE');
     $current=$db->loadAssoc();
     if(!$current)throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
    }
    $raw['state']=$user->authorise('core.edit.state','com_minimarket')
     ?$post->getInt('state',0):(int)($current['state']??0);
    $data=AutoDiscount::normalize($raw);
    if($scope!=='all'){
     $ids=json_decode((string)$data['target_ids_json'],true,512,JSON_THROW_ON_ERROR);
     $table=match($scope){'product'=>'#__minimarket_products',
      'category'=>'#__minimarket_categories','brand'=>'#__minimarket_brands'};
     $q=$db->getQuery(true)->select('COUNT(DISTINCT id)')
      ->from($db->quoteName($table))->where('id IN ('.implode(',',array_map('intval',$ids)).')');
     $db->setQuery($q);
     if((int)$db->loadResult()!==count($ids))
      throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
    }
    $quote=static fn($v)=>$v===null?'NULL':$db->quote((string)$v);
    if($id){
     $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_auto_discounts'));
     foreach($data as $field=>$value)$q->set($db->quoteName($field).'='.$quote($value));
     $q->set('updated_at=UTC_TIMESTAMP()')->where('id='.$id);
    }else{
     $fields=array_keys($data);$fields[]='created_by';$fields[]='created_at';
     $vals=array_map($quote,array_values($data));
     $vals[]=(string)(int)$user->id;$vals[]='UTC_TIMESTAMP()';
     $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_auto_discounts'))
      ->columns($db->quoteName($fields))->values(implode(',',$vals));
    }
    $db->setQuery($q);$db->execute();
    if(!$id)$id=(int)$db->insertid();
    $db->transactionCommit();
   }catch(\Throwable $e){$db->transactionRollback();throw $e;}
   $app->enqueueMessage(Text::_('COM_MINIMARKET_AUTO_DISCOUNT_SAVED'));
   $this->setRedirect($post->getInt('save_close',0)
    ?'index.php?option=com_minimarket&view=autodiscounts'
    :'index.php?option=com_minimarket&view=autodiscount&id='.$id);
  }catch(\Throwable $e){
   $app->enqueueMessage(Text::_('COM_MINIMARKET_AUTO_DISCOUNT_INVALID'),'warning');
   $this->setRedirect($redirect);
  }
 }
}
