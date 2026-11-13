<?php
/** MiniMarket 0.6.19 reports: UTC ranges, financial gating and 3 admin routes. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'admin/src/Service/CommerceReportRange.php';
$cls='MiniMarket\\Component\\Minimarket\\Administrator\\Service\\CommerceReportRange';
$ok=static function(bool $yes,string $name):void{
 if(!$yes)throw new RuntimeException('MM619_REPORT_FAIL: '.$name);
};
[$from,$to,$until]=$cls::normalize('2026-02-01','2026-02-28');
$ok($from==='2026-02-01'&&$to==='2026-02-28'&&$until==='2026-03-01','exclusive end UTC');
foreach([['2026-02-29','2026-03-01'],['2026-04-01','2026-03-01'],
         ['2020-01-01','2026-01-01']] as $pair){
 try{$cls::normalize($pair[0],$pair[1]);$ok(false,'invalid period accepted');}
 catch(DomainException){$ok(true,'invalid range denied');}
}
$views=['salesreport','returnsreport','businessreport'];
$routes=file_get_contents($root.'admin/src/Controller/DisplayController.php');
$links=file_get_contents($root.'admin/src/Service/AdminEntityLinks.php');
$dashboard=file_get_contents($root.'admin/tmpl/dashboard/default.php');
$manifest=file_get_contents($root.'minimarket.xml');
$preset=file_get_contents($root.'admin/presets/minimarket.xml');
foreach($views as $view){
 $path='admin/src/View/'.ucfirst($view).'/HtmlView.php';
 $file=file_get_contents($root.$path);
 $ok(str_contains($file,"authorise('core.admin','com_minimarket')"),'finance ACL '.$view);
 $ok(str_contains($file,'CommerceReportRange as Range'),'single UTC range '.$view);
 $ok(is_file($root.'admin/tmpl/'.$view.'/default.php'),'template '.$view);
 $ok(str_contains($routes,"'".$view."'")&&str_contains($links,"'".$view."'"),
  'registered route '.$view);
 $ok(str_contains($manifest,'view='.$view)&&str_contains($preset,'view='.$view)
  &&str_contains($dashboard,"'view' => '".$view."'"),
  'three entry points '.$view);
}
$business=file_get_contents($root.'admin/src/View/Businessreport/HtmlView.php');
$ok(str_contains($business,"oi.line_cost_cents IS NULL")
 &&str_contains($business,"s.actual_cost_cents IS NULL")
 &&str_contains($business,"'goods_difference'"),
 'missing source costs never fabricated');
$sales=file_get_contents($root.'admin/src/View/Salesreport/HtmlView.php');
$ok(str_contains($sales,"o.payment_status='paid'")
  &&str_contains($sales,"o.status='completed'")
  &&str_contains($sales,'oi.line_cents'),'report measures paid completed goods');
$returns=file_get_contents($root.'admin/src/View/Returnsreport/HtmlView.php');
$ok(str_contains($returns,'return_items')&&str_contains($returns,'SUM(quantity)'),
 'returns count requested line units, not fictional refunds');
foreach(['ru-RU','en-GB'] as $lang){
 $ini=file_get_contents($root.'admin/language/'.$lang.'/com_minimarket.ini');
 foreach(['SALES_REPORT_TITLE','RETURNS_REPORT_TITLE','BUSINESS_REPORT_TITLE'] as $key)
  $ok(str_contains($ini,'COM_MINIMARKET_'.$key.'='),'locale '.$lang.' '.$key);
}
echo "MM619_REPORTS_PASS\n";
