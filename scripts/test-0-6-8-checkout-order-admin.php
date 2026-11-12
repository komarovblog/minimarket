<?php
/** MiniMarket 0.6.8: registration source, manual account, delivery and order UI. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$n=0;
$check=static function(bool $ok,string $name)use(&$n):void{
    $n++;if(!$ok)throw new RuntimeException('MM068_UI_FAIL: '.$name);
};
$manifest=simplexml_load_file($root.'minimarket.xml');
$check(version_compare((string)$manifest->version,'0.6.8','>=' ),'component version');

$config=simplexml_load_file($root.'admin/config.xml');
$fields=$config->xpath('//fieldset[@name="checkout"]/field[@name="checkout_verification_method"]');
$check(count($fields)===1 && (string)$fields[0]['type']==='Registrationverification',
    'native visible registration verification selector, no hidden field');
$check((string)$config->xpath('//fieldset[@name="checkout"]')[0]['addfieldprefix']
    ==='MiniMarket\Component\Minimarket\Administrator\Field',
    'Joomla form loads custom field by native namespace');
$method=$root.'admin/src/Field/RegistrationverificationField.php';
$check(is_file($method),'registration methods field available');
$methodContent=file_get_contents($method);
$check(str_contains($methodContent,"InstalledMethodPlugins::list(\$db,'registration')")
   &&str_contains($methodContent,"'email'")
   &&str_contains($methodContent,'->disable = true'),
   'built-in email available, unimplemented provider plugins are disabled');
$checkout=file_get_contents($root.'site/src/Service/CheckoutSettings.php');
$check(str_contains($checkout,"if(\$method!=='email')throw"),
    'server never falsely accepts an unimplemented plugin verification method');

$create=file_get_contents($root.'admin/tmpl/order/create.php');
$check(str_contains($create,"'input'=>'user_id'") &&
    str_contains($create,"'endpoint'=>'users'") &&
    str_contains($create,"'multiple'=>false"),
    'manual order has single-account native reference picker');
$check(str_contains($create,"'edit_url_prefix'=>\$this->canViewUsers"),
    'account edit link shown only with Joomla Users access');
$picker=file_get_contents($root.'admin/src/Controller/ProductpickerController.php');
$check(str_contains($picker,'function users(): void') &&
    str_contains($picker,"authorise('core.create','com_minimarket')") &&
    str_contains($picker,"where('block=0')"),
    'account lookup enforces operator permission and excludes disabled users');
$check(str_contains($picker,'mb_strlen($term)<2') &&
    str_contains($picker,'$db->setQuery($q,0,20)'),
    'account picker has minimum search length and bounded results');
$orderController=file_get_contents($root.'admin/src/Controller/OrderController.php');
$check(str_contains($orderController,"getInt('user_id',0)") &&
    str_contains($orderController,"->from(\$db->quoteName('#__users'))") &&
    str_contains($orderController,"\$userId,\$delivery,'manual'"),
    'manual order verifies Joomla ID server-side, never guesses by email');
$siteCheckout=file_get_contents($root.'site/src/Controller/CheckoutController.php');
$check(str_contains($siteCheckout,'(int)$identity->id,$delivery'),
    'site order already stores authenticated Joomla account');
$orderView=file_get_contents($root.'admin/src/View/Order/HtmlView.php');
$check(str_contains($orderView,'$linkedUser=null') &&
    str_contains($orderView,"->from(\$db->quoteName('#__users'))") &&
    str_contains($orderView,'canViewUsers'),
    'order card displays stored account link with Joomla ACL');

$deliveryPicker=$picker;
$start=strpos($deliveryPicker,'public function orders(): void');
$end=strpos($deliveryPicker,'public function categories(): void',$start);
$sub=substr($deliveryPicker,$start,$end-$start);
$check($start!==false && $end!==false && !str_contains($sub,'s.id IS NULL')
    &&!str_contains($sub,'JOIN') &&str_contains($sub,"o.status<>'cancelled'"),
    'delivery selection permits a second shipment for an eligible order');
$deliveryCreate=file_get_contents($root.'admin/tmpl/delivery/create.php');
$check(str_contains($deliveryCreate,'preselectedOrder') &&
    str_contains($deliveryCreate,"'input'=>'order_id'") &&
    str_contains($deliveryCreate,'view=order&id='),
    'delivery creation preselects order and has reverse link');
$deliveryView=file_get_contents($root.'admin/src/View/Delivery/HtmlView.php');
$check(str_contains($deliveryView,'hasPriorShipment') &&
    str_contains($deliveryView,'preselectedOrder'),
    'repeat delivery presets the appropriate reason');

$order=file_get_contents($root.'admin/tmpl/order/default.php');
$blocks=['COM_MINIMARKET_ORDER_INFO_BLOCK','COM_MINIMARKET_ORDER_STATUS_BLOCK',
    'COM_MINIMARKET_ORDER_PAYMENT_BLOCK','COM_MINIMARKET_ORDER_PRODUCTS_BLOCK',
    'COM_MINIMARKET_ORDER_DELIVERY_BLOCK'];
$pos=-1;
foreach($blocks as $block){
    $new=strpos($order,$block);
    $check($new!==false&&$new>$pos,'stacked blocks in required order: '.$block);
    $pos=$new;
}
$check(str_contains($order,'COM_MINIMARKET_ORDER_USER_UNLINKED') &&
    str_contains($order,'$this->linkedUser'),
    'readable account link and unlinked label');
$check(str_contains($order,"\$o->delivery_method==='pickup'") &&
    str_contains($order,'COM_MINIMARKET_DELIVERY_STATUS_'),
    'pickup fulfillment lives in delivery block; courier uses shipment statuses');
$check(substr_count($order,'view=delivery&layout=create&order_id=')>=2,
    'new delivery action available both in header and delivery block');
$check(!str_contains($order,'COM_MINIMARKET_ORDER_HISTORY_BLOCK') &&
    !str_contains($order,'eventValue('),
    'history is retained in storage but hidden from order view');
$check(!str_contains($orderView,'u.name AS actor_name'),
    'unused audit query removed from order detail');
foreach(['ru-RU','en-GB'] as $lang){
    $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
    foreach(['COM_MINIMARKET_CHECKOUT_VERIFICATION_METHOD_LABEL',
      'COM_MINIMARKET_ORDER_JOOMLA_USER','COM_MINIMARKET_ORDER_USER_UNLINKED',
      'COM_MINIMARKET_ORDER_HISTORY_HELP','COM_MINIMARKET_VERIFICATION_PLUGIN_UNAVAILABLE'] as $key)
      $check(!empty($ini[$key]),'translation '.$lang.' '.$key);
}
$module=simplexml_load_file(dirname(__DIR__).'/src/mod_minimarket_toolbar/mod_minimarket_toolbar.xml');
$check((string)$module->version==='1.0.0',
    'standalone toolbar stays at 1.0.0');
$schema=file_get_contents($root.'admin/sql/updates/mysql/0.6.8.sql');
$check(!preg_match('/\b(?:CREATE|DROP|ALTER|TRUNCATE|INSERT|UPDATE|DELETE)\s+TABLE\b/i',
    preg_replace('/^[ \t]*--[^\r\n]*$/m','',$schema)),
    '0.6.8 has no new schema changes and preserves the 0.6.7 migration');
$check(is_file(dirname(__DIR__).'/sample-data/minimarket_mysql84_nnop3_update_0_6_8.sql'),
    'optional matching demo SQL version');
echo 'MM068_UI_PASS: '.$n." checks\n";
