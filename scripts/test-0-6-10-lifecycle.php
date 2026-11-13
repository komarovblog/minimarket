<?php
/** MiniMarket 0.6.10 cross-layer status, stock, reminder and column contracts. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$manifest=simplexml_load_file($root.'minimarket.xml');
if((string)$manifest->version!=='0.6.10')throw new RuntimeException('MM010_VERSION');
$schema=file_get_contents($root.'admin/sql/install.mysql.sql');
$migrate=file_get_contents($root.'admin/sql/updates/mysql/0.6.10.sql');
foreach([$schema,$migrate] as $sql)
 foreach(['stock_allocations','reservation_state','reserved_until','accepted_at','cancelled_reason'] as $needle)
  if(!str_contains($sql,$needle))throw new RuntimeException('MM010_SCHEMA_'.$needle);
$stock=file_get_contents($root.'site/src/Service/StockReservation.php');
foreach(['function allocate','function release','function fulfill','getAffectedRows()',
    'stock=stock-','stock=stock+','ORDER BY source_kind,source_id FOR UPDATE'] as $key)
 if(!str_contains($stock,$key))throw new RuntimeException('MM010_STOCK_'.$key);
$storage=file_get_contents($root.'site/src/Service/OrderStorage.php');
if(!str_contains($storage,'StockReservation::allocate')||
   strpos($storage,'StockReservation::allocate')>strpos($storage,'$db->transactionCommit()'))
 throw new RuntimeException('MM010_ORDER_ATOMIC');
$life=file_get_contents($root.'admin/src/Service/OrderLifecycle.php');
if(str_contains($life,"$"."status='confirmed';")&&str_contains($life,'if ($all'))
 throw new RuntimeException('MM010_FAKE_SHIPMENT_ACCEPTANCE');
if(!str_contains($life,'function assertCancelable')||!str_contains($life,'payment_status'))
 throw new RuntimeException('MM010_CANCEL_CHECK');
$controller=file_get_contents($root.'admin/src/Controller/OrderController.php');
foreach(['cancel_reason','StockReservation::release','COM_MINIMARKET_ORDER_CANCEL_REASON_REQUIRED',
         'accepted_at','function fulfillment','payment_confirmation'] as $needle)
 if(!str_contains($controller,$needle))throw new RuntimeException('MM010_CONTROLLER_'.$needle);
$view=file_get_contents($root.'admin/src/View/Orders/HtmlView.php');
$list=file_get_contents($root.'admin/tmpl/orders/default.php');
foreach(['filter_quick','due_first','due_final','overdue','unpaid_reserved',
 'paid_uncollected','reserved_until','reservation_state'] as $needle)
 if(!str_contains($view,$needle)||!str_contains($list,$needle))
  throw new RuntimeException('MM010_LIST_'.$needle);
$config=simplexml_load_file($root.'admin/config.xml');
foreach(['pickup_unpaid_reserve_days','pickup_paid_review_days',
         'pickup_remind_before_days','pickup_remind_final_days'] as $name)
 if(count($config->xpath('//field[@name="'.$name.'"]'))!==1)
  throw new RuntimeException('MM010_SETTING_'.$name);
foreach(['ru-RU','en-GB'] as $locale){
 $translations=parse_ini_file($root.'admin/language/'.$locale.'/com_minimarket.ini');
 foreach(['COM_MINIMARKET_PICKUP_UNPAID_DAYS','COM_MINIMARKET_ORDER_ACCEPT',
          'COM_MINIMARKET_DELIVERY_STATUS_AWAITING_ASSEMBLY',
          'COM_MINIMARKET_ORDERS_QUICK_OVERDUE'] as $key)
  if(!isset($translations[$key]))throw new RuntimeException('MM010_TRANSLATION_'.$locale.'_'.$key);
}
echo "MM010_ORDER_LIFECYCLE_RESERVATIONS_OK\n";
