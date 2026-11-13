<?php
/** 0.6.17 pickup choices and one bidirectional payment matrix. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'admin/src/Service/MethodSettings.php';
require $root.'site/src/Service/PickupPoints.php';
$ok=static function(bool $condition,string $message):void{
 if(!$condition)throw new RuntimeException('MM617_FAIL: '.$message);
};
$mirror='MiniMarket\\Component\\Minimarket\\Administrator\\Service\\MethodSettings';
$points='MiniMarket\\Component\\Minimarket\\Site\\Service\\PickupPoints';
$default=$mirror::mirrorChanges([],'pickup',['bank_transfer']);
$ok($default['payment_on_receipt_allowed_deliveries']==='courier'
    &&$default['payment_bank_transfer_allowed_deliveries']==='pickup,courier,digital',
    'reverse edit changes exactly the same forward payment settings');
$from=$mirror::mirrorChanges([
 'payment_on_receipt_allowed_deliveries'=>'courier',
 'payment_bank_transfer_allowed_deliveries'=>'pickup,digital'
],'courier',['bank_transfer']);
$ok($from['payment_on_receipt_allowed_deliveries']===''
    &&$from['payment_bank_transfer_allowed_deliveries']==='pickup,courier,digital',
    'reverse editor retains all unrelated pair settings');
try{$mirror::mirrorChanges([],'courier',['on_receipt','on_receipt']);$ok(false,'duplicate payment');}
catch(DomainException){$ok(true,'bad payment rejected');}
$point=(object)['id'=>5,'state'=>1,'title'=>'Central','address'=>'Main street 10','fee_cents'=>12500];
$ok($points::select([$point],0)===$point,'single active point selected');
$ok($points::select([$point],5)===$point,'exact active point selected');
try{$points::select([(object)['id'=>5,'state'=>1],(object)['id'=>6,'state'=>1]],0);
 $ok(false,'unselected multiple locations');}
catch(DomainException){$ok(true,'multi requires explicit selection');}
try{$points::select([(object)['id'=>5,'state'=>0]],5);$ok(false,'inactive point');}
catch(DomainException){$ok(true,'inactive pickup denied');}
$v=$points::validate(['title'=>'Central','address'=>'Main street 10',
 'schedule'=>"Mon-Fri\n09:00-18:00",'fee'=>'125.50','map_url'=>'https://example.org','state'=>'1']);
$ok($v['fee_cents']===12550&&$v['state']===1,'valid monetary amount and schedule');
try{$points::validate(['title'=>'Bad','address'=>'Main street 10','fee'=>'1.123','state'=>'1']);$ok(false,'invalid cents');}
catch(DomainException){$ok(true,'incorrect decimal rejected');}
$controller=(string)file_get_contents($root.'admin/src/Controller/MethodsettingsController.php');
$ok(str_contains($controller,'saveDeliveryCompatibility')&&str_contains($controller,"Session::checkToken('post')"),
 'server validates reverse form');
$view=(string)file_get_contents($root.'admin/tmpl/deliverymethods/default.php');
$ok(str_contains($view,'mm-delivery-method')&&str_contains($view,'allowed_payments[]')
  &&str_contains($view,'point_new=1')&&str_contains($view,'points_mode'),
 'native tabs and pickup editor connected');
$quote=(string)file_get_contents($root.'site/src/Service/CheckoutDelivery.php');
$order=(string)file_get_contents($root.'site/src/Service/OrderStorage.php');
$ok(str_contains($quote,'PickupPoints::select')&&str_contains($order,"'pickup_point_id'"),
 'server-derived pickup selection snapshotted with order');
foreach(['site','admin'] as $part){
 foreach(['ru-RU','en-GB'] as $lang){
  $text=(string)file_get_contents($root.$part.'/language/'.$lang.'/com_minimarket.ini');
  $ok(str_contains($text,'COM_MINIMARKET_PICKUP_MODE_SINGLE='),'locale '.$part.' '.$lang);
 }
}
$ok(is_file($root.'admin/sql/updates/mysql/0.6.17.sql'),'versioned migration');
echo "MM617_PICKUP_MIRROR_PASS\n";
