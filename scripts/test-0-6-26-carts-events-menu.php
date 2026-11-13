<?php
/** MiniMarket 0.6.26: first-party carts, event policies, action counters and Joomla structure. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require_once $root.'site/src/Service/CartState.php';
require_once $root.'site/src/Service/AbandonedCartStore.php';
require_once $root.'admin/src/Service/NotificationPolicy.php';
use MiniMarket\Component\Minimarket\Site\Service\AbandonedCartStore;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;
$ok=static function(bool $valid,string $label):void{
 if(!$valid)throw new RuntimeException('MM626_FAIL: '.$label);
};
$reject=static function(callable $f,string $label)use($ok):void{
 try{$f();$ok(false,$label);}catch(DomainException){$ok(true,$label);}
};
$ok(AbandonedCartStore::sanitize(['12:0'=>2,'garbage'=>5])===['12:0'=>2],
 'only bounded real cart lines persist');
$predicate=AbandonedCartStore::abandonedPredicate();
$ok(str_contains($predicate,"cart_status='active'")&&str_contains($predicate,'60 MINUTE')
 &&str_contains($predicate,'30 DAY'),'abandoned means idle, not every session');
$reject(static fn()=>NotificationPolicy::normalizeAdminEvents(['unknown']),
 'unknown admin event is rejected');
$ok(NotificationPolicy::normalizeAdminEvents(['order_new','ticket_new','order_new'])
    ===['order_new','ticket_new'],'admin event permission unique');
$ok(NotificationPolicy::normalizeEvents(['order_created'])===['order_created'],
 'existing customer events unchanged');

$manifest=simplexml_load_file($root.'minimarket.xml');
$ok($manifest!==false&&version_compare((string)$manifest->version,'0.6.26','>='),
 'manifest version');
$dashboard=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
$groups=[
 'management'=>['workqueue','notifications','orders','deliveries','returns','payments',
   'fiscalization','customers','tickets','documents'],
 'reports'=>['businessreport','salesreport','stockreport','returnsreport','planfactreport'],
 'shop'=>['categories','products','bundles','groups','bundlegroups','brands',
  'collections','attributes','orderoptions'],
 'marketing'=>['autodiscounts','promocodes','recommendations','abandonedcarts','reviews','seofilters'],
 'tools'=>['import','feeds'],
 'settings'=>['deliverymethods','paymentmethods','registrationmethods',
  'notificationmethods','notificationevents','fiscalproviders','settings']
];
$all=[];
foreach($groups as $section=>$views){
 $all=array_merge($all,$views);
 $start=strpos($dashboard,"'type' => '".$section."'");
 $ok($start!==false,'dashboard section '.$section);
 preg_match_all("/\\['view' => '([a-z]+)'/",substr($dashboard,$start),$m);
 $ok(array_slice($m[1],0,count($views))===$views,'ordered dashboard group '.$section);
}
$xml=(string)file_get_contents($root.'minimarket.xml');
$preset=(string)file_get_contents($root.'admin/presets/minimarket.xml');
preg_match_all('/<menu link="[^"]*view=([a-z]+)"[^>]*>/',$xml,$m);
preg_match_all('/<menuitem title="[^"]+" type="component"[^>]*link="[^"]*view=([a-z]+)"[^>]*\\/>/',$preset,$p);
$ok($m[1]===array_slice($all,0,-1),'Joomla left submenu follows exact priority');
$ok(array_slice($p[1],1)===array_slice($all,0,-1),'Joomla component menu follows exact priority');
$ok(str_contains($xml,'view=component&amp;component=com_minimarket')
 &&str_contains($preset,'view=component&amp;component=com_minimarket'),
 'global settings still last and linked');
$ok(!str_contains($preset,'undefined'),'no invalid preset placeholders');
foreach($all as $view){
 if($view==='settings')continue;
 $ok((bool)preg_match('/<menu link="[^"]*view='.$view.'" img="class:[^"]+">/',$xml),
 'sidebar icons for '.$view);
 $ok((bool)preg_match('/<menuitem[^>]*icon="[^"]+"[^>]*link="[^"]*view='.$view.'"/',$preset),
 'preset icons for '.$view);
}
preg_match_all("/'icon' => '(icon-[a-z-]+)'/",$dashboard,$icons);
$uniqueIcons=array_unique($icons[1]);
foreach($uniqueIcons as $icon){
 $ok(str_contains($dashboard,"'".$icon."'=>'"),
 'guaranteed vector path for '.$icon);
}
$ok(str_contains($dashboard,'<svg class="mm-dashboard-icon"')
 &&str_contains($dashboard,"'count' => 'orders_new'")
 &&str_contains($dashboard,"'count' => 'notifications_unread'")
 &&str_contains($dashboard,"'count' => 'abandonedcarts'")
 &&str_contains($dashboard,'$mmCount>0'),'visible icons and actionable counts');
$ok(!str_contains($dashboard,'data-mm-stat="none"'),'no fake number placeholders');
$view=(string)file_get_contents($root.'admin/src/View/Dashboard/HtmlView.php');
foreach(['orders_new','deliveries_attention','payments_attention','tickets_new',
 'notifications_unread','abandonedcarts','fiscal_attention'] as $key)
 $ok(str_contains($view,$key),'source-backed alert '.$key);

$cartCtrl=(string)file_get_contents($root.'site/src/Controller/CartController.php');
$checkout=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
$store=(string)file_get_contents($root.'site/src/Service/AbandonedCartStore.php');
$ok(str_contains($cartCtrl,'Session::checkToken')
 &&str_contains($cartCtrl,'AbandonedCartStore::record')
 &&str_contains($checkout,'AbandonedCartStore::converted')
 &&strpos($checkout,'OrderStorage::create(')<strpos($checkout,'AbandonedCartStore::converted('),
 'cart mutation recorded and successful checkout converted, never premature');
$ok(!str_contains($store,'customer_email')&&!str_contains($store,'phone')
 &&!str_contains($store,'getMailer')&&!str_contains($store,'$_COOKIE')
 &&str_contains($store,'random_bytes(32)')
 &&str_contains($store,"hash('sha256',\$token)"),
 'guest privacy, session token and no unsolicited reminders');
$notificationCtrl=(string)file_get_contents($root.'admin/src/Controller/NotificationeventsController.php');
$ok(str_contains($notificationCtrl,"Session::checkToken('post')")
 &&str_contains($notificationCtrl,"authorise('core.options'")
 &&str_contains($notificationCtrl,'normalizeAdminEvents')
 &&str_contains($notificationCtrl,'MethodSettings::save'),
 'administrator event permissions are CSRF guarded and shared with existing settings');
$eventTpl=(string)file_get_contents($root.'admin/tmpl/notificationevents/default.php');
$ok(str_contains($eventTpl,'NotificationPolicy::EVENTS')
 &&str_contains($eventTpl,'NotificationPolicy::ADMIN_EVENTS'),
 'one event-first matrix of customer and administrator events');
$provider=(string)file_get_contents($root.'admin/src/View/Fiscalproviders/HtmlView.php');
$cash=(string)file_get_contents($root.'admin/src/View/Fiscalization/HtmlView.php');
$ok(str_contains($provider,"list(\$db,'fiscal')")
 &&str_contains($cash,'COM_MINIMARKET_CASH_REGISTER_TITLE'),
 'provider configuration and operational register have distinct real screens');
foreach(['ru-RU','en-GB'] as $lang){
 $admin=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_NOTIFICATIONS_TITLE','COM_MINIMARKET_ABANDONED_CARTS_TITLE',
 'COM_MINIMARKET_FISCAL_PROVIDERS_TITLE','COM_MINIMARKET_CASH_REGISTER_TITLE',
 'COM_MINIMARKET_NOTIFICATION_EVENTS_TITLE'] as $key)
  $ok(isset($admin[$key],$sys[$key]),'admin and system translation '.$lang.' '.$key);
}
$sql=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.26.sql');
$install=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$ok(str_contains($sql,'#__minimarket_cart_snapshots')
 &&str_contains($install,'#__minimarket_cart_snapshots')
 &&!str_contains($sql,'email')&&!str_contains($sql,'phone'),
 'additive privacy-minimal cart table for upgrade and fresh install');
echo "MM626_CARTS_EVENTS_MENU_PASS\n";
