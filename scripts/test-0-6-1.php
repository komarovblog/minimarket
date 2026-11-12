<?php
/** 0.6.1: all four checkout steps can be skipped by administrator choice. */
declare(strict_types=1);
define('_JEXEC',1);
define('JPATH_ROOT',dirname(__DIR__));
$root=dirname(__DIR__).'/src/com_minimarket/';
require_once $root.'site/src/Service/CheckoutSettings.php';
require_once $root.'site/src/Service/CheckoutData.php';
require_once $root.'site/src/Service/CheckoutDelivery.php';

use MiniMarket\Component\Minimarket\Site\Service\CheckoutSettings;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutData;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;

class Params61 {
    public function __construct(public array $data=[]){}
    public function get(string $name,mixed $default=null):mixed{return $this->data[$name]??$default;}
}
$count=0;
$check=static function(bool $condition,string $message)use(&$count):void {
    ++$count;
    if(!$condition)throw new RuntimeException('MM061_FAIL: '.$message);
};
$admin=new Params61([
    'checkout_step_buyer_enabled'=>0,
    'checkout_step_delivery_enabled'=>0,
    'checkout_step_payment_enabled'=>0,
    'checkout_step_digital_enabled'=>0,
    'checkout_email_mode'=>'hidden',
    'checkout_phone_mode'=>'hidden',
    'delivery_pickup_enabled'=>0,
    'payment_on_receipt_enabled'=>0,
    'payment_bank_transfer_enabled'=>0,
]);
$guest=(object)['id'=>0,'name'=>'','email'=>''];
foreach(['buyer','delivery','payment','digital'] as $step)
    $check(!CheckoutSettings::step($admin,$step),'explicit disabled step '.$step);
$check(CheckoutSettings::skipBuyer($admin,$guest,[['required'=>true]]),
    'guest buyer is skipped unconditionally even with required custom fields');
$policies=CheckoutSettings::fields($admin);
$check($policies['email']==='hidden'&&$policies['phone']==='hidden',
    'missing contact configuration allowed when buyer step is disabled');
$data=CheckoutData::normalize(['name'=>'','phone'=>'','email'=>'','comment'=>''],
    ['name'=>'optional','phone'=>'optional','email'=>'optional','comment'=>'hidden'],true);
$check($data['name']===''&&$data['email']===''&&$data['phone']==='',
    'never fabricate guest identity');
try{
    CheckoutData::normalize(['name'=>'','phone'=>'','email'=>'','comment'=>''],
        ['name'=>'optional','phone'=>'optional','email'=>'optional','comment'=>'hidden']);
    $check(false,'active buyer step contact should not be bypassed');
}catch(DomainException){$check(true,'ordinary checkout contact validation remains');}
$quote=CheckoutDelivery::checkoutQuote($admin,[
    'delivery_method'=>'courier','payment_method'=>'on_receipt'
],1250,250,200,true);
$check($quote['method']==='pending'
    &&$quote['payment_method']==='pending'
    &&$quote['fee_cents']===0&&$quote['rate_source']==='none',
    'skipped steps ignore posted methods and never invent a shipping fee');
$digital=CheckoutDelivery::checkoutQuote($admin,[],1250,0,0,false);
$check($digital['method']==='digital'&&$digital['payment_method']==='pending',
    'digital-only orders require no physical shipping');
$active=new Params61();
$normal=CheckoutDelivery::checkoutQuote($active,[],900,0,0,true);
$check($normal['method']==='pickup'&&$normal['payment_method']==='on_receipt',
    'existing automatic pickup and receipt method preserved');
$activeBad=new Params61(['delivery_pickup_enabled'=>0]);
try {
    CheckoutDelivery::checkoutQuote($activeBad,[],900,0,0,true);
    $check(false,'missing delivery should be rejected when step enabled');
}catch(DomainException){$check(true,'enabled step still validated');}
$cfg=simplexml_load_file($root.'admin/config.xml');
$check($cfg!==false,'native Joomla XML parses');
foreach(['buyer','delivery','payment','digital'] as $step)
    $check(count($cfg->xpath('//fieldset[@name="checkout"]/field[@name="checkout_step_'.$step.'_enabled"]'))===1,
        'own step switch '.$step);
