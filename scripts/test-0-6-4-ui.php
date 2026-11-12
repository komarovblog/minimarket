<?php
/** MiniMarket 0.6.4: UI, schema and actual category card config. */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/site/src/Service/CategoryDisplayOptions.php';
use MiniMarket\Component\Minimarket\Site\Service\CategoryDisplayOptions as Display;
$root=__DIR__.'/../src/com_minimarket/';
$n=0;
$check=static function(bool $condition,string $name)use(&$n):void{
    ++$n;
    if(!$condition)throw new RuntimeException('MM064_UI_FAIL: '.$name);
};
$manifest=simplexml_load_file($root.'minimarket.xml');
$check((string)$manifest->version==='0.6.4','component version');
$module=simplexml_load_file(__DIR__.'/../src/mod_minimarket_toolbar/mod_minimarket_toolbar.xml');
$check((string)$module->version==='0.6.3','one-time module remains at 0.6.3');
$schema=file_get_contents($root.'admin/sql/install.mysql.sql');
$migration=file_get_contents($root.'admin/sql/updates/mysql/0.6.4.sql');
$form=simplexml_load_file($root.'admin/forms/category.xml');
$globalForm=simplexml_load_file($root.'admin/config.xml');
foreach(['xs','sm','md','lg','xl','xxl'] as $size){
    $key='subcategory_cards_'.$size;
    $check(str_contains($schema,$key)&&str_contains($migration,$key),'schema '.$key);
    $check(count($form->xpath('//field[@name="'.$key.'"]'))===1,'category override '.$key);
    $check(count($globalForm->xpath('//field[@name="'.$key.'"]'))===1,'global setting '.$key);
}
$check(!preg_match('/\b(DROP|TRUNCATE|DELETE)\b/i',$migration),'migration is additive');
$global=new class {
 public function get(string $name,mixed $default=null):mixed {
    return ['category_product_layout'=>'list','subcategory_cards_xs'=>2,
            'subcategory_cards_lg'=>5][$name]??$default;
 }
};
$category=(object)['product_layout'=>'list','subcategory_cards_xs'=>-1,'subcategory_cards_lg'=>3];
$resolved=Display::resolve($category,$global);
$check($resolved['subcategory_cards_xs']===2&&$resolved['subcategory_cards_lg']===3,
       'grid inherited independently of product list');
$classes=Display::subcategoryGridClasses($resolved);
$check(str_contains($classes,'row-cols-2')&&str_contains($classes,'row-cols-lg-3'),
       'real Bootstrap class output');
$site=file_get_contents($root.'site/tmpl/category/default.php');
$check(str_contains($site,'subcategoryGridClasses')&&str_contains($site,'card card-body h-100'),
       'subcategory card presentation');
foreach(['Categoryattributes','Categoryorderoptions'] as $kind) {
    $source=file_get_contents($root.'admin/src/Field/'.$kind.'Field.php');
    $check(str_contains($source,'use MiniMarket\Component\Minimarket\Administrator\Service\CategoryListVisibility;'),
           'missing category field import '.$kind);
}
$catList=file_get_contents($root.'admin/tmpl/categories/default.php');
$check(!str_contains($catList,'CATEGORY_COUNT_BRANCH'),'branch column gone');
$options=simplexml_load_file($root.'admin/forms/orderoption.xml');
$attributes=simplexml_load_file($root.'admin/forms/attribute.xml');
$check(count($options->xpath('//fieldset[@name="filter"]'))===1,'option filter tab');
foreach(['filter_mode','filter_multiple','filter_search'] as $key){
    $check(count($options->xpath('//field[@name="'.$key.'" and @type="list"]'))===1,
           'option filter select '.$key);
    $check(str_contains($schema,$key)&&str_contains($migration,$key),
           'persisted option filter '.$key);
}
foreach(['filter_multiple','filter_search'] as $key)
    $check(count($attributes->xpath('//field[@name="'.$key.'" and @type="list"]'))===1,
           'attribute filter select '.$key);
$facetSource=file_get_contents($root.'site/src/Service/FacetedCatalog.php');
$check(str_contains($facetSource,'filter_search')&&str_contains($facetSource,'filter_multiple'),
       'SKU facet configuration read by storefront');
foreach(['Attribute','Orderoption'] as $kind){
    $view=file_get_contents($root.'admin/src/View/'.$kind.'/HtmlView.php');
    $check(str_contains($view,'compact-values.js'),'compact values JS loaded '.$kind);
}
$check(is_file($root.'media/js/compact-values.js')&&is_file($root.'media/css/compact-values.css'),
       'compact row assets');
$picker=file_get_contents($root.'admin/tmpl/shared/reference-picker.php');
$check(str_contains($picker,'COM_MINIMARKET_REF_CHANGE')&&str_contains($picker,'COM_MINIMARKET_REF_EDIT'),
       'single reference actions');
$review=file_get_contents($root.'admin/tmpl/review/create.php');
$check(str_contains($review,'edit_url_prefix'),'review product is editable');
foreach(['ru-RU','en-GB'] as $lang){
 $word=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 $sys=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
 foreach(['COM_MINIMARKET_TAB_MAIN','COM_MINIMARKET_REF_CHANGE',
          'COM_MINIMARKET_SUBCATEGORY_GRID_HEADING','COM_MINIMARKET_VALUE_NEW'] as $key)
   $check(isset($word[$key]),$lang.' '.$key);
 $check(isset($sys['COM_MINIMARKET_MENUS_PRESET_MINIMARKET']),
        $lang.' preset translation');
}
$release=file_get_contents(__DIR__.'/release-to-desktop.ps1');
$check(str_contains($release,'MINIMARKET_DESKTOP_2_FILES_OK'),'one-time module not reissued');
echo 'MM064_UI_PASS: '.$n." checks\n";
