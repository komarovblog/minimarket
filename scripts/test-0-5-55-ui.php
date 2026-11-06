<?php
/** MiniMarket 0.5.55 Joomla form, search, pricing and import UI contracts. */
declare(strict_types=1);
namespace Joomla\CMS\Language {
 final class Text {
  public static function _(string $key):string{return $key;}
  public static function sprintf(string $key,mixed ...$a):string{return $key.':'.implode(',',$a);}
 }
}
namespace MiniMarket\Regression55 {
 define('_JEXEC',1);
 require __DIR__.'/../src/com_minimarket/admin/src/Service/ProductBadges.php';
 use MiniMarket\Component\Minimarket\Administrator\Service\ProductBadges;
 $checks=0;
 function check(bool $condition,string $label):void {
  global $checks; ++$checks;
  if(!$condition)throw new \RuntimeException('MM_055_FAIL '.$label);
 }
 $dir=__DIR__.'/../src/com_minimarket/';
 $xml=simplexml_load_file($dir.'admin/forms/product.xml');
 check($xml!==false,'valid XML');
 check(count($xml->xpath("//field[@name='product_type' and @type='hidden']"))===1,'hidden structural type');
 // Historical checks updated when 0.5.56 moved the format to component settings.
 check(count($xml->xpath("//field[@name='delivery_type']"))===0,'no per-product delivery format');
 $edit=file_get_contents($dir.'admin/tmpl/product/edit.php');
 $indexes=array_map(static fn($v)=>strpos($edit,$v),
  ["'product_type','sku'","renderField('bundle_price_mode')",
   "renderField('price')","renderField('old_price')",
   "renderField('bundle_discount_pct')"]);
 check(!in_array(false,$indexes,true) && $indexes===array_values($indexes)
  && $indexes===array_values(array_unique($indexes))
  && $indexes[0]<$indexes[1] && $indexes[1]<$indexes[2]
  && $indexes[2]<$indexes[3] && $indexes[3]<$indexes[4],
  'bundle price method appears directly after SKU');
 $f=array_map(static fn($v)=>strpos($edit,"renderField('badge_".$v."')"),
  ['benefit','sale','new','popular']);
 check(!in_array(false,$f,true) && $f[0]<$f[1] && $f[1]<$f[2] && $f[2]<$f[3],
  'Savings Sale New Popular order');
 check(str_contains($edit,"if (\$isBundle) echo \$this->form->renderField('badge_benefit')"),
  'simple goods skip Savings flag');
 check(str_contains($edit,"unset(\$sections[\$isBundle?'variants':'bundle'])"),
  'normal products and bundles have separate tabs');
 $view=file_get_contents($dir.'admin/src/View/Product/HtmlView.php');
 check(str_contains($view,'COM_MINIMARKET_BUNDLE_EDIT_TITLE'),'bundle header');
 $picker=file_get_contents($dir.'admin/src/Controller/ProductpickerController.php');
 $segment=substr($picker,strpos($picker,'function bundleCandidates()'));
 $segment=substr($segment,0,strpos($segment,'public function group()'));
 $before=strpos($segment,'$candidateRows=(array)$db->loadAssocList()');
 $after=strpos($segment,'$priceSnapshot=BundlePrice::snapshot($db)');
 check($before!==false && $after!==false && $before<$after,
  'search candidates fetched BEFORE price snapshot resets database query');
 check(str_contains($segment,'foreach ($candidateRows as $row)'),
  'search uses fetched rows');
 $js=file_get_contents($dir.'media/js/product-badges.js');
 check(str_contains($js,"[[price,!discounted],[old,!discounted],[pct,discounted]]")
  && str_contains($js,'sale.disabled=!eligible')
  && str_contains($js,'benefit.disabled=!discounted'),
  'fixed price and auto discounts mutually exclusive');
 $table=file_get_contents($dir.'admin/src/Table/ProductTable.php');
 check(str_contains($table,"['physical','digital']") &&
  str_contains($table,'COM_MINIMARKET_BADGE_SALE_FIXED_ONLY'),
  'server validates delivery and bundle sale modes');
 $bundle=(object)['price'=>'180.00','old_price'=>'200.00','product_type'=>'bundle',
  'bundle_price_mode'=>'discount','badge_sale'=>1,'badge_benefit'=>1,
  'bundle_discount_amount'=>'45.60'];
 $badge=ProductBadges::forProduct($bundle);
 check($badge['sale']===null && $badge['old_price']===null
  && $badge['benefit_amount']==='45.60',
  'discount bundle only shows money Savings');
 $bundle->bundle_price_mode='fixed';
 $badge=ProductBadges::forProduct($bundle);
 check($badge['sale']===10 && $badge['sale_amount']==='20.00'
  && $badge['benefit_amount']===null,'fixed-price bundle only shows Sale');
 $import=file_get_contents($dir.'admin/tmpl/import/default.php');
 check(strpos($import,'COM_MINIMARKET_IMPORT_TITLE_ONLY')<
  strpos($import,'task=import.preview') &&
  strpos($import,'task=import.preview')<
  strpos($import,'COM_MINIMARKET_EXPORT_TITLE'),
  'import upload inside first card, export is second card');
 check(str_contains($import,'task=import.example') &&
  str_contains($import,'task=import.apply') &&
  str_contains($import,'task=import.cancel') &&
  str_contains($import,'mm-import-format'),'template/preview/apply remain functional');
 check(str_contains(file_get_contents($dir.'media/css/import-layout.css'),'min-width: 6.25rem'),
  'equal width format buttons');
 foreach(['ru-RU','en-GB'] as $lang){
  $admin=file_get_contents($dir.'admin/language/'.$lang.'/com_minimarket.ini');
  $sys=file_get_contents($dir.'admin/language/'.$lang.'/com_minimarket.sys.ini');
  foreach(['PRODUCT_DELIVERY_DIGITAL','PRODUCT_DELIVERY_PHYSICAL',
   'BUNDLE_EDIT_TITLE','IMPORT_COMPACT_HELP','BADGE_SALE_FIXED_ONLY'] as $key){
   check(str_contains($admin,'COM_MINIMARKET_'.$key.'='),
    'missing translated '.$lang.' '.$key);
  }
  check(str_contains($sys,'COM_MINIMARKET_IMPORT_TITLE='),
   'import/export menu title '.$lang);
 }
 $migration=file_get_contents($dir.'admin/sql/updates/mysql/0.5.55.sql');
 check(str_contains($migration,'delivery_type') &&
  !preg_match('/\bDROP\b|\bDELETE\b|\bTRUNCATE\b/i',$migration),
  'additive migration');
 $fresh=file_get_contents($dir.'admin/sql/install.mysql.sql');
 check(str_contains($fresh,"delivery_type") &&
  str_contains($fresh,"DEFAULT 'physical'"), 'fresh install physical default');
 $sql=file_get_contents(__DIR__.'/../sample-data/minimarket_digital_demo_0_5_55.sql');
 check(str_contains($sql,'DIGITAL-STEM-1200') &&
  str_contains($sql,'NOT EXISTS') &&
  !preg_match('/\bDROP\b|\bDELETE\b|\bTRUNCATE\b/i',$sql),
  'optional unpublished digital demo never deletes existing data');
 echo 'MM_055_OK '.$checks." tests\n";
}
