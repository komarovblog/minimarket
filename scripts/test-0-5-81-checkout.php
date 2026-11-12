<?php
/** 0.5.82 checkout: configurable fields, free pickup and server city rates. */
declare(strict_types=1);
\define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/site/src/Service/CheckoutSettings.php';
require __DIR__.'/../src/com_minimarket/site/src/Service/CheckoutDelivery.php';
require __DIR__.'/../src/com_minimarket/site/src/Service/CheckoutData.php';
use MiniMarket\Component\Minimarket\Site\Service\CheckoutSettings;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutData;
class Params81 {
    public function __construct(private array $values=[]) {}
    public function get(string $key,mixed $default=null):mixed{return $this->values[$key]??$default;}
}
$n=0;
$ok=function(bool $condition,string $reason)use(&$n):void{
 $n++;if(!$condition)throw new RuntimeException('MM81_CHECKOUT_FAIL '.$reason);
};
$reject=function(callable $callback,string $reason)use($ok):void{
 try {$callback();$ok(false,$reason);}catch(DomainException $e){$ok(true,$reason);}
};
$default=new Params81();
$ok(CheckoutSettings::mode($default)==='guest','guest is the default');
$ok(CheckoutSettings::mode(new Params81(['checkout_auth_mode'=>'sms']))==='guests','legacy SMS mode maps to signed-in flow');
$ok(CheckoutSettings::mode(new Params81(['checkout_auth_mode'=>'email']))==='guests',
    'legacy email mode never silently becomes guest');
$ok(CheckoutSettings::mode(new Params81(['checkout_auth_mode'=>'invalid']))==='guests',
    'unknown login mode fails closed');
$policy=CheckoutSettings::fields($default);
$ok($policy['name']==='required' && $policy['email']==='optional','default contact field policy');
$ok(array_keys(CheckoutDelivery::methods($default))===['pickup'],'free pickup is default');
$ok(array_keys(CheckoutDelivery::payments($default))===['on_receipt'],'manual payment is default');
$pickup=CheckoutDelivery::quote($default,['delivery_method'=>'pickup','payment_method'=>'on_receipt','fee_cents'=>999999]);
$ok($pickup['fee_cents']===0 && $pickup['method']==='pickup','pickup is always free even if POST supplies a fee');
$prices=new Params81([
 'delivery_pickup_enabled'=>1,'delivery_pickup_address'=>'г. Москва, ул. Ленина, 10',
 'delivery_courier_enabled'=>1,'delivery_rate_source'=>'directory',
 'delivery_city_rates'=>"Москва|500\nТверь|250,50\nВоронеж|0",
 'payment_on_receipt_enabled'=>1,'payment_bank_transfer_enabled'=>1
]);
$rates=CheckoutDelivery::rates($prices);
$ok(count($rates)===3 && $rates['тверь']['fee_cents']===25050,'rubles converted to integer cents');
$quote=CheckoutDelivery::quote($prices,[
 'delivery_method'=>'courier','delivery_city'=>'  мОСКВА ',
 'delivery_address'=>'ул. Пушкина, дом 7','payment_method'=>'bank_transfer',
 'fee_cents'=>1,'price'=>1
]);
$ok($quote['fee_cents']===50000 && $quote['city']==='Москва' && $quote['rate_source']==='directory',
 'city tariff only from server directory, never from POST');
$reject(fn()=>CheckoutDelivery::quote($prices,[
 'delivery_method'=>'courier','delivery_city'=>'Казань',
 'delivery_address'=>'ул. Пушкина, 7','payment_method'=>'on_receipt'
]),'unknown city rejected');
$reject(fn()=>CheckoutDelivery::quote($prices,[
 'delivery_method'=>'courier','delivery_city'=>'Москва',
 'delivery_address'=>'','payment_method'=>'on_receipt'
]),'missing address rejected');
$reject(fn()=>CheckoutDelivery::quote($prices,[
 'delivery_method'=>'pickup','payment_method'=>'online'
]),'unsupported payment rejected');
$cdek=new Params81(['delivery_pickup_enabled'=>1,
 'delivery_courier_enabled'=>1,'delivery_rate_source'=>'cdek']);
$ok(!isset(CheckoutDelivery::methods($cdek)['courier']),'CDEK disabled until API');
$bad=new Params81(['delivery_courier_enabled'=>1,
 'delivery_city_rates'=>"Москва|100\nмосква|300"]);
$reject(fn()=>CheckoutDelivery::rates($bad),'duplicate city rejected');
$bad=new Params81(['delivery_courier_enabled'=>1,'delivery_city_rates'=>'Москва|-10']);
$reject(fn()=>CheckoutDelivery::rates($bad),'negative fee rejected');
$fieldModes=new Params81([
 'checkout_name_mode'=>'hidden','checkout_phone_mode'=>'hidden',
 'checkout_email_mode'=>'required','checkout_comment_mode'=>'hidden'
]);
$contact=CheckoutData::normalize(['name'=>'FAKE','phone'=>'+79001234567',
 'email'=>'buyer@example.com','comment'=>'FAKE'],CheckoutSettings::fields($fieldModes));
$ok($contact['name']==='' && $contact['phone']==='' && $contact['comment']===''
 && $contact['email']==='buyer@example.com','hidden fields discarded on server');
$reject(fn()=>CheckoutData::normalize(['name'=>'','phone'=>'','email'=>'','comment'=>''],
 CheckoutSettings::fields($fieldModes)),'required email enforced');
$reject(fn()=>CheckoutSettings::fields(new Params81([
 'checkout_phone_mode'=>'hidden','checkout_email_mode'=>'hidden'
])),'both contact methods cannot be hidden');
$root=dirname(__DIR__).'/src/com_minimarket/';
$config=(string)file_get_contents($root.'admin/config.xml');
$ok(simplexml_load_string($config)!==false,'global Joomla options XML is well formed');
$ok(str_contains($config,'name="checkout_auth_mode"')
 && str_contains($config,'name="checkout_name_mode"')
 && str_contains($config,'name="checkout_phone_mode"')
 && str_contains($config,'name="checkout_email_mode"')
 && str_contains($config,'name="checkout_comment_mode"'),'native checkout fields');
$methods=(string)file_get_contents($root.'admin/src/Service/MethodSettings.php');
$ok(str_contains($methods,'delivery_rate_source')
 && str_contains($methods,'delivery_city_rates')
 && str_contains($methods,'payment_on_receipt_enabled')
 && !str_contains($config,'<fieldset name="delivery_methods"')
 && !str_contains($config,'<fieldset name="payment_methods"'),
 'delivery/payment settings live in dedicated method editors');
$controller=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
$ok(str_contains($controller,'CheckoutSettings::requiresAccount')
    && str_contains($controller,'CheckoutSettings::requiresPurchaseCode'),
 'guest mode and every-order OTP are separate server policies');
$ok(str_contains($controller,'CheckoutDelivery::quote')
 && str_contains($controller,'CheckoutSettings::requiresAccount')
 && str_contains($controller,'CheckoutSettings::fields'),'server checkout wiring');
$view=(string)file_get_contents($root.'site/tmpl/checkout/default.php');
$ok(str_contains($view,'name="delivery_method"')
 && str_contains($view,'name="payment_method"')
 && str_contains($view,'data-fee-cents'),'single page checkout form');
$xml=(string)file_get_contents($root.'minimarket.xml');
$ok((bool)preg_match('~<version>([0-9]+\\.[0-9]+\\.[0-9]+)</version>~', $xml, $match)
    && version_compare($match[1], '0.5.81', '>='), 'supported checkout version');
echo "MM81_CHECKOUT_PASS: $n checks\n";