$check(count($cfg->xpath('//field[@name="checkout_consent_text" and @type="editor"]'))===1,
    'native consent HTML editor');
$check(count($cfg->xpath('//field[@name="checkout_require_terms"]'))===0
    &&count($cfg->xpath('//field[@name="checkout_terms_itemid"]'))===0,
    'obsolete required terms checkbox and menu removed');
$check(count($cfg->xpath('//field[@name="checkout_confirmation_description"]'))===0,
    'purchase confirmation is not a standalone editable step');
$view=(string)file_get_contents($root.'site/src/View/Checkout/HtmlView.php');
$ctrl=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
$template=(string)file_get_contents($root.'site/tmpl/checkout/default.php');
$delivery=(string)file_get_contents($root.'site/src/Service/CheckoutDelivery.php');
$storage=(string)file_get_contents($root.'site/src/Service/OrderStorage.php');
$check(str_contains($view,'checkout_consent_text')
    &&str_contains($template,"\$this->consentText")&&str_contains($template,"clean(\$this->consentText,'HTML')"),
    'safe HTML consent shown above submit');
$check(!str_contains($template,'name="accept_terms"')
    &&!str_contains($ctrl,'checkout_require_terms'),
    'text consent never behaves like a mandatory checkbox');
$check(str_contains($ctrl,'CheckoutSettings::requiresPurchaseCode')
    &&str_contains($ctrl,'CheckoutVerification::assertValid')
    &&str_contains($template,'name="purchase_code"'),
    'one-time purchase code remains controlled by confirmation mode');
$check(str_contains($ctrl,'CheckoutDelivery::checkoutQuote')
    &&str_contains($delivery,"'payment_method'=>\$payment")
    &&str_contains($storage,"['pickup','courier','digital','pending']"),
    'skipped steps create explicitly pending order snapshots');
$paymentController=(string)file_get_contents($root.'admin/src/Controller/OrderController.php');
$check(str_contains($paymentController,'DigitalFulfillment::send')
    &&!str_contains($paymentController,"throw new \\DomainException('COM_MINIMARKET_DIGITAL_LOGIN_REQUIRED')"),
    'missing digital recipient no longer prevents real payment confirmation');
$check(!str_contains($view,'COM_MINIMARKET_STEP_DELIVERY_CONFIGURATION')
    &&!str_contains($view,'COM_MINIMARKET_STEP_PAYMENT_CONFIGURATION')
    &&!str_contains($ctrl,'COM_MINIMARKET_STEP_BUYER_CONFIGURATION'),
    'admin override cannot be blocked by mandatory-step heuristics');
foreach(['ru-RU','en-GB'] as $lang){
    $adminLang=(string)file_get_contents($root.'admin/language/'.$lang.'/com_minimarket.ini');
    $siteLang=(string)file_get_contents($root.'site/language/'.$lang.'/com_minimarket.ini');
    foreach(['COM_MINIMARKET_CHECKOUT_CONSENT_TEXT=',
        'COM_MINIMARKET_CHECKOUT_STEP_UNCONDITIONAL_HELP=',
        'COM_MINIMARKET_ORDER_DELIVERY_PENDING='] as $key)
        $check(str_contains($adminLang,$key),'admin locale '.$lang.' '.$key);
    foreach(['COM_MINIMARKET_CHECKOUT_DELIVERY_PENDING_NOTICE=',
        'COM_MINIMARKET_CHECKOUT_PAYMENT_PENDING_NOTICE='] as $key)
        $check(str_contains($siteLang,$key),'site locale '.$lang.' '.$key);
}
$check(!is_file($root.'admin/sql/updates/mysql/0.6.1.sql'),
    'no schema migration needed: pending is stored in existing VARCHAR columns');
echo "MM061_CHECKOUT_PASS: $count checks\n";
