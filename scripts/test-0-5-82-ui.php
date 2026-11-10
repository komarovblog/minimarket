<?php
/** MiniMarket 0.5.82 admin UX, manual orders and visibility release gate. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$n=0;
$ok=static function(bool $pass,string $what) use (&$n):void {
    ++$n;if(!$pass)throw new RuntimeException('MM82_UI_FAIL: '.$what);
};
$xml=simplexml_load_file($root.'minimarket.xml');
$sysLang=$xml->xpath('//administration/languages/language');
$ok(count($sysLang)===2
 && in_array('ru-RU/com_minimarket.sys.ini',array_map('strval',$sysLang),true)
 && in_array('en-GB/com_minimarket.sys.ini',array_map('strval',$sysLang),true),
 'administrator menu language files installed globally');

$ok($xml!==false && (string)$xml->version==='0.5.83','manifest');
$menus=$xml->xpath('//administration/submenu/menu');
$links=[];
foreach($menus as $menu)if(isset($menu['link']))$links[]=(string)$menu['link'];
$ok(!in_array('option=com_minimarket&view=dashboard',$links,true),'duplicate miniMarket sidebar gone');
$ok(in_array('option=com_config&view=component&component=com_minimarket',$links,true),'last sidebar settings links Joomla config');
$ok(count($xml->xpath('//administration/submenu/menu[@type="separator"]'))===9,'all 9 sidebar separators');
$order=$xml->xpath('//administration/submenu/menu[@link="option=com_minimarket&view=orders"]');
$ok(count($order)===1 && count($order[0]->xpath('./params/menu-quicktask'))===1,'Orders quicktask');
$cfg=simplexml_load_file($root.'admin/config.xml');
$ok($cfg!==false,'global options parse');
foreach(['basic','display','checkout','delivery','payment','registration'] as $section){
    $ok(count($cfg->xpath('//fieldset[@name="'.$section.'"]'))===1,
        'options tab '.$section);
}
$ok(count($cfg->xpath('//fieldset[@name="inventory"]'))===0
    && count($cfg->xpath('//fieldset[@name="filters"]'))===0,
    'inventory moved to Basic and filters moved to Display');
foreach(['inventory_enabled'] as $key){
    $ok(count($cfg->xpath('//fieldset[@name="basic"]/field[@name="'.$key.'"]'))===1,'inventory key');
}
foreach(['category_products_per_page','filter_price_mode','filter_sku_mode',
 'attribute_display_location','option_display_location','system_display_location',
 'category_show_add_to_cart','category_quick_view'] as $key) {
    $ok(count($cfg->xpath('//fieldset[@name="display"]/field[@name="'.$key.'"]'))===1,
        'display option '.$key);
}
$auth=$cfg->xpath('//field[@name="checkout_auth_mode"]/option');
$values=array_map(static fn($x)=>(string)$x['value'],$auth);
sort($values);
$ok($values===['email','guest','sms'],'guest/SMS/email modes');
foreach(['registration_phone','registration_email','registration_yandex',
    'registration_sber','registration_mts','registration_vk'] as $field){
    $ok(count($cfg->xpath('//fieldset[@name="registration"]/field[@name="'.$field.'"]'))===1,
        'future auth '.$field);
}
$dash=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
$ok(str_contains($dash,"'type' => 'settings'") &&
 str_contains($dash,"'view' => 'deliveries'"),'settings and deliveries tiles');
$orders=(string)file_get_contents($root.'admin/tmpl/orders/default.php');
$ok(str_contains($orders,'filter_product') &&
 str_contains($orders,'filter_status') &&
 str_contains($orders,'filter_delivery') &&
 str_contains($orders,'badge bg-') &&
 str_contains($orders,'layout=create'),'order list filters and native status colors');
$catalog=(string)file_get_contents($root.'admin/src/Service/ManualOrderCatalog.php');
$ok(str_contains($catalog,'CartStock::assertAvailable') &&
 str_contains($catalog,'BundlePrice::calculate') &&
 str_contains($catalog,"'available'=>true"),'operator uses server-priced items and stock');
$controller=(string)file_get_contents($root.'admin/src/Controller/OrderController.php');
$ok(str_contains($controller,"Session::checkToken('post')") &&
 str_contains($controller,"authorise('core.create'") &&
 str_contains($controller,"'manual'"),'protected operator create and source');
$storage=(string)file_get_contents($root.'site/src/Service/OrderStorage.php');
$ok(str_contains($storage,"'order_source'") &&
 str_contains($storage,"'site','manual','integration'"),'order source validated and saved');
$ok(is_file($root.'admin/tmpl/order/create.php') &&
 is_file($root.'media/js/manual-order.js'),'order editor assets');
$attrs=simplexml_load_file($root.'admin/forms/attribute.xml');
$options=simplexml_load_file($root.'admin/forms/orderoption.xml');
$ok(count($attrs->xpath('//field[@name="display_location"]'))===1 &&
 count($options->xpath('//field[@name="display_location"]'))===1,
    'attribute and order option display overrides');
$cat=(string)file_get_contents($root.'site/tmpl/category/default.php');
$ok(str_contains($cat,"RouteHelper::cartAction('add')") &&
 str_contains($cat,"HTMLHelper::_('form.token')") &&
 str_contains($cat,'data-mm-quick-view') &&
 str_contains($cat,'categoryAttributeRows') &&
 str_contains($cat,'categoryVariantOptions'),
    'cart, quickview, attributes and variants on category card');
$cartJs=(string)file_get_contents($root.'media/js/cart.js');
$cartPHP=(string)file_get_contents($root.'site/src/Controller/CartController.php');
$ok(str_contains($cartJs,'fetch(form.action') &&
 str_contains($cartPHP,"Session::checkToken('post')") &&
 str_contains($cartPHP,'JsonResponse'),'async cart still CSRF guarded');
$admin=(string)file_get_contents($root.'admin/src/Controller/DisplayController.php');
$ok(str_contains($admin,'admin-columns.js') &&
 str_contains($admin,'inherited-options.js'),'admin universal enhancements registered');
foreach(['admin-columns.js','category-actions.js','inherited-options.js','manual-order.js'] as $file){
    $ok(is_file($root.'media/js/'.$file),'new Javascript '.$file);
}
$schema=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.82.sql');
$ok(str_contains($schema,'order_source') &&
 str_contains($schema,'display_location') &&
 !preg_match('/\b(?:DROP\s+TABLE|TRUNCATE\s+TABLE|DELETE\s+FROM)\b/i',$schema),
 'additive schema no destruction');
echo "MM82_UI_PASS: $n checks\n";
