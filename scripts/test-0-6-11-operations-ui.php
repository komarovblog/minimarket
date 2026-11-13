<?php
/** MiniMarket 0.6.11: payment journal, native admin tabs and matching menus. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$manifest=simplexml_load_file($root.'minimarket.xml');
if(!$manifest||version_compare((string)$manifest->version,'0.6.11','<'))
 throw new RuntimeException('MM611_VERSION');
$sql=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.11.sql');
foreach([$sql,$migration] as $src)
 foreach(['#__minimarket_payment_records','external_reference','order_id','confirmed_at'] as $token)
  if(!str_contains($src,$token))throw new RuntimeException('MM611_PAYMENT_SCHEMA_'.$token);
$controller=(string)file_get_contents($root.'admin/src/Controller/OrderController.php');
if(!str_contains($controller,"->quoteName('#__minimarket_payment_records')")||
   !str_contains($controller,"if(\$before!=='paid')"))
 throw new RuntimeException('MM611_PAYMENT_NOT_JOURNALED');
foreach(['Payments','Payment'] as $view)
 if(!is_file($root.'admin/src/View/'.$view.'/HtmlView.php'))
  throw new RuntimeException('MM611_MISSING_PAYMENT_VIEW_'.$view);
foreach(['payments/default.php','payment/default.php'] as $file)
 if(!is_file($root.'admin/tmpl/'.$file))
  throw new RuntimeException('MM611_MISSING_PAYMENT_TEMPLATE_'.$file);
foreach(['order','delivery','returncase','payment'] as $view){
 $template=(string)file_get_contents($root.'admin/tmpl/'.$view.'/default.php');
 if(!str_contains($template,"HTMLHelper::_('uitab.startTabSet'")||
    !str_contains($template,"HTMLHelper::_('uitab.endTabSet'"))
  throw new RuntimeException('MM611_NATIVE_TABS_'.$view);
}
$orders=(string)file_get_contents($root.'admin/tmpl/orders/default.php');
$ordersView=(string)file_get_contents($root.'admin/src/View/Orders/HtmlView.php');
if(!str_contains($orders,'shared/searchtools.php')||
   !str_contains($orders,"'name'=>'filter_quick'")||
   str_contains($orders,'mm-bulk-cancel-reason')||
   str_contains($orders,'mm-orders-list" class="table table-striped mm-admin-list mm-orders-compact"')===false)
 throw new RuntimeException('MM611_NATIVE_SEARCHTOOLS');
foreach(['awaiting_assembly','assembling','ready','in_transit','awaiting_pickup','delivered','failed'] as $status)
 if(!str_contains($ordersView,$status))throw new RuntimeException('MM611_ALL_DELIVERY_STATUSES_'.$status);
$dashboard=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
foreach(['management','shop','marketing','reports','tools','settings'] as $group)
 if(!str_contains($dashboard,"'type' => '".$group."'"))
  throw new RuntimeException('MM611_DASHBOARD_GROUP_'.$group);
preg_match_all("/\['view' => '([a-z]+)'/",$dashboard,$match);
$views=array_values(array_unique($match[1]));
$menu=[];
foreach($manifest->administration->submenu->menu as $entry){
 $url=(string)$entry['link'];
 if(str_starts_with($url,'option=com_minimarket&view='))$menu[]=substr($url,strlen('option=com_minimarket&view='));
 if(str_starts_with($url,'option=com_config&view=component&component=com_minimarket'))$menu[]='settings';
}
sort($views);sort($menu);
if($views!==$menu)throw new RuntimeException('MM611_DASHBOARD_SIDEBAR_MISMATCH: '.implode(',',$views).' / '.implode(',',$menu));
$preset=(string)file_get_contents($root.'admin/presets/minimarket.xml');
foreach($views as $view)
 if($view!=='settings'&&!str_contains($preset,'option=com_minimarket&amp;view='.$view.'"'))
  throw new RuntimeException('MM611_PRESET_MISSING_'.$view);
foreach(['ru-RU','en-GB'] as $lang){
 $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_PAYMENTS_TITLE','COM_MINIMARKET_DASHBOARD_MARKETING','COM_MINIMARKET_PAYMENT_OPERATIONS'] as $key)
  if(!isset($ini[$key]))throw new RuntimeException('MM611_I18N_'.$lang.'_'.$key);
 if(!isset($sys['COM_MINIMARKET_PAYMENTS_TITLE']))throw new RuntimeException('MM611_SYSTEM_I18N_'.$lang);
}
echo "MM611_NATIVE_OPERATIONS_UI_OK\n";
