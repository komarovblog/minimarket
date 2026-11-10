<?php
/** MiniMarket 0.5.76: filter presentation, choice multiplicity, global/category settings. */
declare(strict_types=1);
define('_JEXEC',1);
require dirname(__DIR__).'/src/com_minimarket/admin/src/Service/AttributeSettings.php';
require dirname(__DIR__).'/src/com_minimarket/site/src/Service/CategoryDisplayOptions.php';
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings as Settings;
use MiniMarket\Component\Minimarket\Site\Service\CategoryDisplayOptions as Options;
$checks=0;
function check76(bool $value,string $message):void {
    global $checks;
    $checks++;
    if (!$value) throw new RuntimeException('MM76_FILTER_FAIL: '.$message);
}
$text=(object)['input_type'=>'text','value_source'=>'preset','filter_mode'=>'checkboxes',
  'filter_multiple'=>0,'filter_search'=>1,'is_multiple'=>1,'is_filterable'=>0];
check76(Settings::normalize($text)===null,'text filter valid');
check76($text->filter_multiple===0,'checkbox appearance does not force multi choice');
check76($text->filter_search===1,'search is independent');
check76($text->is_filterable===1,'visible filter automatically enabled');
$multi=clone $text;$multi->filter_mode='dropdown';$multi->filter_multiple=1;
check76(Settings::normalize($multi)===null && $multi->filter_multiple===1,
  'dropdown supports multiple values');
$none=clone $multi;$none->filter_mode='none';
check76(Settings::normalize($none)===null && $none->filter_search===0 &&
  $none->filter_multiple===0 && $none->is_filterable===0,'none disables public filter');
$boolean=(object)['input_type'=>'boolean','value_source'=>'manual',
   'filter_mode'=>'checkboxes','filter_multiple'=>1,'filter_search'=>1,'is_filterable'=>1];
check76(Settings::normalize($boolean)===null,'boolean list valid');
check76($boolean->filter_multiple===0 && $boolean->filter_search===0,'boolean always single no search');
$numeric=(object)['input_type'=>'number','value_source'=>'manual',
 'filter_mode'=>'slider','filter_multiple'=>1,'filter_search'=>1,'number_min'=>'0',
 'number_max'=>'100','number_step'=>'1','is_filterable'=>1];
check76(Settings::normalize($numeric)===null,'number slider valid');
check76($numeric->filter_multiple===0 && $numeric->filter_search===0,'range nonmultiselect nonsearchable');
$wrong=clone $numeric;$wrong->filter_mode='text';
check76(Settings::normalize($wrong)!==null,'numeric text mode refused');
$global=new class {
 public function get(string $key,mixed $default=null):mixed {
  return [
   'category_products_per_page'=>20,
   'filter_price_mode'=>'range','filter_sku_mode'=>'text',
   'filter_stock_enabled'=>1,
  ][$key]??$default;
 }
};
$category=(object)[
 'products_per_page'=>0,'filter_price_mode'=>'inherit',
 'filter_sku_mode'=>'none','filter_stock_mode'=>-1,'product_layout'=>'inherit'
];
$resolved=Options::resolve($category,$global);
check76($resolved['products_per_page']===20,'global page-size inheritance');
check76($resolved['filter_price_mode']==='range','global price mode inheritance');
check76($resolved['filter_sku_mode']==='none','per-category SKU override');
check76($resolved['filter_stock_enabled']===true,'stock filter inheritance');
$category->products_per_page=50;$category->filter_price_mode='slider';
$overridden=Options::resolve($category,$global);
check76($overridden['products_per_page']===50 &&
 $overridden['filter_price_mode']==='slider','per-category page and price override');
$root=dirname(__DIR__);
$manifest=file_get_contents($root.'/src/com_minimarket/minimarket.xml');
check76(str_contains((string)$manifest,'<version>0.5.79</version>'),'correct version');
$migration=file_get_contents($root.'/src/com_minimarket/admin/sql/updates/mysql/0.5.76.sql');
check76(str_contains((string)$migration,'ADD COLUMN filter_search') &&
 str_contains((string)$migration,'ADD COLUMN products_per_page')
 && !str_contains((string)$migration,'DROP TABLE'),'non-destructive migration');
$field=file_get_contents($root.'/src/com_minimarket/admin/src/Field/ProductattributesField.php');
check76(strpos((string)$field,'$source = (string)') <
 strpos((string)$field,'if ($source ==='), 'undefined source fixed');
echo "MM76_FILTER_SETTINGS_PASS: $checks tests\n";
