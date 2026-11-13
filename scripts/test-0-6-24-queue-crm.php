<?php
/** MiniMarket 0.6.24: isolated personal read flags and real ticket links. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require_once $root.'admin/src/Service/WorkqueueReads.php';
$service='MiniMarket\Component\Minimarket\Administrator\Service\WorkqueueReads';
$ok=static function(bool $yes,string $why):void {
 if(!$yes)throw new RuntimeException('MM624_QUEUE_CRM_FAIL: '.$why);
};
$row=(object)['id'=>7,'state_code'=>'new:normal','state_at'=>'2026-11-13 13:00:00'];
$first=$service::signature('ticket',$row);
$ok(strlen($first)===64&&$first===$service::signature('ticket',$row),
 'same source event, stable signature');
$row->state_code='new:high';
$ok($first!==$service::signature('ticket',$row),'priority change becomes unread');
$row->state_code='new:normal';$row->state_at='2026-11-13 13:00:01';
$ok($first!==$service::signature('ticket',$row),'new internal note becomes unread');
$row->state_at='2026-11-13 13:00:00';
$ok($first!==$service::signature('returncase',$row),'source kinds do not share receipts');
$row->event_revision=5;
$ok($first!==$service::signature('ticket',$row),'new message ID within same second resets read');
$row->event_revision=0;
try{$service::signature('fake',$row);$ok(false,'unknown type accepted');}
catch(DomainException){$ok(true,'invalid source rejected');}

$manifest=simplexml_load_file($root.'minimarket.xml');
$ok($manifest!==false&&version_compare((string)$manifest->version,'0.6.24','>='),
 'manifest version');
$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.24.sql');
$fresh=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
foreach(['#__minimarket_queue_reads','priority','shipment_id','return_id'] as $name)
 $ok(str_contains($migration,$name)&&str_contains($fresh,$name),'both schemas '.$name);
$ok(!str_contains($migration,'stock_movements')
 &&!str_contains($migration,'UPDATE #__minimarket_orders')
 &&!str_contains($migration,'INSERT INTO #__minimarket_notification_log'),
 'no stock, money or invented outgoing notice');
$serviceText=(string)file_get_contents($root.'admin/src/Service/WorkqueueReads.php');
foreach(['FOR UPDATE','source_signature','user_id=','source_kind=','source_id=',
 'ON DUPLICATE KEY UPDATE','hash_equals','transactionStart','transactionCommit'] as $v)
 $ok(str_contains($serviceText,$v),'server read mark guard '.$v);
$queue=(string)file_get_contents($root.'admin/src/View/Workqueue/HtmlView.php');
foreach(['support_tickets','shipments','orders','returns','WorkqueueReads::sourceSql',
 'WorkqueueReads::receipts'] as $v)
 $ok(str_contains($queue,$v),'queue resolves current records '.$v);
$controller=(string)file_get_contents($root.'admin/src/Controller/WorkqueueController.php');
$ok(str_contains($controller,"authorise('core.manage','com_minimarket')")
 &&str_contains($controller,"Session::checkToken('post')")
 &&str_contains($controller,"getIdentity()->id")
 &&str_contains($controller,"WorkqueueReads::save"),'actor ACL CSRF current source');
$markup=(string)file_get_contents($root.'admin/tmpl/workqueue/default.php');
$ok(str_contains($markup,'task=workqueue.setRead')
 &&str_contains($markup,'name="signature"')
 &&str_contains($markup,'form.token'),'real read/unread controls');

$tickets=(string)file_get_contents($root.'admin/src/Controller/TicketController.php');
$ok(str_contains($tickets,'SELECT order_id FROM')
 &&str_contains($tickets,'$relatedId')
 &&str_contains($tickets,'$orderId')
 &&str_contains($tickets,'FOR UPDATE')
 &&str_contains($tickets,'shipment_id')
 &&str_contains($tickets,'return_id')
 &&str_contains($tickets,"in_array(\$priority,['normal','high'],true)"),
 'ticket links validated against source order, priority whitelisted');
$ticketUi=(string)file_get_contents($root.'admin/tmpl/ticket/default.php');
$createUi=(string)file_get_contents($root.'admin/tmpl/ticket/create.php');
$listUi=(string)file_get_contents($root.'admin/tmpl/tickets/default.php');
$ok(str_contains($ticketUi,"view=delivery")&&str_contains($ticketUi,"view=returncase")
 &&str_contains($createUi,'name="priority"')
 &&str_contains($createUi,'name="shipment_id"')
 &&str_contains($createUi,'name="return_id"')
 &&str_contains($listUi,'filter_priority'),'editor list and links');

foreach(['ru-RU','en-GB'] as $loc){
 $ini=(string)file_get_contents($root.'admin/language/'.$loc.'/com_minimarket.ini');
 foreach(['COM_MINIMARKET_WORKQUEUE_MARK_READ',
         'COM_MINIMARKET_WORKQUEUE_STALE','COM_MINIMARKET_TICKET_PRIORITY_HIGH',
         'COM_MINIMARKET_TICKET_LINK_HELP'] as $key)
  $ok(substr_count($ini,$key.'=')===1,'unique locale '.$loc.' '.$key);
}
echo "MM624_QUEUE_CRM_PASS\n";
