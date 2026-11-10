<?php
/** 0.5.84: only safe entity changes; no fake external integrations. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$check=static function(bool $ok,string $why):void{
 if(!$ok)throw new RuntimeException('MM84_FEATURE_FAIL '.$why);
};
require $root.'site/src/Service/CheckoutDelivery.php';
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;
$config=new class {
 private array $options=['delivery_courier_enabled'=>1,
   'delivery_rate_source'=>'directory',
   'delivery_city_rates'=>"Москва|500|5000\nТверь|250"];
 public function get(string $key,mixed $default=null):mixed{
  return $this->options[$key]??$default;
 }
};
$rates=CheckoutDelivery::rates($config);
$check($rates['москва']['fee_cents']===50000 &&
       $rates['москва']['free_from_cents']===500000,'free shipping threshold');
$check($rates['тверь']['free_from_cents']===0,'old two-column price compatibility');
$input=['delivery_method'=>'courier','delivery_city'=>'Москва',
 'delivery_address'=>'улица Пушкина, дом 8','payment_method'=>'on_receipt','fee_cents'=>1];
$check(CheckoutDelivery::quote($config,$input,499999)['fee_cents']===50000,
 'server quote below free threshold');
$check(CheckoutDelivery::quote($config,$input,500000)['fee_cents']===0,
 'server quote above free threshold');
$check(CheckoutDelivery::quote($config,
 ['delivery_method'=>'pickup','payment_method'=>'on_receipt'],500000)['fee_cents']===0,
 'pickup always free');
$read=static fn(string $p):string=>(string)file_get_contents($root.$p);
$xml=simplexml_load_file($root.'minimarket.xml');
$check($xml!==false && (string)$xml->version==='0.5.84','manifest version');
$cfg=simplexml_load_file($root.'admin/config.xml');
$check($cfg!==false && count($cfg->xpath('//fieldset[@name="checkout"]'))===1 &&
 count($cfg->xpath('//fieldset[@name="delivery"]'))===0 &&
 count($cfg->xpath('//fieldset[@name="payment"]'))===0,'single checkout tab');
foreach(['checkout_buyer_note','checkout_delivery_note','checkout_payment_note'] as $field){
 $check(count($cfg->xpath('//fieldset[@name="checkout"]/field[@name="'.$field.'" and @type="note" and @class="alert alert-info"]'))===1,
  'native, noncollapsible informative subsection '.$field);
}
$manifest=(string)$xml->administration->submenu->asXML();
foreach(['view=delivery','view=integrations','view=promocodes'] as $target)
 $check(str_contains($manifest,$target),'sidebar '.$target);
$orders=$read('admin/src/Controller/OrderController.php');
$check(str_contains($orders,"authorise('core.delete'") &&
 str_contains($orders,"Session::checkToken('post')") &&
 str_contains($orders,"'cancelled'") &&
 str_contains($orders,"'deleted_at'"),'reversible order trash guards');
$check(file_exists($root.'admin/tmpl/seofilter/default.php'),'SEO editor default layout exists');
$ship=$read('admin/src/Controller/DeliveryController.php');
$check(str_contains($ship,"delivery_method='courier'") &&
 str_contains($ship,"'own','cdek','other'") &&
 str_contains($ship,"Session::checkToken('post')"),'delivery validation and carrier choices');
$check(file_exists($root.'admin/tmpl/delivery/create.php') &&
 file_exists($root.'admin/tmpl/delivery/default.php'),'shipment editor');
$promo=$read('admin/src/Controller/PromocodeController.php');
$check(str_contains($promo,"$"."this->guard('core.create')") &&
 str_contains($promo,"Session::checkToken('post')") &&
 str_contains($promo,'[A-Z0-9-]{3,64}'),'promo code validation');
$check(str_contains($read('admin/tmpl/integrations/default.php'),'COM_MINIMARKET_INTEGRATIONS_NOT_CONNECTED'),
 'no fake provider connection');
$check(str_contains($read('media/js/admin-columns.js'),'mm-columns-toolbar'),
 'compact columns selector');
$sql=$read('admin/sql/updates/mysql/0.5.84.sql');
$check(str_contains($sql,'#__minimarket_shipments') &&
 str_contains($sql,'#__minimarket_promo_codes') &&
 str_contains($sql,'ADD COLUMN deleted_at') &&
 !preg_match('/\b(?:DROP\s+TABLE|TRUNCATE\s+TABLE|DELETE\s+FROM)\b/i',$sql),
 'additive SQL only');
echo "MM84_FEATURES_PASS\n";
