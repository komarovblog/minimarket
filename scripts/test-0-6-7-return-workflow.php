<?php
/** MiniMarket 0.6.7: return cases, multi-shipment relations, order audit UI. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$n=0;
$check=static function(bool $condition,string $description)use(&$n):void {
 ++$n;if(!$condition)throw new RuntimeException('MM067_WORKFLOW_FAIL: '.$description);
};
$xml=simplexml_load_file($root.'minimarket.xml');
$check($xml!==false && version_compare((string)$xml->version,'0.6.7','>='),'compatible return workflows');
$menu=(string)file_get_contents($root.'minimarket.xml');
$check((bool)preg_match('~view=deliveries.*?view=returns.*?view=reviews~s',$menu),
 'return menu appears after deliveries and before reviews');
$check(str_contains($menu,'view=returncase&amp;layout=create'),
 'native admin Quicktask creates return case');
$schema=file_get_contents($root.'admin/sql/install.mysql.sql');
$update=file_get_contents($root.'admin/sql/updates/mysql/0.6.7.sql');
foreach([$schema,$update] as $sql) {
 $check(str_contains($sql,'#__minimarket_returns'),'returns table');
 $check(str_contains($sql,'#__minimarket_return_items'),'return item links');
 $check(str_contains($sql,'payment_reference'),'payment gateway reference');
 $check(str_contains($sql,'payment_url'),'payment gateway URL');
 $check(str_contains($sql,'idx_mm_shipment_order'),'non-unique shipment/order index');
}
$check(!str_contains($schema,'UNIQUE KEY idx_mm_shipment_one_per_order'),
 'new installations permit multiple deliveries');
$check(str_contains($update,'DROP INDEX idx_mm_shipment_one_per_order'),
 'update removes legacy unique index without dropping records');
$categoryAttrs=file_get_contents($root.'admin/src/Field/CategoryattributesField.php');
$categoryOptions=file_get_contents($root.'admin/src/Field/CategoryorderoptionsField.php');
foreach([$categoryAttrs,$categoryOptions] as $content) {
 $check(!str_contains($content,'COM_MINIMARKET_SHOW_IN_PRODUCT_LIST')
    &&str_contains($content,'mm-category-rules-viewport'),
    'category edits only eligibility + publication');
}
$catalog=file_get_contents($root.'site/src/Service/ProductAttributeCatalog.php');
$categoryView=file_get_contents($root.'site/src/View/Category/HtmlView.php');
$check(!str_contains($catalog,"\$assignment['list']")&&
    str_contains($catalog,'display_location'),
    'global characteristic display location used across categories');
$check(str_contains($categoryView,'CategoryOrderOptions::resolveModes')
    && !str_contains($categoryView,'CategoryListVisibility::resolve'),
    'order-option display uses global setting');
// New pages are only reachable when DisplayController permits and dispatches
// them, and the native list receives Joomla POST filter normalization.
$dispatcher=file_get_contents($root.'admin/src/Controller/DisplayController.php');
$check(substr_count($dispatcher,"'returns'")>=4 &&
    substr_count($dispatcher,"'returncase'")>=2,
    'return routes listed in native toolbar, allowlist, filter and view dispatch');
$check(str_contains($dispatcher,"NativeListTools::importRequest()"),
    'returns honor Joomla native SearchTools input normalization');
$orderView=file_get_contents($root.'admin/src/View/Order/HtmlView.php');
$orderTpl=file_get_contents($root.'admin/tmpl/order/default.php');
foreach(['shipments','returns'] as $field)
 $check(str_contains($orderView,'
$check(str_contains($orderTpl,'view=returncase&layout=create&order_id=')
    &&str_contains($orderTpl,'view=delivery&layout=create&order_id='),
    'order links to new return and repeated delivery');
$check(str_contains($orderTpl,'view=product&layout=edit&id='),
    'order lines link to original product');
$deliveryController=file_get_contents($root.'admin/src/Controller/DeliveryController.php');
$deliveryView=file_get_contents($root.'admin/src/View/Delivery/HtmlView.php');
$check(!str_contains($deliveryView,"where('s.id IS NULL')")
    &&str_contains($deliveryController,'initial')
    &&str_contains($deliveryController,'redelivery')
    &&str_contains($deliveryController,'replacement'),
    'multiple deliveries with explicit reasons');
$check(str_contains($deliveryController,'OrderHistory::record')
    &&str_contains(file_get_contents($root.'admin/src/Controller/OrderController.php'),
        'OrderHistory::record'),
    'shipment and order status changes audited');
$check(str_contains(file_get_contents($root.'site/src/Service/OrderStorage.php'),
    "order_created"),'timeline starts on order creation');
foreach(['Returncase','Returns'] as $type)
 $check(is_file($root.'admin/src/View/'.$type.'/HtmlView.php'),'working view '.$type);
foreach(['Returncase','Returns'] as $type)
 $check(is_file($root.'admin/src/Controller/'.$type.'Controller.php'),'working controller '.$type);
foreach(['returns/default','returncase/create','returncase/default','review/default'] as $page)
 $check(is_file($root.'admin/tmpl/'.$page.'.php'),'working editor '.$page);
foreach(['orders','deliveries','returns','reviews'] as $view){
 $s=file_get_contents($root.'admin/tmpl/'.$view.'/default.php');
 $check(str_contains($s,"grid.checkall")&&str_contains($s,"grid.id"),
    'native row selection '.$view);
 $check(str_contains($s,'shared/searchtools.php')
    &&str_contains($s,'shared/column-toggle.php'),
    'native filters and column manager '.$view);
}
$history=file_get_contents($root.'admin/src/Service/OrderHistory.php');
$check(str_contains($history,'created_at')&&str_contains($history,'actor_user_id'),
 'order events preserve operator and timestamp');
$check(str_contains(file_get_contents($root.'admin/src/Controller/ReturncaseController.php'),
 'Session::checkToken')&&str_contains(file_get_contents($root.'admin/src/Controller/ReturnsController.php'),
 'ListBulkActions::selected'),
 'return writes use CSRF/ACL, mass updates validate selection');
$check(str_contains(file_get_contents($root.'admin/tmpl/returncase/default.php'),
 'COM_MINIMARKET_RETURN_REFUND_WARNING'),
 'return resolution cannot imply gateway refund');
foreach(['ru-RU','en-GB'] as $lang){
 $terms=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_RETURNS_TITLE','COM_MINIMARKET_DELIVERY_REASON_REPLACEMENT',
          'COM_MINIMARKET_ORDER_HISTORY_BLOCK'] as $key)
    $check(!empty($terms[$key]),'translation '.$lang.' '.$key);
 $check(!empty($sys['COM_MINIMARKET_RETURNS_TITLE']),
    'administrator menu system translation '.$lang);
}
$check(is_file(dirname(__DIR__).'/sample-data/minimarket_mysql84_nnop3_update_0_6_7.sql'),
 'cumulative optional demo SQL matches component version');
echo "MM067_WORKFLOW_PASS: $n checks\n";
.$field.'=')||str_contains($orderView,'
$check(str_contains($orderTpl,'view=returncase&layout=create&order_id=')
    &&str_contains($orderTpl,'view=delivery&layout=create&order_id='),
    'order links to new return and repeated delivery');
$check(str_contains($orderTpl,'view=product&layout=edit&id='),
    'order lines link to original product');
$deliveryController=file_get_contents($root.'admin/src/Controller/DeliveryController.php');
$deliveryView=file_get_contents($root.'admin/src/View/Delivery/HtmlView.php');
$check(!str_contains($deliveryView,"where('s.id IS NULL')")
    &&str_contains($deliveryController,'initial')
    &&str_contains($deliveryController,'redelivery')
    &&str_contains($deliveryController,'replacement'),
    'multiple deliveries with explicit reasons');
$check(str_contains($deliveryController,'OrderHistory::record')
    &&str_contains(file_get_contents($root.'admin/src/Controller/OrderController.php'),
        'OrderHistory::record'),
    'shipment and order status changes audited');
$check(str_contains(file_get_contents($root.'site/src/Service/OrderStorage.php'),
    "order_created"),'timeline starts on order creation');
foreach(['Returncase','Returns'] as $type)
 $check(is_file($root.'admin/src/View/'.$type.'/HtmlView.php'),'working view '.$type);
foreach(['Returncase','Returns'] as $type)
 $check(is_file($root.'admin/src/Controller/'.$type.'Controller.php'),'working controller '.$type);
foreach(['returns/default','returncase/create','returncase/default','review/default'] as $page)
 $check(is_file($root.'admin/tmpl/'.$page.'.php'),'working editor '.$page);
foreach(['orders','deliveries','returns','reviews'] as $view){
 $s=file_get_contents($root.'admin/tmpl/'.$view.'/default.php');
 $check(str_contains($s,"grid.checkall")&&str_contains($s,"grid.id"),
    'native row selection '.$view);
 $check(str_contains($s,'shared/searchtools.php')
    &&str_contains($s,'shared/column-toggle.php'),
    'native filters and column manager '.$view);
}
$history=file_get_contents($root.'admin/src/Service/OrderHistory.php');
$check(str_contains($history,'created_at')&&str_contains($history,'actor_user_id'),
 'order events preserve operator and timestamp');
$check(str_contains(file_get_contents($root.'admin/src/Controller/ReturncaseController.php'),
 'Session::checkToken')&&str_contains(file_get_contents($root.'admin/src/Controller/ReturnsController.php'),
 'ListBulkActions::selected'),
 'return writes use CSRF/ACL, mass updates validate selection');
$check(str_contains(file_get_contents($root.'admin/tmpl/returncase/default.php'),
 'COM_MINIMARKET_RETURN_REFUND_WARNING'),
 'return resolution cannot imply gateway refund');
foreach(['ru-RU','en-GB'] as $lang){
 $terms=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_RETURNS_TITLE','COM_MINIMARKET_DELIVERY_REASON_REPLACEMENT',
          'COM_MINIMARKET_ORDER_HISTORY_BLOCK'] as $key)
    $check(!empty($terms[$key]),'translation '.$lang.' '.$key);
 $check(!empty($sys['COM_MINIMARKET_RETURNS_TITLE']),
    'administrator menu system translation '.$lang);
}
$check(is_file(dirname(__DIR__).'/sample-data/minimarket_mysql84_nnop3_update_0_6_7.sql'),
 'cumulative optional demo SQL matches component version');
echo "MM067_WORKFLOW_PASS: $n checks\n";
.$field.'=[]'),
    'order view loads '.$field);
$check(!str_contains($orderView,"->from($db->quoteName('#__minimarket_order_events','e'))")
    &&!str_contains($orderTpl,'COM_MINIMARKET_ORDER_HISTORY_BLOCK'),
    '0.6.9 hides history in order card without deleting the audit trail');
foreach(['ORDER_STATUS_BLOCK','ORDER_PAYMENT_BLOCK','ORDER_DELIVERY_BLOCK',
 'ORDER_INFO_BLOCK','ORDER_PRODUCTS_BLOCK'] as $block)
 $check(str_contains($orderTpl,'COM_MINIMARKET_'.$block),'order card section '.$block);
$check(str_contains($orderTpl,'view=returncase&layout=create&order_id=')
    &&str_contains($orderTpl,'view=delivery&layout=create&order_id='),
    'order links to new return and repeated delivery');
$check(str_contains($orderTpl,'view=product&layout=edit&id='),
    'order lines link to original product');
$deliveryController=file_get_contents($root.'admin/src/Controller/DeliveryController.php');
$deliveryView=file_get_contents($root.'admin/src/View/Delivery/HtmlView.php');
$check(!str_contains($deliveryView,"where('s.id IS NULL')")
    &&str_contains($deliveryController,'initial')
    &&str_contains($deliveryController,'redelivery')
    &&str_contains($deliveryController,'replacement'),
    'multiple deliveries with explicit reasons');
$check(str_contains($deliveryController,'OrderHistory::record')
    &&str_contains(file_get_contents($root.'admin/src/Controller/OrderController.php'),
        'OrderHistory::record'),
    'shipment and order status changes audited');
$check(str_contains(file_get_contents($root.'site/src/Service/OrderStorage.php'),
    "order_created"),'timeline starts on order creation');
foreach(['Returncase','Returns'] as $type)
 $check(is_file($root.'admin/src/View/'.$type.'/HtmlView.php'),'working view '.$type);
foreach(['Returncase','Returns'] as $type)
 $check(is_file($root.'admin/src/Controller/'.$type.'Controller.php'),'working controller '.$type);
foreach(['returns/default','returncase/create','returncase/default','review/default'] as $page)
 $check(is_file($root.'admin/tmpl/'.$page.'.php'),'working editor '.$page);
foreach(['orders','deliveries','returns','reviews'] as $view){
 $s=file_get_contents($root.'admin/tmpl/'.$view.'/default.php');
 $check(str_contains($s,"grid.checkall")&&str_contains($s,"grid.id"),
    'native row selection '.$view);
 $check(str_contains($s,'shared/searchtools.php')
    &&str_contains($s,'shared/column-toggle.php'),
    'native filters and column manager '.$view);
}
$history=file_get_contents($root.'admin/src/Service/OrderHistory.php');
$check(str_contains($history,'created_at')&&str_contains($history,'actor_user_id'),
 'order events preserve operator and timestamp');
$check(str_contains(file_get_contents($root.'admin/src/Controller/ReturncaseController.php'),
 'Session::checkToken')&&str_contains(file_get_contents($root.'admin/src/Controller/ReturnsController.php'),
 'ListBulkActions::selected'),
 'return writes use CSRF/ACL, mass updates validate selection');
$check(str_contains(file_get_contents($root.'admin/tmpl/returncase/default.php'),
 'COM_MINIMARKET_RETURN_REFUND_WARNING'),
 'return resolution cannot imply gateway refund');
foreach(['ru-RU','en-GB'] as $lang){
 $terms=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_RETURNS_TITLE','COM_MINIMARKET_DELIVERY_REASON_REPLACEMENT',
          'COM_MINIMARKET_ORDER_HISTORY_BLOCK'] as $key)
    $check(!empty($terms[$key]),'translation '.$lang.' '.$key);
 $check(!empty($sys['COM_MINIMARKET_RETURNS_TITLE']),
    'administrator menu system translation '.$lang);
}
$check(is_file(dirname(__DIR__).'/sample-data/minimarket_mysql84_nnop3_update_0_6_7.sql'),
 'cumulative optional demo SQL matches component version');
echo "MM067_WORKFLOW_PASS: $n checks\n";
