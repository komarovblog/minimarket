<?php
/** 0.6.12: inventory report math, no fake inventory movements, menu parity. */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/admin/src/Service/StockReport.php';
use MiniMarket\Component\Minimarket\Administrator\Service\StockReport;
$assert=static function($yes,$what){if(!$yes)throw new RuntimeException('MM612_REPORT_FAIL: '.$what);};
$base=__DIR__.'/../src/com_minimarket/';
$xml=simplexml_load_file($base.'minimarket.xml');
$assert(version_compare((string)$xml->version,'0.6.12','>='),'version');
$rows=StockReport::analyze([
 ['key'=>'product:1','title'=>'A','available'=>10,'held'=>5,'sold'=>20,'revenue_cents'=>8000],
 ['key'=>'variant:2','title'=>'B','available'=>5,'held'=>0,'sold'=>10,'revenue_cents'=>1500],
 ['key'=>'product:3','title'=>'C','available'=>0,'held'=>1,'sold'=>5,'revenue_cents'=>500],
 ['key'=>'product:4','title'=>'D','available'=>12,'held'=>0,'sold'=>0,'revenue_cents'=>0]
],30);
$assert(array_column($rows,'abc')===['A','B','C',''],'ABC over paid direct-line revenue');
$assert($rows[0]['total']===15&&$rows[0]['available']===10,'held is not available');
$assert($rows[0]['cover_days']===15.0,'days based on available not held units');
$assert($rows[2]['cover_days']===0.0,'zero available stock is zero days');
$assert($rows[3]['cover_days']===null,'never invent infinite coverage for missing demand');
foreach(['admin/src/View/Stockreport/HtmlView.php','admin/tmpl/stockreport/default.php'] as $path)
 $assert(is_file($base.$path),'actual report view '.$path);
$svc=file_get_contents($base.'admin/src/Service/StockReport.php');
foreach(["a.status='fulfilled'","o.payment_status='paid'","o.fulfillment_status='delivered'",
 'source_kind','source_id','stock_allocations','order_items'] as $key)
 $assert(str_contains($svc,$key),'source-aware demand '.$key);
$router=file_get_contents($base.'admin/src/Controller/DisplayController.php');
$assert(substr_count($router,"'stockreport'")>=3,'real route, list scripts and POST filters');
$assert(str_contains($router,"'payments'")&&str_contains($router,"'payment'"),'actual 0.6.11 payment routes');
$dashboard=file_get_contents($base.'admin/tmpl/dashboard/default.php');
$assert(str_contains($dashboard,"'type' => 'reports'")&&str_contains($dashboard,"'view' => 'stockreport'"),'reports tile');
$menu=(string)$xml->administration->submenu->asXML();
$assert(str_contains($menu,'stockreport'),'Joomla sidebar');
$preset=file_get_contents($base.'admin/presets/minimarket.xml');
$assert(str_contains($preset,'view=stockreport'),'Joomla admin preset');
$release=file_get_contents(__DIR__.'/release-to-desktop.ps1');
$assert(str_contains($release,'IncludeTaskPlugin')&&str_contains($release,'MINIMARKET_DESKTOP_2_FILES_OK'),
 'default release does not require scheduler');
foreach(['ru-RU','en-GB'] as $lang){
 $ini=parse_ini_file($base.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($base.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_DASHBOARD_REPORTS','COM_MINIMARKET_STOCK_REPORT_TITLE',
          'COM_MINIMARKET_STOCK_REPORT_HELP','COM_MINIMARKET_STOCK_REPORT_COVER'] as $key)
   $assert(isset($ini[$key]),$lang.' '.$key);
 $assert(isset($sys['COM_MINIMARKET_STOCK_REPORT_TITLE']),$lang.' system sidebar label');
}
$assert(is_file($base.'admin/sql/updates/mysql/0.6.12.sql'),'versioned no-DB-change migration');
$assert(is_file(__DIR__.'/../sample-data/minimarket_mysql84_nnop3_update_0_6_12.sql'),'optional sample SQL');
echo "MM612_STOCK_REPORT_PASS\n";
