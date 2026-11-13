<?php
/** 0.6.28: action-first notifications, unified menu and safe channel draft templates. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require_once $root.'admin/src/Service/NotificationPolicy.php';
require_once $root.'admin/src/Service/MessageTemplates.php';
require_once $root.'admin/src/Service/AttentionInbox.php';
use MiniMarket\Component\Minimarket\Administrator\Service\MessageTemplates;
$check=static fn(bool $ok,string $why)=>$ok?:throw new RuntimeException('MM628_FAIL: '.$why);
$data=MessageTemplates::normalize([
 'title'=>'Delivery template','audience'=>'customer','event_code'=>'delivery_updated',
 'channel_code'=>'telegram','subject'=>'Delivery','body'=>'Order {order_id}','state'=>'0'
]);
$check($data['channel_code']==='telegram'&&$data['state']===0,'non-email draft allowed');
foreach(['max','account'] as $channel){
 $data=MessageTemplates::normalize(['title'=>'Draft','audience'=>'admin','event_code'=>'order_new',
  'channel_code'=>$channel,'subject'=>'New order','body'=>'Order {order_id}','state'=>'0']);
 $check($data['channel_code']===$channel,'future draft '.$channel);
}
foreach([
 ['audience'=>'customer','event_code'=>'delivery_updated','channel_code'=>'telegram','state'=>'1'],
 ['audience'=>'marketing','event_code'=>'order_created','channel_code'=>'max','state'=>'0'],
 ['audience'=>'customer','event_code'=>'delivery_updated','channel_code'=>'unknown','state'=>'0']
] as $invalid){
 try{
  MessageTemplates::normalize(array_merge([
   'title'=>'Invalid','subject'=>'Test','body'=>'Text'],$invalid));
  throw new RuntimeException('MM628_FAIL: unsafe template accepted');
 }catch(DomainException){}
}
$attention=(string)file_get_contents($root.'admin/src/Service/AttentionInbox.php');
$check(str_contains($attention,"status='new' AND deleted_at IS NULL")
 &&str_contains($attention,"status NOT IN ('new','cancelled','completed')")
 &&str_contains($attention,"problem_status='open'")
 &&str_contains($attention,'INTERVAL 3 DAY')
 &&!str_contains($attention,'read_at'),'action status comes from business data');
$dash=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
$check(str_contains($dash,'btn btn-warning')
 &&str_contains($dash,'badge bg-danger')
 &&str_contains($dash,'data-mm-stat="notifications_attention"'),'topbar button uses action badge');
preg_match_all("/\\['view' => '([a-z]+)'/",$dash,$views);
$check(!in_array('workqueue',$views[1],true)&&!in_array('notifications',$views[1],true)
 &&!in_array('messagetemplates',$views[1],true)&&!in_array('notificationmethods',$views[1],true),
 'no duplicate dashboard tiles');
$notif=(string)file_get_contents($root.'admin/tmpl/notifications/default.php');
$check(str_contains($notif,'attentionGroups')&&str_contains($notif,'notifications.markRead')
 &&str_contains($notif,'COM_MINIMARKET_NOTIFICATIONS_HISTORY'),'actions and history separated');
$events=(string)file_get_contents($root.'admin/tmpl/notificationevents/default.php');
$check(str_contains($events,'data-mm-event-disable')
 &&str_contains($events,"['email','telegram','max','account']")
 &&str_contains($events,'view=notificationmethods'),'event-first settings');
$ctrl=(string)file_get_contents($root.'admin/src/Controller/NotificationmethodsController.php');
$check(str_contains($ctrl,"get('events',null,'array')"),'channel save preserves event matrix');
$manifest=(string)file_get_contents($root.'minimarket.xml');
$check(str_contains($manifest,'<version>0.6.28</version>')
 &&!str_contains($manifest,'view=workqueue')
 &&!str_contains($manifest,'view=notifications')
 &&!str_contains($manifest,'view=messagetemplates'),'single settings entrypoint');
foreach(['ru-RU','en-GB'] as $lang){
 $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['DELIVERY_SETTINGS_TITLE','PAYMENT_SETTINGS_TITLE','REGISTRATION_SETTINGS_TITLE',
  'NOTIFICATION_SETTINGS_TITLE','FISCAL_SETTINGS_TITLE','GENERAL_SETTINGS_TITLE'] as $name){
  $key='COM_MINIMARKET_'.$name;
  $check(isset($ini[$key],$sys[$key]),'menu translations '.$lang.' '.$key);
 }
}
echo "MM628_NOTIFICATION_CENTER_PASS\n";
