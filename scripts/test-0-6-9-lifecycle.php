<?php
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$setting=simplexml_load_file($root.'admin/config.xml')->xpath('//fieldset[@name="basic"]/field[@name="order_manual_status_enabled"]');
if(count($setting)!==1||(string)$setting[0]['default']!=='1')throw new RuntimeException('Manual status config missing');
$l=file_get_contents($root.'admin/src/Service/OrderLifecycle.php');
foreach(['function manualEnabled','function validateManual','function refresh',"'in_transit'","'delivered'","function assertCancelable"] as $k)
 if(!str_contains($l,$k))throw new RuntimeException('Lifecycle missing: '.$k);
foreach(['admin/src/Controller/OrderController.php','admin/src/Controller/DeliveryController.php','admin/src/Service/ListBulkActions.php'] as $p)
 if(!str_contains(file_get_contents($root.$p),'OrderLifecycle::'))throw new RuntimeException('Controller bypass: '.$p);
$c=file_get_contents($root.'admin/tmpl/order/default.php');
if(str_contains($c,'COM_MINIMARKET_ORDER_HISTORY_BLOCK')||!str_contains($c,'mm-order-page')
 ||!str_contains($c,'COM_MINIMARKET_ORDER_ACCEPT')||!str_contains($c,'$ship->created_at')
 ||!str_contains($c,'$r->created_at')||!str_contains($c,'COM_MINIMARKET_ORDER_PAID_AT'))
 throw new RuntimeException('Order UI regression');
if(!str_contains(file_get_contents($root.'media/css/admin-ui.css'),'.mm-order-page .mm-order-card'))
 throw new RuntimeException('Native frame missing');
foreach(['ru-RU','en-GB'] as $locale){
 $ini=parse_ini_file($root.'admin/language/'.$locale.'/com_minimarket.ini');
 foreach(['COM_MINIMARKET_ORDER_MANUAL_STATUS','COM_MINIMARKET_ORDER_MANUAL_DISABLED',
   'COM_MINIMARKET_ORDER_TRANSITION_DENIED','COM_MINIMARKET_ORDER_REFUNDED_AT'] as $k)
  if(empty($ini[$k]))throw new RuntimeException('Missing translation: '.$locale.' '.$k);
}
if(version_compare((string)simplexml_load_file($root.'minimarket.xml')->version,'0.6.9','<'))
 throw new RuntimeException('Version missing');
echo "MM069_LIFECYCLE_UI_OK\n";
