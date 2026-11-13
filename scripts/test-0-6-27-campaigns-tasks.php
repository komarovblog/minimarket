<?php
/** 0.6.27: safe chain drafts, event templates, distinct tasks and notifications. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require_once $root.'admin/src/Service/NotificationPolicy.php';
require_once $root.'admin/src/Service/MessageTemplates.php';
require_once $root.'admin/src/Service/MarketingFlows.php';
require_once $root.'admin/src/Service/AdminInbox.php';
use MiniMarket\Component\Minimarket\Administrator\Service\MessageTemplates;
use MiniMarket\Component\Minimarket\Administrator\Service\MarketingFlows;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminInbox;
$ok=static function(bool $yes,string $why):void{
 if(!$yes)throw new RuntimeException('MM627_FAIL: '.$why);
};
$rejected=static function(callable $fn,string $why)use($ok):void{
 try{$fn();$ok(false,$why);}catch(DomainException){$ok(true,$why);}
};
$data=MessageTemplates::normalize([
 'title'=>'Your order','audience'=>'customer','event_code'=>'order_created',
 'subject'=>'Order {order_id}','body'=>"Hello {customer_name}\nYou placed order {order_id}",
 'state'=>'1'
]);
$ok($data['state']===1&&$data['channel_code']==='email'
 &&$data['audience']==='customer','correct customer template normalization');
$ok(MessageTemplates::render('Order {order_id} for {customer_name}',[
 'order_id'=>123,'customer_name'=>"An\nna"])==='Order 123 for An na',
 'safe plain-text interpolation');
$rejected(static fn()=>MessageTemplates::normalize([
 'title'=>'X','audience'=>'customer','event_code'=>'wrong',
 'subject'=>'Hi','body'=>'Text']),'unknown customer event');
$rejected(static fn()=>MessageTemplates::normalize([
 'title'=>'X','audience'=>'marketing','event_code'=>'cart_abandoned',
 'subject'=>'Hi {evil}','body'=>'Text']),'no arbitrary placeholders');
$rejected(static fn()=>MessageTemplates::normalize([
 'title'=>'X','audience'=>'customer','event_code'=>'order_created',
 'subject'=>"Hi\r\nBcc: somebody",'body'=>'Text']),'CRLF injection forbidden');
$rejected(static fn()=>MessageTemplates::normalize([
 'title'=>'X','audience'=>'marketing','event_code'=>'cart_abandoned',
 'subject'=>'Hi','body'=>'Text {unknown}']),'invalid body variables');
$flow=MarketingFlows::normalize(['title'=>'Recover cart','trigger_code'=>'cart_abandoned',
 'stop_on_order'=>'1','steps'=>[
 ['delay_minutes'=>'60','template_id'=>'5'],
 ['delay_minutes'=>'1440','template_id'=>'6']
 ]]);
$ok($flow['state']==='draft'&&$flow['steps'][1]['delay_minutes']===1440
 &&$flow['stop_on_order']===1,'campaign sequence remains draft');
$rejected(static fn()=>MarketingFlows::normalize(['title'=>'Bad','trigger_code'=>'cart_abandoned',
 'steps'=>[['delay_minutes'=>'10','template_id'=>'5'],['delay_minutes'=>'9','template_id'=>'6']]]),
 'non-increasing step timing rejected');
$rejected(static fn()=>MarketingFlows::normalize(['title'=>'Bad','trigger_code'=>'cart_abandoned',
 'steps'=>[['delay_minutes'=>'0','template_id'=>'5'],['delay_minutes'=>'0','template_id'=>'6']]]),
 'simultaneous duplicate timing rejected');
$rejected(static fn()=>MarketingFlows::normalize(['title'=>'Bad','trigger_code'=>'unknown',
 'steps'=>[['delay_minutes'=>'1','template_id'=>'5']]]),'unknown marketing trigger');
$ok(MarketingFlows::MAX_STEPS===10&&MarketingFlows::MAX_DELAY_MINUTES===43200,
 'bounded campaigns');
$ok(AdminInbox::KINDS===['order','payment','shipment','ticket','returncase'],
 'immutable activity inbox distinctly sourced from task states');
$task=(string)file_get_contents($root.'admin/src/Service/WorkqueueReads.php');
$ok(str_contains($task,"s.status='awaiting_pickup'")
 &&str_contains($task,'INTERVAL 3 DAY')
 &&str_contains($task,"problem_status='open'"),
 'stale pickup becomes actionable alongside genuine delivery problems');
$notView=(string)file_get_contents($root.'admin/src/View/Notifications/HtmlView.php');
$ok(str_contains($notView,'AdminInbox::load')
 &&!str_contains($notView,'extends \\MiniMarket\\Component\\Minimarket\\Administrator\\View\\Workqueue\\HtmlView'),
 'notifications no longer simply display unresolved tasks');
$controller=(string)file_get_contents($root.'admin/src/Controller/NotificationsController.php');
$ok(str_contains($controller,"Session::checkToken('post')")
 &&str_contains($controller,"authorise('core.manage'")
 &&str_contains($controller,'AdminInbox::mark'),
 'personal event read receipts are ACL and CSRF protected');
$flowCtrl=(string)file_get_contents($root.'admin/src/Controller/MarketingflowController.php');
$templateCtrl=(string)file_get_contents($root.'admin/src/Controller/MessagetemplateController.php');
foreach([$flowCtrl,$templateCtrl] as $ctrl)$ok(
 str_contains($ctrl,"Session::checkToken('post')")&&str_contains($ctrl,"authorise("),
 'write actions use Joomla ACL and CSRF');
$sender=(string)file_get_contents($root.'site/src/Service/NotificationSender.php');
$ok(str_contains($sender,'MessageTemplates::customerEmail')
 &&str_contains($sender,'MessageTemplates::render')
 &&str_contains($sender,'notification_preferences')
 &&str_contains($sender,'notification_email_enabled'),
 'templates render only within the existing consented email sender');
$ok(!str_contains((string)file_get_contents($root.'admin/src/Service/MarketingFlows.php'),
 'getMailer')&&!str_contains($flowCtrl,'->Send('),
 'draft campaign configuration never sends messages');
$dashboard=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
$ok(!str_contains($dashboard,'COM_MINIMARKET_REPORTS_OVERVIEW_HELP'),
 'remove reports explanatory paragraph');
$all=[];
preg_match_all("/\\['view' => '([a-z]+)'/",$dashboard,$m);
$all=$m[1];
$ok(in_array('marketingflows',$all,true)&&in_array('messagetemplates',$all,true)
 &&array_search('promocodes',$all,true)<array_search('marketingflows',$all,true)
 &&array_search('marketingflows',$all,true)<array_search('recommendations',$all,true)
 &&array_search('notificationevents',$all,true)<array_search('messagetemplates',$all,true),
 'new sections sorted in dashboard');
foreach(['ru-RU','en-GB'] as $lang){
 $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_MARKETING_FLOWS_TITLE','COM_MINIMARKET_MESSAGE_TEMPLATES_TITLE'] as $key)
  $ok(isset($ini[$key],$sys[$key]),'both menu locales '.$lang.' '.$key);
 $ok(in_array($ini['COM_MINIMARKET_AUTO_DISCOUNTS_TITLE'],['Акции','Promotions'],true),
 'promotions renamed without touching product price flags');
}
$xml=(string)file_get_contents($root.'minimarket.xml');
$ok(str_contains($xml,'<version>0.6.27</version>'),'version bump');
$ddl=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.27.sql');
$fresh=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
foreach(['#__minimarket_message_templates','#__minimarket_marketing_flows',
 '#__minimarket_marketing_flow_steps','#__minimarket_admin_inbox_reads'] as $table)
 $ok(str_contains($ddl,$table)&&str_contains($fresh,$table),
 'fresh and upgrade DDL '.$table);
echo "MM627_CAMPAIGNS_TASKS_PASS\n";
