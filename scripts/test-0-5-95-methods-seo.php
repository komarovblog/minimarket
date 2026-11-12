<?php
/** MiniMarket 0.5.95: meaningful method, SEO and display regression checks. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace MiniMarket\Smoke95 {
 define('_JEXEC',1);
 require __DIR__.'/../src/com_minimarket/admin/src/Service/CategoryAttributes.php';
 require __DIR__.'/../src/com_minimarket/admin/src/Service/CategoryOrderOptions.php';
 require __DIR__.'/../src/com_minimarket/admin/src/Service/SeoCategoryChoices.php';
 require __DIR__.'/../src/com_minimarket/admin/src/Service/MethodSettings.php';
 require __DIR__.'/../src/com_minimarket/site/src/Service/CheckoutDelivery.php';
 require __DIR__.'/../src/com_minimarket/site/src/Service/CheckoutSettings.php';
 use Joomla\Database\DatabaseInterface;
 use MiniMarket\Component\Minimarket\Administrator\Service\MethodSettings as Methods;
 use MiniMarket\Component\Minimarket\Administrator\Service\SeoCategoryChoices as Seo;
 use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;
 use MiniMarket\Component\Minimarket\Site\Service\CheckoutSettings;
 $n=0;
 function check95(bool $ok,string $why):void{
  global $n;++$n;if(!$ok)throw new \RuntimeException('MM95_FAIL: '.$why);
 }
 $pickup=Methods::normalize('delivery','pickup',[
  'enabled'=>'1','address'=>'Main street','fee'=>'159.50',
  'schedule'=>'Monday to Friday 9-18','map_url'=>'https://maps.example/location'
 ]);
 check95($pickup['delivery_pickup_fee_cents']===15950
  && $pickup['delivery_pickup_schedule']!=='','pickup cost and schedule');
 foreach(['-1','1.234','9999999999','hello'] as $bad){
  try{Methods::normalize('delivery','pickup',['enabled'=>'1','fee'=>$bad]);check95(false,'bad fee accepted');}
  catch(\DomainException){check95(true,'bad fee rejected');}
 }
 try{Methods::normalize('delivery','pickup',['enabled'=>'1','map_url'=>'javascript:alert(1)']);check95(false,'bad URL');}
 catch(\DomainException){check95(true,'javascript link rejected');}
 check95(Methods::normalize('registration','email',['primary'=>'1','enabled'=>'0'])
    ===['checkout_verification_method'=>'email'],'primary email');
 final class Params {
  public function __construct(public array $values){}
  public function get(string $name,mixed $fallback=null):mixed {return $this->values[$name]??$fallback;}
 }
 $params=new Params(['delivery_pickup_enabled'=>1,'delivery_pickup_fee_cents'=>15950,
  'delivery_pickup_address'=>'Main street','payment_on_receipt_enabled'=>1]);
 check95(CheckoutDelivery::methods($params)['pickup']['fee_cents']===15950,'pickup has fee');
 check95(CheckoutDelivery::quote($params,['delivery_method'=>'pickup','payment_method'=>'on_receipt'])['fee_cents']===15950,
  'server pickup quote includes fee');
 try{CheckoutSettings::method(new Params(['checkout_auth_mode'=>'guests','checkout_verification_method'=>'']));
  check95(false,'email confirmation not configured');}
 catch(\DomainException){check95(true,'email needs selected method');}
 final class Q {
  public string $from='';
  public function select(mixed $x):self{return $this;}
  public function from(mixed $x):self{$this->from=(string)$x;return $this;}
  public function join(mixed $x,mixed $y):self{return $this;}
  public function where(mixed $x):self{return $this;}
  public function order(mixed $x):self{return $this;}
 }
 final class Db implements DatabaseInterface {
  private ?Q $q=null;
  public function getQuery(bool $new=true):Q{return new Q;}
  public function quoteName(string $name,?string $alias=null):string{return $name.($alias===null?'':' AS '.$alias);}
  public function quote(string $v):string{return "'".$v."'";}
  public function setQuery(mixed $q,int $offset=0,int $limit=0):void{$this->q=$q;}
  public function loadAssocList():array {
   return match($this->q->from){
    '#__minimarket_categories'=>[
      ['id'=>1,'parent_id'=>0,'state'=>1],['id'=>2,'parent_id'=>1,'state'=>1],
      ['id'=>3,'parent_id'=>0,'state'=>0]],
    '#__minimarket_category_attributes'=>[
      ['category_id'=>1,'attribute_id'=>10,'enabled'=>1,'required'=>0,'show_in_list'=>1]],
    '#__minimarket_category_order_options'=>[
      ['category_id'=>2,'option_id'=>20,'mode'=>1,'show_in_list'=>1]],
    '#__minimarket_attributes AS a'=>[
      ['property_id'=>10,'property_title'=>'Color','value_id'=>101,'value_title'=>'Blue']],
    '#__minimarket_order_options AS o'=>[
      ['property_id'=>20,'property_title'=>'Size','value_id'=>201,'value_title'=>'Large']],
    default=>throw new \RuntimeException('Unexpected table '.$this->q->from)
   };
  }
 }
 $db=new Db;
 $groups=Seo::groups($db,2);
 check95(count($groups)===2 && $groups[0]['key']==='a10' && $groups[1]['key']==='s20',
  'SEO choices only from category ancestry');
 check95(Seo::groups($db,3)===[],'SEO cannot use unpublished category');
 Seo::validate($db,2,json_encode(['a10'=>['v:101'],'s20'=>['v:201'],'price'=>['min'=>'10']]));
 check95(true,'SEO accepts allocated tokens');
 foreach([['a11'=>['v:101']],['a10'=>['v:999']],['s20'=>['v:999']]] as $bad){
  try{Seo::validate($db,2,json_encode($bad));check95(false,'invalid token accepted');}
  catch(\DomainException){check95(true,'invalid token refused');}
 }
 $root=dirname(__DIR__).'/src/com_minimarket/';
 $cfg=simplexml_load_file($root.'admin/config.xml');
 check95($cfg!==false,'config XML');
 foreach(['price','sku','stock'] as $name){
  check95(count($cfg->xpath('//fieldset[@name="basic"]/field[@name="system_'.$name.'_display_location"]'))===1,
   'independent system display for '.$name);
 }
 check95(count($cfg->xpath('//field[@name="system_display_location"]'))===0,
  'no shared system location selector');
 $systemFields=$cfg->xpath('//fieldset[@name="basic"]/field');
 $names=array_map(static fn($node)=>(string)$node['name'],$systemFields);
 foreach(['price'=>['filter_price_mode','system_price_display_location'],
          'sku'=>['filter_sku_mode','system_sku_display_location'],
          'stock'=>['filter_stock_enabled','system_stock_display_location']] as $system=>$pair){
  $first=array_search($pair[0],$names,true);
  $second=array_search($pair[1],$names,true);
  check95($first!==false && $second===$first+1
    &&!in_array('mm_'.$system.'_section',$names,true),
    'system attribute '.$system.' has exactly two adjacent controls, no duplicate heading');
 }
 $option=simplexml_load_file($root.'admin/forms/orderoption.xml');
 check95((string)$option->xpath("//field[@name='display_location']")[0]['default']==='product',
   'new order options default to product page, not a global system setting');
 $attributes=simplexml_load_file($root.'admin/forms/attribute.xml');
 check95((string)$attributes->xpath("//field[@name='display_location']")[0]['default']==='product',
  'custom attribute default product only');
 $view=(string)file_get_contents($root.'admin/tmpl/seofilter/create.php');
 $controller=(string)file_get_contents($root.'admin/src/Controller/SeofilterController.php');
 check95(strpos($view,"'main'")<strpos($view,"'filters'")
  && strpos($view,"'filters'")<strpos($view,"'meta'")
  && strpos($view,"'meta'")<strpos($view,"'publication'"),
  'SEO general filters SEO publication order');
 check95(str_contains($view,'data-mm-seo-choices-url')
  && str_contains($controller,'SeoCategoryChoices::validate'),
  'SEO category choices are validated server-side');
 $mig=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.95.sql');
 check95(str_contains($mig,'ADD COLUMN name'),'SEO name migrated');
 $pluginList=(string)file_get_contents($root.'admin/src/Service/MethodListRows.php');
 $pluginService=(string)file_get_contents($root.'admin/src/Service/PluginMethodSettings.php');
 $pluginController=(string)file_get_contents($root.'admin/src/Controller/PluginmethodController.php');
 $pluginTpl=(string)file_get_contents($root.'admin/tmpl/shared/plugin-method.php');
 check95(str_contains($pluginList,"'plugin_info_url'")
   && str_contains($pluginList,'&method=plugin_'),
   'installed plugin enters MiniMarket card while retaining Joomla info link');
 check95(str_contains($pluginService,'FormFactoryInterface::class')
   && str_contains($pluginService,'$form->filter(')
   && str_contains($pluginService,'$form->validate(')
   && str_contains($pluginService,'transactionStart()')
   && str_contains($pluginService,'transactionRollback()'),
   'installed plugin form uses Joomla validation and atomic saving');
 check95(str_contains($pluginController,"Session::checkToken('post')")
   && str_contains($pluginController,"'core.edit','com_plugins'"),
   'plugin settings save guarded by Joomla ACL and CSRF');
 check95(str_contains($pluginTpl,'$this->pluginForm->renderFieldset')
   && str_contains($pluginTpl,'COM_MINIMARKET_METHOD_PLUGIN_INFO'),
   'plugin card renders own manifest settings and separate info link');
 check95(str_contains($pluginTpl,'name="enabled"')
   && str_contains($pluginController,"'core.edit.state','com_plugins'")
   && str_contains($pluginService,"'enabled'")
   && str_contains($pluginService,'$db->transactionCommit()'),
   'plugin publication can be edited inside method card with Joomla ACL');
 $pickupTpl=(string)file_get_contents($root.'admin/tmpl/deliverymethods/default.php');
 check95(str_contains($pickupTpl,'mm-delivery-method')
   && str_contains($pickupTpl,'deliveryzone/edit.php')
   && str_contains($pickupTpl,'mm-pickup-fee')
   && str_contains($pickupTpl,'mm-pickup-schedule')
   && str_contains($pickupTpl,'mm-pickup-map'),
   'courier zones tab and configured pickup fields are in method details');
 $regTpl=(string)file_get_contents($root.'admin/tmpl/registrationmethods/default.php');
 check95(str_contains($regTpl,'COM_MINIMARKET_REGISTRATION_PRIMARY_CHECKOUT')
   && str_contains($regTpl,'name="primary"'),
   'registration card has primary checkout confirmation selector');
 echo 'MM95_METHODS_SEO_PASS: '.$n." checks\n";
}