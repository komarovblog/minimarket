<?php
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/site/src/Service/CartState.php';
require __DIR__.'/../src/com_minimarket/site/src/Service/CartStock.php';
use MiniMarket\Component\Minimarket\Site\Service\CartState;
use MiniMarket\Component\Minimarket\Site\Service\CartStock;
$count = 0;
function check74(bool $ok,string $message):void {
    global $count;
    $count++;
    if (!$ok) throw new RuntimeException('MM74_CART_FAIL: '.$message);
}
function expect74(callable $fn,string $message):void {
    $thrown = false;
    try { $fn(); } catch (DomainException $e) { $thrown = true; }
    check74($thrown,$message);
}
check74(CartState::normalize(null)===[],'null session is empty');
check74(CartState::normalize(['1:0'=>2,'abc'=>5,'2:5'=>4])===['1:0'=>2,'2:5'=>4],
    'discard injected cart keys');
$cart=CartState::add([],1,0,2);
$cart=CartState::add($cart,1,0,1);
$cart=CartState::add($cart,2,21,2);
check74($cart===['1:0'=>3,'2:21'=>2],'merges identical SKU only');
check74(CartState::count($cart)===5,'counts total units');
check74(CartState::put($cart,1,0,0)===['2:21'=>2],'quantity zero removes');
expect74(static fn()=>CartState::put([],1,0,100),'max quantity 99');
expect74(static fn()=>CartState::add(['1:0'=>99],1,0,1),'add refuses overflow');
expect74(static fn()=>CartState::key(0,0),'invalid product id');
expect74(static fn()=>CartState::cents('-1.00'),'negative price');
check74(CartState::cents('1500.25')===150025,'decimal cents exact');
check74(CartState::format(150025)==='1 500.25','formatted total');
$snapshot=[
 'products'=>[
  1=>['category_id'=>1,'state'=>1,'product_type'=>'simple','stock'=>5,'option_stock_mode'=>0],
  2=>['category_id'=>1,'state'=>1,'product_type'=>'simple','stock'=>50,'option_stock_mode'=>1],
  3=>['category_id'=>1,'state'=>1,'product_type'=>'bundle','stock'=>50,'option_stock_mode'=>1],
  4=>['category_id'=>2,'state'=>1,'product_type'=>'simple','stock'=>50,'option_stock_mode'=>1],
  5=>['category_id'=>1,'state'=>1,'product_type'=>'bundle','stock'=>50,'option_stock_mode'=>1],
 ],
 'categories'=>[1=>['parent_id'=>0,'state'=>1],2=>['parent_id'=>1,'state'=>0]],
 'variants'=>[
  21=>['product_id'=>2,'state'=>1,'stock'=>3],
  22=>['product_id'=>2,'state'=>1,'stock'=>8],
  23=>['product_id'=>1,'state'=>1,'stock'=>20],
 ],
 'hasVariants'=>[1=>true,2=>true],
 'items'=>[
  3=>[['component_product_id'=>1,'component_variant_id'=>23,'quantity'=>2],
       ['component_product_id'=>2,'component_variant_id'=>21,'quantity'=>1]],
  5=>[['component_product_id'=>3,'component_variant_id'=>null,'quantity'=>1]]
 ]
];
$testRows=[
 ['product_id'=>1,'variant_id'=>23,'quantity'=>1],
 ['product_id'=>2,'variant_id'=>21,'quantity'=>1],
 ['product_id'=>3,'variant_id'=>0,'quantity'=>1]
];
CartStock::assertAvailable($testRows,$snapshot,true);
check74(true,'bundle and SKU meet combined limits');
expect74(static fn()=>CartStock::assertAvailable([
 ['product_id'=>1,'variant_id'=>23,'quantity'=>4],
 ['product_id'=>3,'variant_id'=>0,'quantity'=>1]
],$snapshot,true),'bundle and product compete for shared product stock');
expect74(static fn()=>CartStock::assertAvailable([
 ['product_id'=>2,'variant_id'=>21,'quantity'=>3],
 ['product_id'=>3,'variant_id'=>0,'quantity'=>1]
],$snapshot,true),'bundle and SKU compete for stock');
CartStock::assertAvailable([
 ['product_id'=>2,'variant_id'=>21,'quantity'=>3],
 ['product_id'=>3,'variant_id'=>0,'quantity'=>1]
],$snapshot,false);
check74(true,'disabled inventory bypasses stock count');
expect74(static fn()=>CartStock::assertAvailable([
 ['product_id'=>4,'variant_id'=>0,'quantity'=>1]
],$snapshot,false),'unpublished ancestor still blocked');
expect74(static fn()=>CartStock::assertAvailable([
 ['product_id'=>2,'variant_id'=>999,'quantity'=>1]
],$snapshot,false),'foreign SKU blocked');
expect74(static fn()=>CartStock::assertAvailable([
 ['product_id'=>2,'variant_id'=>0,'quantity'=>1]
],$snapshot,false),'cannot omit required SKU');
expect74(static fn()=>CartStock::assertAvailable([
 ['product_id'=>5,'variant_id'=>1,'quantity'=>1]
],$snapshot,false),'bundle cannot have variant');
$cycle=$snapshot;
$cycle['items'][3][]=['component_product_id'=>5,'component_variant_id'=>null,'quantity'=>1];
expect74(static fn()=>CartStock::assertAvailable([
 ['product_id'=>5,'variant_id'=>0,'quantity'=>1]
],$cycle,false),'nested bundle cycle blocked');
$paths=[
 'src/com_minimarket/site/src/Controller/CartController.php',
 'src/com_minimarket/site/src/Controller/DisplayController.php',
 'src/com_minimarket/site/src/View/Cart/HtmlView.php',
 'src/com_minimarket/site/tmpl/cart/default.php',
 'src/com_minimarket/site/tmpl/product/default.php',
 'src/com_minimarket/media/js/variants.js',
 'src/com_minimarket/site/src/Service/Router.php',
 'src/com_minimarket/site/language/ru-RU/com_minimarket.ini',
 'src/com_minimarket/site/language/en-GB/com_minimarket.ini',
];
$root=dirname(__DIR__);
$source=[];
foreach($paths as $path) {
 $content=file_get_contents($root.'/'.$path);
 check74(is_string($content),$path.' exists');
 $source[$path]=$content;
}
$controller=$source[$paths[0]];
check74(str_contains($controller,"Session::checkToken('post')"),'CSRF protected POST');
check74(str_contains($controller,"getMethod() !== 'POST'"),'no GET mutation');
check74(str_contains($controller,'assertAvailable('),'server enforces combined stock');
check74(!str_contains($controller,"get('shop_type','physical')"),'no global shop type gate');
check74(str_contains((string)file_get_contents($root.'/src/com_minimarket/site/src/Service/CartCatalog.php'),'DigitalAsset::isReady'),
  'digital products must have a private asset before being sold');
