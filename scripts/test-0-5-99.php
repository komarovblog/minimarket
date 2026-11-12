<?php
/** MiniMarket 0.5.99: simplified settings and protected digital order lifecycle. */
declare(strict_types=1);
define('_JEXEC',1);
define('JPATH_ROOT',dirname(__DIR__));
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'site/src/Service/CartState.php';
require $root.'site/src/Service/CartStock.php';
require $root.'site/src/Service/CheckoutSettings.php';
require $root.'site/src/Service/CheckoutDelivery.php';
require $root.'site/src/Service/DigitalAsset.php';
use MiniMarket\Component\Minimarket\Site\Service\CartStock;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutSettings;
use MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery;
use MiniMarket\Component\Minimarket\Site\Service\DigitalAsset;
$checks=0;
$assert=static function(bool $result,string $name)use(&$checks){
    ++$checks;if(!$result)throw new RuntimeException('MM99_FAIL: '.$name);
};
$p=new class {
    public array $data=['payment_bank_transfer_enabled'=>1,
        'payment_on_receipt_enabled'=>1,'checkout_phone_mode'=>'optional'];
    public function get(string $key,mixed $fallback=null):mixed{return $this->data[$key]??$fallback;}
};
$methods=CheckoutDelivery::methods($p,false);
$assert(array_keys($methods)===['digital'] && $methods['digital']['fee_cents']===0,
    'digital-only checkout needs no shipping');
$payments=CheckoutDelivery::payments($p,true);
$assert(array_keys($payments)===['bank_transfer'],'no cash on receipt for digital-only');
$quote=CheckoutDelivery::quote($p,['delivery_method'=>'digital',
    'payment_method'=>'bank_transfer'],10000,0,0,false);
$assert($quote['method']==='digital'&&$quote['fee_cents']===0,'server digital quote');
try {
    CheckoutDelivery::quote($p,['delivery_method'=>'pickup',
        'payment_method'=>'on_receipt'],10000,0,0,false);
    $assert(false,'digital-only pickup blocked');
} catch (DomainException) {$assert(true,'digital-only pickup blocked');}
$u=(object)['id'=>6,'name'=>'Buyer','email'=>'buyer@example.com','block'=>0];
$assert(CheckoutSettings::skipBuyer($p,$u,[]),'auto-hide complete buyer step');
$p->data['checkout_phone_mode']='required';
$assert(!CheckoutSettings::skipBuyer($p,$u,[]),'never skip required phone');
$digitalProduct=['state'=>1,'category_id'=>1,'product_type'=>'simple',
    'delivery_type'=>'digital','option_stock_mode'=>0,'stock'=>0];
$snapshot=['products'=>[2=>$digitalProduct],
    'categories'=>[1=>['parent_id'=>0,'state'=>1]],
    'variants'=>[],'hasVariants'=>[],'items'=>[]];
CartStock::assertAvailable([['product_id'=>2,'variant_id'=>0,'quantity'=>4]],
    $snapshot,true);
$assert(true,'digital lines bypass physical inventory demand');
$assert(DigitalAsset::path('../etc/passwd')===null,'reject traversal');
$assert(DigitalAsset::path('not-an-asset')===null,'reject unknown format');
$form=simplexml_load_file($root.'admin/config.xml');
$assert($form!==false,'global Joomla configuration parses');
foreach(['shop_type','checkout_buyer_skip','checkout_delivery_skip','checkout_payment_skip',
    'product_detail_show_reviews','category_pagination_next','category_load_previous'] as $old){
    $assert(count($form->xpath('//field[@name="'.$old.'"]'))===0,'remove conflicting field '.$old);
}
foreach(['product_detail_gallery_effect','checkout_digital_description',
    'checkout_consent_text'] as $new){
    $assert(count($form->xpath('//field[@name="'.$new.'"]'))===1,'new working setting '.$new);
}
$productForm=simplexml_load_file($root.'admin/forms/product.xml');
$assert(count($productForm->xpath('//field[@name="delivery_type"]/option'))===2,
    'physical/digital choice belongs to product');
$schema=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.99.sql');
foreach(['digital_asset_key','digital_filename','payment_status',
    'fulfillment_status','#__minimarket_digital_grants',
    '#__minimarket_order_events'] as $key){
    $assert(str_contains($schema,$key)&&str_contains($migration,$key),
        'fresh and upgrade schema '.$key);
}
$assert(!preg_match('/\\b(?:DROP\\s+TABLE|TRUNCATE\\s+TABLE|DELETE\\s+FROM)\\b/i',$migration),
    'additive migration');
$site=(string)file_get_contents($root.'site/src/Controller/DownloadController.php');
$admin=(string)file_get_contents($root.'admin/src/Controller/OrderController.php');
$assets=(string)file_get_contents($root.'admin/src/Service/ProductDigitalFile.php');
$send=(string)file_get_contents($root.'admin/src/Service/DigitalFulfillment.php');
$assert(str_contains($site,'hash_equals')
    &&str_contains($site,"payment_status']!=='paid'")
    &&str_contains($site,'download_count')
    &&str_contains($site,'user_id'),
    'download requires owner, payment, signed link and quota');
$assert(str_contains($admin,"'core.edit.state'")
    &&str_contains($admin,'DigitalFulfillment::prepare')
    &&str_contains($admin,'#__minimarket_order_events'),
    'manual payment workflow is authorized and auditable');
$assert(str_contains($assets,'move_uploaded_file')
    &&str_contains($assets,'dirname(dirname(JPATH_ROOT))')
    &&str_contains($send,'hash_hmac'),'private upload + HMAC delivery');
$gallery=(string)file_get_contents($root.'media/js/gallery.js');
$assert(str_contains($gallery,'showModal()')
    &&str_contains($gallery,'pointermove')&&str_contains($gallery,'ArrowLeft'),
    'interactive lightbox, hover zoom and keyboard navigation');
$checkout=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
$assert(str_contains($checkout,'CheckoutSettings::requiresPurchaseCode')
    &&str_contains($checkout,"Session::checkToken('post')")
    &&str_contains($checkout,'$needsPhysical'),
    'server purchase-code, CSRF and mixed cart validation');
echo "MM99_PASS: ".$checks." assertions\n";
