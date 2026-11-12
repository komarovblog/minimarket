<?php
/** MiniMarket 0.6.0: three auth modes, step controls, recipient verification and digital methods. */
declare(strict_types=1);
define('_JEXEC',1);
define('JPATH_ROOT',dirname(__DIR__));
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'site/src/Service/CheckoutSettings.php';
use MiniMarket\Component\Minimarket\Site\Service\CheckoutSettings;
$n=0;
$check=static function(bool $v,string $name)use(&$n){++$n;if(!$v)throw new RuntimeException('MM060_FAIL '.$name);};
$param=new class{
 public array $values=[];
 public function get(string $key,mixed $default=null):mixed{return $this->values[$key]??$default;}
};
foreach(['guest','guests','all'] as $mode){
 $param->values['checkout_auth_mode']=$mode;
 $check(CheckoutSettings::mode($param)===$mode,'mode '.$mode);
 $check(CheckoutSettings::requiresAccount($param)===($mode!=='guest'),'account mode '.$mode);
 $check(CheckoutSettings::requiresPurchaseCode($param)===($mode==='all'),'OTP mode '.$mode);
}
foreach(['email','sms'] as $legacy){
 $param->values['checkout_auth_mode']=$legacy;
 $check(CheckoutSettings::mode($param)==='guests','old mode migrated '.$legacy);
}
$param->values['checkout_verification_method']='';
try{CheckoutSettings::method($param);$check(false,'missing provider');}
catch(DomainException){$check(true,'missing provider is fail closed');}
$param->values['checkout_verification_method']='email';
$check(CheckoutSettings::method($param)==='email','primary method');
foreach(['buyer','delivery','payment','digital'] as $step){
 $check(CheckoutSettings::step($param,$step),'default step enabled '.$step);
 $param->values['checkout_step_'.$step.'_enabled']=0;
 $check(!CheckoutSettings::step($param,$step),'step off '.$step);
}
$form=simplexml_load_file($root.'admin/config.xml');
$check($form!==false,'Joomla global config XML');
$options=$form->xpath('//field[@name="checkout_auth_mode"]/option');
$check(array_map(static fn($o)=>(string)$o['value'],$options)===['guest','guests','all'],
    'exactly three purchase modes');
foreach(['buyer','delivery','payment','digital'] as $step){
 $name='checkout_step_'.$step.'_enabled';
 $check(count($form->xpath('//fieldset[@name="checkout"]/field[@name="'.$name.'"]'))===1,
    'step config '.$step);
}
$check(count($form->xpath('//field[@name="checkout_confirmation_description"]'))===0,
    'no duplicate confirmation step');
$check(count($form->xpath('//field[@name="checkout_verification_method"]'))===1,
    'registration is single source for provider');
$check(count($form->xpath('//field[@name="checkout_digital_description"]'))===1,
    'digital receipt description');
$product=simplexml_load_file($root.'admin/forms/product.xml');
$methods=$product->xpath('//field[@name="digital_delivery_mode"]/option');
$check(array_map(static fn($x)=>(string)$x['value'],$methods)
    ===['file','license','cloud','manual'],'four delivery methods');
$schema=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$upgrade=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.0.sql');
foreach(['digital_recipient_email','digital_delivery_mode',
    'digital_fulfilled_at','#__minimarket_digital_keys'] as $key)
    $check(str_contains($schema,$key)&&str_contains($upgrade,$key),'schema '.$key);
$check(!preg_match('/\\b(DROP TABLE|TRUNCATE TABLE|DELETE FROM)\\b/i',$upgrade),
    'additive migration only');
$ctrl=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
$view=(string)file_get_contents($root.'site/src/View/Checkout/HtmlView.php');
$template=(string)file_get_contents($root.'site/tmpl/checkout/default.php');
$otp=(string)file_get_contents($root.'site/src/Service/CheckoutVerification.php');
$check(str_contains($ctrl,'CheckoutVerification::assertValid')
    &&str_contains($ctrl,'CheckoutVerification::issue')
    &&str_contains($ctrl,'CheckoutVerification::consume'),
    'checkout issues, checks and consumes OTP');
$check(str_contains($otp,'hash_hmac')&&str_contains($otp,'hash_equals')
    &&str_contains($otp,'MAX_ATTEMPTS')&&str_contains($otp,'COOLDOWN'),
    'email challenges hashed and rate limited');
$check(str_contains($template,'digital_recipient_email')
    &&str_contains($template,'purchase_code')
    &&str_contains($template,'recipient_code'),
    'digital recipient and purchase code UI');
$check(!str_contains($view,'$this->hasDigital && (int)$app->getIdentity()->id<1')
    &&!str_contains($ctrl,"COM_MINIMARKET_DIGITAL_LOGIN_REQUIRED"),
    'digital guest orders accepted when guest mode selected');
$download=(string)file_get_contents($root.'site/src/Controller/DownloadController.php');
$check(str_contains($download,"(int)$row['user_id']>0")
    &&str_contains($download,'hash_equals')&&str_contains($download,'download_count'),
    'guest token ownership and paid download protection');
$keys=(string)file_get_contents($root.'site/src/Service/DigitalLicensePool.php');
$check(str_contains($keys,'sodium_crypto_secretbox')&&str_contains($keys,'FOR UPDATE')
    &&str_contains($keys,'order_item_id IS NULL')&&str_contains($keys,'fingerprint'),
    'encrypted non-reusable license allocation');
$fulfill=(string)file_get_contents($root.'admin/src/Service/DigitalFulfillment.php');
$check(str_contains($fulfill,'DigitalLicensePool::assign')
    &&str_contains($fulfill,"['manual','cloud']")
    &&str_contains($fulfill,'allAutomatic'),'real license and pending manual fulfillment');
$order=(string)file_get_contents($root.'admin/src/Controller/OrderController.php');
$check(str_contains($order,'function digitalIssue')
    &&str_contains($order,"'core.edit.state'")
    &&str_contains($order,'payment_status')&&str_contains($order,'#__minimarket_order_events'),
    'manual access only after paid with audit log');
echo "MM060_PASS: $n checks\n";