check74(!str_contains($controller,'->price'),'client cannot set price');
check74(str_contains($source[$paths[1]],"'cart'"),'display controller allows cart');
check74(str_contains($source[$paths[3]],"HTMLHelper::_('form.token')"),'cart update/remove forms have CSRF');
check74(str_contains($source[$paths[4]],"data-mm-cart-variant"),'product form posts chosen variant id');
check74(str_contains($source[$paths[4]],"HTMLHelper::_('form.token')"),'add form has CSRF');
check74(str_contains($source[$paths[5]],"cartVariant.value = String(selected.id)"),'JS updates selected SKU');
check74(str_contains($source[$paths[5]],"cartButton.disabled = !canOrder(selected)"),'JS disables unavailable SKU');
check74(str_contains($source[$paths[6]],"return ['view'=>'cart']"),'router accepts /cart');
foreach([$paths[7],$paths[8]] as $languagePath) {
 check74(str_contains($source[$languagePath],'COM_MINIMARKET_CART_STOCK_ERROR='),
    'localized stock errors '.$languagePath);
}
$manifest=file_get_contents($root.'/src/com_minimarket/minimarket.xml');
preg_match('~<version>([0-9]+\\.[0-9]+\\.[0-9]+)</version>~', $manifest, $versionMatch);
check74(isset($versionMatch[1]) && version_compare($versionMatch[1], '0.5.74', '>='), 'component version');
$marker=file_get_contents($root.'/src/com_minimarket/admin/sql/updates/mysql/0.5.75.sql');
check74(str_contains($marker,'SELECT 1;'),'no-DDL update marker');
echo "MM74_CART_PASS: $count checks\n";
