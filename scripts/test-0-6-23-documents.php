<?php
/** MiniMarket 0.6.23 immutable commercial documents and safe fiscal foundation. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'admin/src/Service/OrderDocuments.php';
$doc='MiniMarket\Component\Minimarket\Administrator\Service\OrderDocuments';
$ok=static function(bool $yes,string $label):void{
 if(!$yes)throw new RuntimeException('MM623_DOCUMENTS_FAIL: '.$label);
};
$bad=static function(callable $action,string $label)use($ok):void{
 try{$action();$ok(false,$label);}catch(DomainException){$ok(true,$label);}
};
$order=['id'=>15,'currency'=>'RUB','created_at'=>'2026-11-13 15:00:00',
 'customer_name'=>'Customer <tag>','email'=>'email@example.invalid','phone'=>'123',
 'delivery_method'=>'pickup','delivery_address'=>'Example st',
 'items_total_cents'=>5000,'discount_cents'=>500,'delivery_fee_cents'=>200,'total_cents'=>4700];
$items=[['sku'=>'SKU-1','title'=>'Original title','quantity'=>2,'unit_cents'=>2500,
 'line_cents'=>5000]];
$snapshot=$doc::snapshot($order,$items);
$ok($snapshot['total_cents']===4700&&$snapshot['lines'][0]['title']==='Original title'
 &&$snapshot['discount_cents']===500,'document uses exact order snapshot and cents');
$order['total_cents']=4701;
$bad(fn()=> $doc::snapshot($order,$items),'inconsistent total rejected');
$order['total_cents']=4700;
$bad(fn()=> $doc::snapshot($order,[]),'empty order rejected');
$bad(fn()=> $doc::snapshot($order,[array_merge($items[0],['line_cents'=>4900])]),
 'line mismatch rejected');
$order['discount_cents']=6000;
$bad(fn()=> $doc::snapshot($order,$items),'discount above value rejected');
$json=json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
$ok($doc::decode($json,hash('sha256',$json))===$snapshot,'verifiable stored document');
try{$doc::decode($json,hash('sha256',$json.'altered'));$ok(false,'tamper detected');}
catch(RuntimeException){$ok(true,'tampering blocked');}

$manifest=simplexml_load_file($root.'minimarket.xml');
$ok($manifest!==false&&version_compare((string)$manifest->version,'0.6.23','>='),
 'manifest bump');
$xml=(string)file_get_contents($root.'minimarket.xml');
$preset=(string)file_get_contents($root.'admin/presets/minimarket.xml');
$dash=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
$links=(string)file_get_contents($root.'admin/src/Service/AdminEntityLinks.php');
$display=(string)file_get_contents($root.'admin/src/Controller/DisplayController.php');
foreach(['documents','fiscalization'] as $view)
 $ok(str_contains($xml,'view='.$view)&&str_contains($preset,'view='.$view)
 &&str_contains($dash,"'view' => '".$view."'")
 &&str_contains($links,"'".$view."'")
 &&str_contains($display,"'".$view."'"),'three navigation surfaces and view dispatch '.$view);
$controller=(string)file_get_contents($root.'admin/src/Controller/DocumentController.php');
$ok(str_contains($controller,"Session::checkToken('post')")
 &&str_contains($controller,"authorise('core.admin','com_minimarket')"),
 'invoice issue POST CSRF superuser access');
$read=(string)file_get_contents($root.'admin/src/View/Document/HtmlView.php');
$ok(str_contains($read,"authorise('core.manage','com_minimarket')")
 &&str_contains($read,'OrderDocuments::decode'),'private verified read');
$fiscal=(string)file_get_contents($root.'admin/src/View/Fiscalization/HtmlView.php');
$ok(str_contains($fiscal,"authorise('core.admin','com_minimarket')")
 &&!str_contains($fiscal,'INSERT INTO'),'fiscal records are read-only');
$orderUi=(string)file_get_contents($root.'admin/tmpl/order/default.php');
$ok(str_contains($orderUi,"task=document.create")
 &&str_contains($orderUi,"'form.token'")
 &&str_contains($orderUi,"documentId"),'real order document tab');

$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.23.sql');
$install=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
foreach(['#__minimarket_order_documents','#__minimarket_fiscal_operations',
         '#__minimarket_fiscal_operation_events'] as $name)
 $ok(str_contains($migration,$name)&&str_contains($install,$name),
 'fresh and upgrade schema '.$name);
$ok(!str_contains($migration,'INSERT INTO #__minimarket_fiscal_operations')
 &&!str_contains($migration,'UPDATE #__minimarket_orders')
 &&!str_contains($migration,'stock_movements'),'no fake receipt/stock changes');
foreach(['ru-RU','en-GB'] as $lang){
 $ini=(string)file_get_contents($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=(string)file_get_contents($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_DOCUMENTS_TITLE','COM_MINIMARKET_FISCAL_TITLE'] as $key)
  $ok(str_contains($ini,$key.'=')&&str_contains($sys,$key.'='),
   'admin and system language '.$lang.' '.$key);
}
echo "MM623_DOCUMENTS_PASS\n";
