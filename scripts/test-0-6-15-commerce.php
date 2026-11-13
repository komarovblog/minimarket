<?php
/** MiniMarket 0.6.15: financial source-of-truth and permission contracts. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$check=static function(bool $ok,string $label):void{
 if(!$ok)throw new RuntimeException('MM615_COMMERCE_FAIL: '.$label);
};
$manifest=simplexml_load_file($root.'minimarket.xml');
$check(version_compare((string)$manifest->version,'0.6.15','>='),'version');
$form=simplexml_load_file($root.'admin/forms/product.xml');
$check(count($form->xpath('//field[@name="cost_price" and @filter="string"]'))===1,
 'single nullable acquisition cost input');
require $root.'site/src/Service/PurchaseCost.php';
$class='MiniMarket\\Component\\Minimarket\\Site\\Service\\PurchaseCost';
$check($class::cents(null)===null&&$class::cents('')===null,'unknown is not zero');
$check($class::cents('0')===0&&$class::cents('0.00')===0,'explicit zero');
$check($class::cents('2.5')===250&&$class::cents('23.47')===2347,'exact decimal cents');
foreach(['-1','1.001','1e3','12,50','10000000000000','x'] as $bad){
 try{$class::cents($bad);$check(false,'invalid amount '.$bad);}
 catch(InvalidArgumentException){$check(true,'rejected '.$bad);}
}
$install=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.15.sql');
foreach(['cost_price','unit_cost_cents','line_cost_cents','actual_cost_cents',
 'cost_reference','cost_confirmed_at','cost_confirmed_by',
 '#__minimarket_shipment_cost_revisions'] as $field){
 $check(str_contains($install,$field)&&str_contains($migration,$field),'schema field '.$field);
}
$check(str_contains($migration,'NULL DEFAULT NULL')
 && !str_contains($migration,'UPDATE #__minimarket_'),'no fabricated historical data');
$order=(string)file_get_contents($root.'site/src/Service/OrderStorage.php');
$check(str_contains($order,'PurchaseCost::snapshot')&&str_contains($order,"'unit_cost_cents','line_cost_cents'")
 && str_contains($order,"$"."costs[$"."line['product_id']]===null?'NULL'"),
 'order transaction snapshots cost with explicit unknown');
$product=(string)file_get_contents($root.'admin/src/Model/ProductModel.php');
$check(str_contains($product,"authorise('core.admin','com_minimarket')")
 && str_contains($product,"unset($"."data['cost_price'])"),
 'nonfinancial product editors cannot submit costs');
$controller=(string)file_get_contents($root.'admin/src/Controller/DeliveryController.php');
$check(str_contains($controller,"$"."this->requirePost('core.admin')")
 && str_contains($controller,'FOR UPDATE')
 && str_contains($controller,"$"."db->transactionStart()")
 && str_contains($controller,'shipment_cost_revisions')
 && str_contains($controller,"$"."previous['actual_cost_cents']!==null"),
 'confirmed shipment expenses have server permissions, row lock and revisions');
$display=(string)file_get_contents($root.'admin/tmpl/delivery/default.php');
$orderView=(string)file_get_contents($root.'admin/tmpl/order/default.php');
$check(str_contains($display,'if($this->canViewFinance)')
 && str_contains($orderView,'if($this->canViewFinance)')
 && str_contains($orderView,'COM_MINIMARKET_FINANCE_UNKNOWN'),
 'financial amounts gated and NULL marked unknown');
foreach(['ru-RU','en-GB'] as $locale){
 $language=(string)file_get_contents($root.'admin/language/'.$locale.'/com_minimarket.ini');
 foreach(['COM_MINIMARKET_COST_PRICE','COM_MINIMARKET_DELIVERY_ACTUAL_COST',
   'COM_MINIMARKET_ORDER_GROSS_DIFFERENCE'] as $key)
    $check(str_contains($language,$key.'='),'translation '.$locale.' '.$key);
}
$check(!is_file($root.'admin/sql/updates/mysql/0.6.15-stock-movements.sql'),
 'no extra stock-movements feature');
echo "MM615_COMMERCE_PASS\n";
