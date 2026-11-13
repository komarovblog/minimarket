<?php
/** MiniMarket 0.6.25: category sources, product overrides, ABC/XYZ, RU/EN and navigation. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require_once $root.'admin/src/Service/StockReport.php';
require_once $root.'site/src/Service/CategoryRecommendationRules.php';
require_once $root.'admin/src/Service/RecommendationSettings.php';
use MiniMarket\Component\Minimarket\Administrator\Service\StockReport;
use MiniMarket\Component\Minimarket\Site\Service\CategoryRecommendationRules as Rules;
use MiniMarket\Component\Minimarket\Administrator\Service\RecommendationSettings as Settings;
$ok=static function(bool $yes,string $msg):void {
 if(!$yes)throw new RuntimeException('MM625_FAIL: '.$msg);
};
$reject=static function(callable $fn,string $msg)use($ok):void {
 try{$fn();$ok(false,$msg);}catch(DomainException){$ok(true,$msg);}
};
$ok(StockReport::xyz([10,10,10])==='X','stable demand X');
$ok(StockReport::xyz([10,12,8])==='Y','moderate variation Y');
$ok(StockReport::xyz([1,1,20])==='Z','unstable demand Z');
$ok(StockReport::xyz([0,0,0])==='','no demand has no XYZ');
$ok(StockReport::xyz([-1,0,1])==='', 'reject negative units');
$rows=StockReport::analyze([
 ['key'=>'a','available'=>30,'held'=>0,'sold'=>10,'revenue_cents'=>2000,
  'xyz_buckets'=>[3,3,3]],
 ['key'=>'b','available'=>10,'held'=>0,'sold'=>10,'revenue_cents'=>1000,
  'xyz_buckets'=>[1,1,8]]
],90);
$ok($rows[0]['xyz']==='X'&&$rows[1]['xyz']==='Z'
 &&$rows[0]['abc']==='A','ABC and XYZ independent');
$ok(Rules::ids('1,2,3')===[1,2,3],'CSV IDs retained');
$ok(Rules::ids('1,1')===[]&&Rules::ids('1,0')===[],'duplicate and zero IDs rejected');
$normalized=Rules::normalize([
 'similar_scope'=>'selected','similar_categories'=>['2','5'],
 'similar_attributes'=>['11','12'],'together_scope'=>'own'
]);
$ok($normalized['similar_scope']==='selected'
 &&$normalized['similar_categories']===[2,5]
 &&$normalized['similar_attributes']===[11,12]
 &&$normalized['together_scope']==='own','independent category rules');
$reject(static fn()=>Rules::normalize(['similar_scope'=>'selected','similar_categories'=>[]]),
 'selected categories cannot be empty');
$reject(static fn()=>Rules::normalize(['similar_scope'=>'unknown']),'unknown scope rejected');
$reject(static fn()=>Rules::normalize(['similar_scope'=>'selected',
 'similar_categories'=>['1','1']]),'duplicates rejected');
$ok(Rules::normalize(['similar_scope'=>'inherit','similar_categories'=>['1']])
 ['similar_categories']===[],'inherited rules cannot store phantom scope');
$m=Settings::normalize('inherit','hybrid','2,3','4',1);
$ok($m['modes']['similar']==='inherit'&&$m['modes']['together']==='hybrid'
 &&$m['similar']===[2,3],'product overrides remain independent');
$reject(static fn()=>Settings::normalize('manual','auto','1','',1),'self edge rejected');
$reject(static fn()=>Settings::normalize('manual','auto','2,2','',1),'duplicate link rejected');
$manifest=simplexml_load_file($root.'minimarket.xml');
$ok($manifest!==false&&version_compare((string)$manifest->version,'0.6.25','>='),
 'component version');
$dashboard=file_get_contents($root.'admin/tmpl/dashboard/default.php');
$groups=[
 'management'=>['workqueue','notifications','orders','deliveries','returns','payments','fiscalization','customers','tickets','documents'],
 'reports'=>['businessreport','salesreport','stockreport','returnsreport','planfactreport'],
 'shop'=>['categories','products','bundles','groups','bundlegroups','brands','collections','attributes','orderoptions'],
 'marketing'=>['autodiscounts','promocodes','marketingflows','recommendations','abandonedcarts','reviews','seofilters'],
 'tools'=>['import','feeds'],
 'settings'=>['deliverymethods','paymentmethods','registrationmethods','notificationmethods','notificationevents','messagetemplates','fiscalproviders','settings']
];
$expected=[];
foreach($groups as $group=>$views){
 $expected=array_merge($expected,$views);
 $start=strpos($dashboard,"'type' => '".$group."'");
 $ok($start!==false,'dashboard section '.$group);
 $tail=substr($dashboard,$start);
 preg_match_all("/\\['view' => '([a-z]+)'/",$tail,$found);
 $ok(array_slice($found[1],0,count($views))===$views,'dashboard sorted '.$group);
}
$xml=(string)file_get_contents($root.'minimarket.xml');
$preset=(string)file_get_contents($root.'admin/presets/minimarket.xml');
$xmlViews=[];$presetViews=[];
preg_match_all('/<menu link="[^"]*view=([a-z]+)"[^>]*>/',$xml,$a);
preg_match_all('/<menuitem title="[^"]+" type="component"[^>]*link="[^"]*view=([a-z]+)"[^>]*\\/>/',$preset,$b);
$xmlViews=$a[1];$presetViews=$b[1];
$ok($xmlViews===array_slice($expected,0,-1)
 &&str_contains($xml,'view=component&amp;component=com_minimarket'),
 'sidebar ordered correctly');
$ok(array_slice($presetViews,1)===array_slice($expected,0,-1)
 &&str_contains($preset,'view=component&amp;component=com_minimarket')
 &&!str_contains($preset,'undefined'),
 'component preset ordered correctly');
foreach($xmlViews as $view)
 $ok((bool)preg_match('/<menu link="[^"]*view='.$view.'" img="class:[^"]+">/',$xml),
  'sidebar icon '.$view);
foreach($expected as $view)
 $ok((bool)preg_match('/<menuitem[^>]+icon="[^"]+"[^>]+link="[^"]*view='
   .($view==='settings'?'component':$view).'[^"]*"/',$preset),
  'preset icon '.$view);
foreach(['category','product'] as $kind){
 $form=file_get_contents($root.'admin/forms/'.$kind.'.xml');
 $tpl=file_get_contents($root.'admin/tmpl/'.$kind.'/edit.php');
 foreach(['recommend_similar','recommend_together'] as $tab)
  $ok(str_contains($form,$tab)&&str_contains($tpl,$tab),'native '.$kind.' tab '.$tab);
}
$engine=file_get_contents($root.'site/src/Service/ProductRecommendations.php');
$ok(str_contains($engine,'CategoryRecommendationRules::effective')
 &&str_contains($engine,'attributeScores')
 &&str_contains($engine,'getVisibleRecommendationsByIds')
 &&str_contains($engine,'getProductsForCategory'),
 'category rules drive real storefront candidates');
$model=file_get_contents($root.'admin/src/Model/CategoryModel.php');
$ok(str_contains($model,'CategoryRecommendationRules::save')
 &&str_contains($model,'transactionCommit'),'category edit atomic');
$productModel=file_get_contents($root.'admin/src/Model/ProductModel.php');
$ok(str_contains($productModel,'RecommendationSettings::save')
 &&str_contains($productModel,'transactionCommit'),'product override atomic');
foreach(['site','admin'] as $area){
 $keys=[];
 foreach(['ru-RU','en-GB'] as $lang){
  $ini=$root.$area.'/language/'.$lang.'/com_minimarket.ini';
  $strings=parse_ini_file($ini);
  $ok(is_array($strings),'valid ini '.$area.' '.$lang);
  $names=array_keys($strings);
  sort($names);
  $keys[$lang]=$names;
 }
 $ok($keys['ru-RU']===$keys['en-GB'],'locale parity '.$area);
}
$ru=parse_ini_file($root.'site/language/ru-RU/com_minimarket.ini');
$en=parse_ini_file($root.'site/language/en-GB/com_minimarket.ini');
$ok(($ru['COM_MINIMARKET_CHECKOUT_JOOMLA_LOGIN']??'')==='Войти в аккаунт'
 &&($en['COM_MINIMARKET_CHECKOUT_JOOMLA_LOGIN']??'')==='Sign in to your account',
 'visible checkout login button translated on site');
$stockUi=file_get_contents($root.'admin/tmpl/stockreport/default.php');
$ok(str_contains($stockUi,'filter_xyz')&&str_contains($stockUi,"['xyz']"),
 'XYZ in native stock report');
$ddl=file_get_contents($root.'admin/sql/updates/mysql/0.6.25.sql');
$install=file_get_contents($root.'admin/sql/install.mysql.sql');
$ok(str_contains($ddl,'#__minimarket_category_recommendations')
 &&str_contains($install,'#__minimarket_category_recommendations'),'fresh and upgrade DDL');
echo "MM625_NAVIGATION_RECOMMENDATIONS_PASS\n";
