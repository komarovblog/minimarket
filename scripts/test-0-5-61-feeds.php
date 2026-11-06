<?php
/** Feeds: isolated selection, XML, admin/public wiring and Joomla dashboard. */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/admin/src/Service/FeedSelection.php';
require __DIR__.'/../src/com_minimarket/admin/src/Service/FeedConfig.php';
require __DIR__.'/../src/com_minimarket/admin/src/Service/FeedYml.php';
use MiniMarket\Component\Minimarket\Administrator\Service\FeedSelection as Selection;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig as Config;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedYml as Yml;
$n=0;
function check61(bool $ok,string $why):void {global $n;$n++;if(!$ok)throw new RuntimeException('FEED_TEST_FAIL: '.$why);}
$categories=[
 ['id'=>1,'parent_id'=>0,'title'=>'Toys & fun','state'=>1],
 ['id'=>2,'parent_id'=>1,'title'=>'Puzzles <3','state'=>1],
 ['id'=>3,'parent_id'=>2,'title'=>'Complex','state'=>1],
 ['id'=>4,'parent_id'=>1,'title'=>'Hidden','state'=>0],
 ['id'=>5,'parent_id'=>4,'title'=>'Hidden child','state'=>1],
 ['id'=>6,'parent_id'=>0,'title'=>'Home','state'=>1],
 ['id'=>7,'parent_id'=>8,'title'=>'Cycle 7','state'=>1],
 ['id'=>8,'parent_id'=>7,'title'=>'Cycle 8','state'=>1],
];
$products=[
 ['id'=>10,'category_id'=>1,'state'=>1,'product_type'=>'simple'],
 ['id'=>11,'category_id'=>2,'state'=>1,'product_type'=>'simple'],
 ['id'=>12,'category_id'=>3,'state'=>1,'product_type'=>'bundle'],
 ['id'=>13,'category_id'=>4,'state'=>1,'product_type'=>'simple'],
 ['id'=>14,'category_id'=>5,'state'=>1,'product_type'=>'simple'],
 ['id'=>15,'category_id'=>2,'state'=>0,'product_type'=>'simple'],
 ['id'=>16,'category_id'=>6,'state'=>1,'product_type'=>'simple'],
 ['id'=>17,'category_id'=>8,'state'=>1,'product_type'=>'simple'],
];
check61(Selection::ids(['3','2','3','-1','abc',0,9.9])===[2,3],
 'strict positive unique numeric ID parser');
check61(Selection::visibleCategories($categories)===[1,2,3,6],
 'never emit hidden, hidden descendant, cycle');
check61(Selection::selectedCategoryIds($categories,[1],true)===[1,2,3],
 'descendant inclusion across three levels');
check61(Selection::selectedCategoryIds($categories,[1],false)===[1],
 'category descendants switch off');
$base=['category_ids'=>[1],'include_descendants'=>1,'include_bundles'=>1,
 'selection_mode'=>'auto','include_product_ids'=>[],'exclude_product_ids'=>[]];
check61(Selection::productIds($categories,$products,$base)===[10,11,12],
 'auto includes all products in selected published subtree');
$base['exclude_product_ids']=[11];
$base['include_product_ids']=[16];
check61(Selection::productIds($categories,$products,$base)===[10,12,16],
 'per-product exclude + explicitly include outside categories');
$base['include_bundles']=0;
check61(Selection::productIds($categories,$products,$base)===[10,16],
 'bundle toggle cannot be overridden by manual inclusion');
$base['selection_mode']='manual';
$base['include_product_ids']=[11,12,13,14,15,16,17];
check61(Selection::productIds($categories,$products,$base)===[11,16],
 'manual mode still excludes unpublished and bundles when disabled');
$base['include_bundles']=1;
check61(Selection::productIds($categories,$products,$base)===[11,12,16],
 'manual published bundle supported');
$feed=['provider'=>'yandex_market','title'=>'Toy & friends',
 'company'=>'Company < ltd'];
