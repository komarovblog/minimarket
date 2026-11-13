<?php
/** MiniMarket 0.6.18 CRM and operational workqueue contracts. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$check=static function(bool $ok,string $name):void{
 if(!$ok)throw new RuntimeException('MM618_CRM_FAIL: '.$name);
};
$xml=simplexml_load_file($root.'minimarket.xml');
$check(version_compare((string)$xml->version,'0.6.18','>='),'version');
foreach(['customers','customer','tickets','ticket','workqueue'] as $view){
 $name=ucfirst($view);
 $check(is_file($root.'admin/src/View/'.$name.'/HtmlView.php'),'view '.$view);
 $check(is_file($root.'admin/tmpl/'.$view.'/'.($view==='ticket'?'default':'default').'.php'),
  'template '.$view);
}
$routes=file_get_contents($root.'admin/src/Controller/DisplayController.php');
$links=file_get_contents($root.'admin/src/Service/AdminEntityLinks.php');
foreach(['customers','customer','tickets','ticket','workqueue'] as $view){
 $check(str_contains($routes,"'".$view."'")&&str_contains($links,"'".$view."'"),'registered view '.$view);
}
$dashboard=file_get_contents($root.'admin/tmpl/dashboard/default.php');
$preset=file_get_contents($root.'admin/presets/minimarket.xml');
$manifest=file_get_contents($root.'minimarket.xml');
foreach(['customers','tickets','workqueue'] as $view){
 $check(str_contains($dashboard,"'view' => '".$view."'")
   &&str_contains($preset,'view='.$view)
   &&str_contains($manifest,'view='.$view),'all three navigation layers '.$view);
}
$crm=file_get_contents($root.'admin/src/View/Customers/HtmlView.php');
$check(str_contains($crm,'COUNT(DISTINCT o.user_id)')
    &&str_contains($crm,'o.user_id>0')&&str_contains($crm,'JOIN'),
    'CRM only groups verified Joomla identities');
$notes=file_get_contents($root.'admin/src/Controller/CustomerController.php');
$check(str_contains($notes,"authorise('core.edit','com_minimarket')")
   &&str_contains($notes,"Session::checkToken('post')"),'authenticated customer notes');
$tickets=file_get_contents($root.'admin/src/Controller/TicketController.php');
$check(str_contains($tickets,"authorise('core.edit.state','com_minimarket')")
 &&str_contains($tickets,'FOR UPDATE')
 &&str_contains($tickets,'$row[\'user_id\']'),'linked orders cannot be impersonated');
$queue=file_get_contents($root.'admin/src/View/Workqueue/HtmlView.php');
foreach(['support_tickets','shipments','orders','returns'] as $table)
 $check(str_contains($queue,$table),'queue references real source '.$table);
$install=file_get_contents($root.'admin/sql/install.mysql.sql');
$migration=file_get_contents($root.'admin/sql/updates/mysql/0.6.18.sql');
foreach(['#__minimarket_customer_notes','#__minimarket_support_tickets',
         '#__minimarket_support_messages'] as $name){
 $check(str_contains($install,$name)&&str_contains($migration,$name),'schema '.$name);
}
$check(!str_contains($migration,'stock_movements'),'no new stock ledger');
foreach(['ru-RU','en-GB'] as $lang){
 $text=file_get_contents($root.'admin/language/'.$lang.'/com_minimarket.ini');
 foreach(['CUSTOMERS_TITLE','TICKETS_TITLE','WORKQUEUE_TITLE'] as $key)
  $check(str_contains($text,'COM_MINIMARKET_'.$key.'='),'translation '.$lang.' '.$key);
}
echo "MM618_CRM_WORKQUEUE_PASS\n";
