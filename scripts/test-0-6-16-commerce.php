<?php
/** 0.6.16: single compatibility matrix and delivery event state machine. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'site/src/Service/CheckoutDelivery.php';
require $root.'admin/src/Service/MethodSettings.php';
require $root.'admin/src/Service/ShipmentEvents.php';
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery as Delivery;
use MiniMarket\Component\Minimarket\Administrator\Service\MethodSettings as Settings;
use MiniMarket\Component\Minimarket\Administrator\Service\ShipmentEvents as Events;
$assert=static function(bool $ok,string $what):void {
 if(!$ok)throw new RuntimeException('MM616_CONTRACT_FAIL: '.$what);
};
$reject=static function(callable $task,string $what)use($assert):void{
 try{$task();$assert(false,$what);}catch(DomainException){$assert(true,$what);}
};
$params=new class{
 private array $v=[
 'delivery_pickup_enabled'=>1,'delivery_pickup_fee_cents'=>0,
 'delivery_courier_enabled'=>1,'delivery_rate_source'=>'directory',
 'delivery_city_rates'=>'Москва|300',
 'payment_on_receipt_enabled'=>1,'payment_bank_transfer_enabled'=>1
 ];
 public function get(string $key,mixed $default=null):mixed{return $this->v[$key]??$default;}
 public function set(string $key,mixed $value):void{$this->v[$key]=$value;}
};
$assert(Delivery::allowedForPayment($params,'on_receipt')===['pickup','courier'],'backwards compatible receipt');
$assert(Delivery::allowedForPayment($params,'bank_transfer')===['pickup','courier','digital'],'backwards compatible transfer');
$changed=Settings::normalize('payment','bank_transfer',[
 'enabled'=>'1','instructions'=>'', 'allowed_deliveries'=>['courier','digital']
]);
$assert($changed['payment_bank_transfer_allowed_deliveries']==='courier,digital','canonical payment matrix');
$reject(fn()=>Settings::normalize('payment','on_receipt',
 ['enabled'=>'1','allowed_deliveries'=>['digital']]),'receipt cannot ship digital');
$reject(fn()=>Settings::normalize('payment','bank_transfer',
 ['enabled'=>'1','allowed_deliveries'=>['pickup','pickup']]),'duplicate delivery rejected');
$params->set('payment_on_receipt_allowed_deliveries','pickup');
$params->set('payment_bank_transfer_allowed_deliveries','courier,digital');
$assert(Delivery::compatible($params,'on_receipt','pickup'),'pickup receipt');
$assert(!Delivery::compatible($params,'on_receipt','courier'),'courier cannot receipt');
$assert(!Delivery::compatible($params,'bank_transfer','pickup'),'pickup cannot transfer');
$assert(Delivery::compatible($params,'bank_transfer','courier'),'courier transfer');
$assert(Delivery::compatible($params,'pending','courier'),'deliberately skipped payment pending');
$reject(fn()=>Delivery::quote($params,['delivery_method'=>'courier',
 'delivery_city'=>'Москва','delivery_address'=>'Ленина 20','payment_method'=>'on_receipt']),
 'client cannot bypass pair restriction');
$quote=Delivery::quote($params,['delivery_method'=>'courier',
 'delivery_city'=>'Москва','delivery_address'=>'Ленина 20','payment_method'=>'bank_transfer']);
$assert($quote['fee_cents']===30000&&$quote['payment_method']==='bank_transfer','allowed pair priced on server');
$params->set('payment_on_receipt_allowed_deliveries','pickup,invalid');
$reject(fn()=>Delivery::allowedForPayment($params,'on_receipt'),'invalid settings fail closed');
$time=gmmktime(12,0,0,11,13,2026);
$key='aabbccddeeff00112233445566778899';
$normalize=['event_code'=>'handed_to_carrier','problem_code'=>'',
 'note'=>'Carrier accepted parcel','event_key'=>$key,'occurred_at'=>'2026-11-13T12:00'];
$data=Events::normalize($normalize,$time);
$assert($data['occurred_at']==='2026-11-13 12:00:00','UTC recorded without local conversion');
$state=Events::apply(['status'=>'ready','problem_status'=>'none'],$data);
$assert($state['status']==='in_transit'&&$state['problem_status']==='none','handoff sets in-transit');
$state=Events::apply($state,['event_code'=>'arrived_at_pickup']);
$assert($state['status']==='awaiting_pickup','arrived at pickup');
$state=Events::apply($state,['event_code'=>'pickup_expired']);
$assert($state['status']==='failed','pickup expiry does not restore stock');
$reject(fn()=>Events::apply(['status'=>'delivered','problem_status'=>'none'],['event_code'=>'failed_attempt']),
 'delivered parcel cannot become failed');
$open=Events::apply(['status'=>'in_transit','problem_status'=>'none'],
 ['event_code'=>'problem_opened','problem_code'=>'delay']);
$assert($open['status']==='in_transit'&&$open['problem_status']==='open'
 &&$open['problem_code']==='delay','opening issue does not change fulfillment');
$reject(fn()=>Events::apply($open,['event_code'=>'problem_opened','problem_code'=>'lost']),
 'second open issue rejected');
$closed=Events::apply($open,['event_code'=>'problem_resolved']);
$assert($closed['problem_status']==='resolved'&&$closed['status']==='in_transit','resolution remains separate');
$reject(fn()=>Events::normalize([...$normalize,'occurred_at'=>'2026-11-13T12:08'],$time),'future fact rejected');
$reject(fn()=>Events::normalize([...$normalize,'event_key'=>'bad'],$time),'invalid idempotency key rejected');
$source=(string)file_get_contents($root.'admin/src/Controller/DeliveryController.php');
$assert(str_contains($source,'ShipmentEvents::normalize')
 && str_contains($source,'ShipmentEvents::apply')
 && str_contains($source,'ShipmentEvents::record')
 && str_contains($source,'FOR UPDATE')
 && str_contains($source,"$"."this->requirePost('core.edit.state')"),
 'server locked event acceptance with ACL and persistent audit');
$view=(string)file_get_contents($root.'admin/tmpl/delivery/default.php');
$assert(str_contains($view,"task=delivery.event")&&str_contains($view,'data-mm-shipment-event-form'),'events screen wired');
$method=(string)file_get_contents($root.'admin/tmpl/paymentmethods/default.php');
$assert(str_contains($method,"allowed_deliveries[]")
 && str_contains($method,"uitab.startTabSet"),'native compatibility tab with selected delivery methods');
$site=(string)file_get_contents($root.'site/tmpl/checkout/default.php');
$manual=(string)file_get_contents($root.'admin/tmpl/order/create.php');
$assert(str_contains($site,'data-allowed-deliveries')&&str_contains($manual,'data-allowed-deliveries'),
 'both checkouts expose allowed options for presentation');
$assert(is_file($root.'media/js/payment-compatibility.js'),'shared frontend/admin visibility script');
$schema=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.16.sql');
$assert(str_contains($schema,'#__minimarket_shipment_events')
 && str_contains($migration,'#__minimarket_shipment_events')
 && !str_contains($migration,'stock_movements'),'no stock movement schema');
foreach(['ru-RU','en-GB'] as $lang){
 $txt=(string)file_get_contents($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $assert(str_contains($txt,'COM_MINIMARKET_PAYMENT_COMPATIBILITY=')
   &&str_contains($txt,'COM_MINIMARKET_SHIPMENT_EVENTS_TITLE='),'admin translations '.$lang);
}
echo "MM616_CONTRACT_PASS\n";