$offers=[[
 'id'=>'p-10','category_id'=>1,'url'=>'https://shop.example/products/10',
 'image'=>'https://shop.example/images/product.webp',
 'title'=>'Doll < & unicorn','price'=>'1990.00','old_price'=>'2500',
 'sku'=>'TEST-10','stock'=>2,'vendor'=>'Brand "A"','description'=>'Suitable for all'
],[
 'id'=>'bad','category_id'=>1,'url'=>'https://shop.example/products/11',
 'image'=>'https://shop.example/images/11.png','price'=>100,'title'=>'Bad',
],[
 'id'=>'sku-123','category_id'=>2,'url'=>'https://shop.example/products/10',
 'image'=>'https://shop.example/images/blue.png','price'=>1990,
 'title'=>'Doll Blue','stock'=>0,
]];
$now=new DateTimeImmutable('2026-11-06T12:00:00+03:00');
$xml=Yml::render($feed,array_slice($categories,0,2),$offers,
 'https://shop.example/',$now);
check61(str_contains($xml,'<yml_catalog date="2026-11-06T12:00:00+03:00">'),
 'Yandex Market YML timestamps include timezone');
check61(str_contains($xml,'Toy &amp; friends') && str_contains($xml,'Puzzles &lt;3'),
 'valid XML escapes category and shop');
check61(str_contains($xml,'Doll &lt; &amp; unicorn')
 && str_contains($xml,'Brand &quot;A&quot;'),'escaped offer and vendor fields');
check61(substr_count($xml,'<offer id=')===2 &&
 str_contains($xml,'id="sku-123" available="false"'),
 'stable SKU offer IDs, out-of-stock and invalid offers excluded');
check61(str_contains($xml,'<oldprice>2500.00</oldprice>'),
 'old price only emitted when greater than price');
$feed['provider']='yandex_direct';
$direct=Yml::render($feed,[],[],'https://shop.example/',$now);
check61(str_contains($direct,'<yml_catalog date="2026-11-06 12:00">'),
 'Yandex Direct XML date format supported separately');
$root=__DIR__.'/../src/com_minimarket/';
$manifest=simplexml_load_file($root.'minimarket.xml');
check61($manifest!==false && version_compare((string)$manifest->version,'0.5.61','>='),'manifest version >= 0.5.61');
check61((string)$manifest->administration->menu->params->dashboard==='minimarket'
 && count($manifest->dashboards->dashboard)===1,'native Joomla dashboard icon declared');
check61((string)$manifest->administration->submenu->menu[count($manifest->administration->submenu->menu)-1]
 ==='COM_MINIMARKET_FEEDS_TITLE','Feeds added at end of import/export menu');
check61(is_file($root.'script.php') && is_file($root.'admin/presets/minimarket.xml'),
 'Joomla native dashboard preset and installer present');
$admin=(string)file_get_contents($root.'admin/src/Controller/FeedController.php');
$public=(string)file_get_contents($root.'site/src/Controller/FeedController.php');
check61(str_contains($admin,"Session::checkToken('post')") &&
 str_contains($admin,"authorise('core.edit'"),'feed saves use CSRF and ACL');
check61(str_contains($public,'hash_equals(') &&
 str_contains($public,"['state']!==1") &&
 str_contains($public,'FeedSelection::productIds('),
 'public feed checks token, published status and product selection');
check61(str_contains($public,'$repo->getProduct(') &&
 str_contains($public,'$repo->getProductVariants('),
 'public product visibility, actual bundle price and SKU combinations');
$editor=(string)file_get_contents($root.'admin/tmpl/feed/default.php');
check61(str_contains($editor,'feed[categories][]') &&
 str_contains($editor,'feed[products][]') &&
 str_contains($editor,'data-mm-feed-all') &&
 str_contains($editor,'data-mm-feed-none'),
 'editor supports bulk and per-item selection');
$schema=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.61.sql');
check61(str_contains($schema,'CREATE TABLE IF NOT EXISTS') &&
 str_contains($schema,'#__minimarket_feeds') &&
 !preg_match('/\bDROP\s+TABLE\b|\bTRUNCATE\b|\bDELETE\s+FROM\b/i',$schema),
 'feed migration is idempotent and does not remove existing catalog data');
foreach(['ru-RU','en-GB'] as $lang) {
 $ui=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 check61(isset($ui['COM_MINIMARKET_FEED_SELECTION_HELP'])
  && isset($ui['COM_MINIMARKET_FEED_YANDEX_BUSINESS'])
  && isset($sys['COM_MINIMARKET_FEEDS_TITLE']),
  'frontend and admin language for '.$lang);
}
echo "MM61_FEEDS_PASS: $n assertions\n";
