<?php
/** MiniMarket 0.6.22: monthly plans, exact monetary input, three navigation points. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'admin/src/Service/SalesPlan.php';
$service='MiniMarket\\Component\\Minimarket\\Administrator\\Service\\SalesPlan';
$ok=static function(bool $condition,string $message):void{
 if(!$condition)throw new RuntimeException('MM622_PLANFACT_FAIL: '.$message);
};
$invalid=static function(callable $fn,string $message)use($ok):void{
 try{$fn();$ok(false,$message);}catch(DomainException){$ok(true,$message);}
};
$ok($service::period(2026,11)===['2026-11-01 00:00:00','2026-12-01 00:00:00'],
 'UTC month bounds');
$ok($service::period(2026,12)===['2026-12-01 00:00:00','2027-01-01 00:00:00'],
 'year boundary');
foreach([[2020,0],[2026,13],[2019,12],[2101,1]] as [$y,$m])
 $invalid(fn()=> $service::period($y,$m),'invalid year/month');
$ok($service::cents('1')===100&&$service::cents('125.25')===12525
 &&$service::cents('0.01')===1,'exact cents');
foreach(['','0','0.00','-1','12.345','1,50','1e3','99999999999999.99'] as $bad)
 $invalid(fn()=> $service::cents($bad),'reject invalid amount '.$bad);
$ok($service::percentTenths(5000,10000)===500,'50 percent');
$ok($service::percentTenths(12500,10000)===1250,'125 percent');
$ok($service::percentTenths(1,0)===null,'no percentage without valid target');
$manifest=simplexml_load_file($root.'minimarket.xml');
$ok($manifest!==false&&version_compare((string)$manifest->version,'0.6.22','>='),
 'manifest version');
$xml=(string)file_get_contents($root.'minimarket.xml');
$preset=(string)file_get_contents($root.'admin/presets/minimarket.xml');
$dash=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
$links=(string)file_get_contents($root.'admin/src/Service/AdminEntityLinks.php');
$display=(string)file_get_contents($root.'admin/src/Controller/DisplayController.php');
$ok(str_contains($xml,'view=planfactreport')&&str_contains($preset,'view=planfactreport')
 &&str_contains($dash,"'view' => 'planfactreport'")
 &&str_contains($links,"'planfactreport'")
 &&str_contains($display,"'planfactreport'"),'all three navigation surfaces plus URL registry');
$controller=(string)file_get_contents($root.'admin/src/Controller/PlanfactreportController.php');
$ok(str_contains($controller,"authorise('core.admin','com_minimarket')")
 &&str_contains($controller,"Session::checkToken('post')")
 &&str_contains($controller,'SalesPlan::cents') ,'financial edit ACL CSRF and cents');
$view=(string)file_get_contents($root.'admin/src/View/Planfactreport/HtmlView.php');
$ok(str_contains($view,"o.status='completed'")
 &&str_contains($view,"o.payment_status='paid'")
 &&str_contains($view,"o.currency='RUB'")
 &&str_contains($view,"o.deleted_at IS NULL")
 &&str_contains($view,'o.items_total_cents-o.discount_cents')
 &&str_contains($view,'o.created_at>='), 'confirmed paid order facts, no fake revenue');
$schema=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.22.sql');
foreach(['#__minimarket_sales_plans','#__minimarket_sales_plan_revisions'] as $needle)
 $ok(str_contains($schema,$needle)&&str_contains($migration,$needle),'fresh plus migration '.$needle);
$ok(!str_contains($migration,'UPDATE #__minimarket_orders')
 &&!str_contains($migration,'stock_movements'),'no historical rewrite or inventory ledger');
foreach(['ru-RU','en-GB'] as $lang){
 $ini=(string)file_get_contents($root.'admin/language/'.$lang.'/com_minimarket.ini');
 foreach(['COM_MINIMARKET_PLANFACT_REPORT_TITLE','COM_MINIMARKET_PLANFACT_REPORT_NOTE',
   'COM_MINIMARKET_PLANFACT_REPORT_INVALID'] as $key)
  $ok(str_contains($ini,$key.'='),'translation '.$lang.' '.$key);
}
echo "MM622_PLANFACT_PASS\n";
