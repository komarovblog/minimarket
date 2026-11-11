<?php
/** Isolated regression for inherited category layout, safe grid options and descendants. */
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__.'/../src/com_minimarket/site/src/Service/CategoryTree.php';
require __DIR__.'/../src/com_minimarket/site/src/Service/CategoryDisplayOptions.php';
use MiniMarket\Component\Minimarket\Site\Service\CategoryDisplayOptions as Options;
use MiniMarket\Component\Minimarket\Site\Service\CategoryTree;
$checks=0;
function check58(bool $ok,string $what):void { global $checks; $checks++; if(!$ok)throw new RuntimeException('MM58_FAIL: '.$what); }
$global=new class {
    public array $values=[];
    public function get(string $name,mixed $default=null):mixed { return $this->values[$name]??$default; }
};
$category=(object)[];
$v=Options::resolve($category,$global);
check58($v['show_children'] && $v['show_products'] && $v['show_bundles'],
 'legacy own-category behavior stays visible by default');
check58(!$v['show_descendant_products'] && !$v['show_descendant_bundles'],
 'legacy descendants stay excluded by default');
check58($v['layout']==='grid','new default uses the responsive product grid');
check58(Options::bootstrapGridClasses($v)==='row row-cols-1 row-cols-sm-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-4 g-3',
 'bootstrap grid has safe mobile to xl defaults');
$global->values=['category_show_children'=>0,'category_show_products'=>0,
    'category_show_bundles'=>1,'category_show_descendant_products'=>1,
    'category_show_descendant_bundles'=>1,'category_product_layout'=>'grid',
    'category_cards_xs'=>2,'category_cards_sm'=>2,'category_cards_md'=>3,
    'category_cards_lg'=>4,'category_cards_xl'=>6,'category_cards_xxl'=>6];
$v=Options::resolve($category,$global);
check58(!$v['show_children'] && !$v['show_products'] && $v['show_bundles'],
 'global flags resolved independently');
check58($v['show_descendant_products'] && $v['show_descendant_bundles'],
 'global nested products and kits work independently');
check58($v['layout']==='grid' && $v['cards_xxl']===6,'global responsive layout');
$category=(object)['show_children'=>1,'show_products'=>1,'show_bundles'=>0,
    'show_descendant_products'=>0,'show_descendant_bundles'=>-1,
    'product_layout'=>'list','cards_xs'=>1,'cards_sm'=>-1,'cards_md'=>-1,
    'cards_lg'=>5,'cards_xl'=>-1,'cards_xxl'=>-1];
$v=Options::resolve($category,$global);
check58($v['show_children'] && $v['show_products'] && !$v['show_bundles'],
 'local category flag overrides global');
check58(!$v['show_descendant_products'] && $v['show_descendant_bundles'],
 'each descendant setting inherits or overrides separately');
check58($v['layout']==='list' && $v['cards_xs']===2 && $v['cards_sm']===2
    && $v['cards_md']===3 && $v['cards_lg']===4
    && $v['cards_xl']===6 && $v['cards_xxl']===6,
    'List disables local grid overrides and retains global defaults');
$category->product_layout='grid';
$v=Options::resolve($category,$global);
check58($v['cards_xs']===1 && $v['cards_lg']===5,
    'Grid activates previously saved independent local counts');
$category->product_layout='inherit';$category->cards_lg=-1;
$global->values['category_cards_lg']=99;
$v=Options::resolve($category,$global);
check58($v['layout']==='grid' && $v['cards_lg']===6,'global unsafe number clamped');
$global->values['category_product_layout']='unsupported';
$v=Options::resolve($category,$global);
check58($v['layout']==='grid','unexpected global layout safely falls back to grid');
$tree=new CategoryTree([
 (object)['id'=>1,'parent_id'=>0,'state'=>1],
 (object)['id'=>2,'parent_id'=>1,'state'=>1],
 (object)['id'=>3,'parent_id'=>2,'state'=>1],
 (object)['id'=>4,'parent_id'=>1,'state'=>0],
 (object)['id'=>5,'parent_id'=>4,'state'=>1],
 (object)['id'=>6,'parent_id'=>1,'state'=>1],
 (object)['id'=>7,'parent_id'=>9,'state'=>1],
 (object)['id'=>9,'parent_id'=>7,'state'=>1],
]);
check58($tree->descendantIds(1)===[2,3,6],'nested, visible descendants only');
check58($tree->descendantIds(4)===[] && $tree->descendantIds(7)===[],
 'hidden and cyclic ancestors excluded');
$root=__DIR__.'/../src/com_minimarket/';
$config=simplexml_load_file($root.'admin/config.xml');
$form=simplexml_load_file($root.'admin/forms/category.xml');
check58($config!==false && $form!==false,'config and category XML parse');
check58(count($config->xpath('//fieldset[@name="display"]'))===1
    && count($config->xpath('//fieldset[@name="basic"]'))===1
    && count($config->xpath('//fieldset[@name="registration"]'))===0,
    'display and general settings without duplicate registration tab');
foreach(['category_show_children','category_show_products','category_show_bundles',
 'category_show_descendant_products','category_show_descendant_bundles','category_product_layout',
 'category_cards_xs','category_cards_sm','category_cards_md','category_cards_lg','category_cards_xl','category_cards_xxl'] as $key){
 check58(count($config->xpath('//fieldset[@name="display"]/field[@name="'.$key.'"]'))===1,
  'global category field '.$key);
}
foreach(['show_descendant_products','show_descendant_bundles','product_layout',
 'cards_xs','cards_sm','cards_md','cards_lg','cards_xl','cards_xxl'] as $key){
 check58(count($form->xpath('//fieldset[@name="display"]/field[@name="'.$key.'"]'))===1,
  'category override field '.$key);
}
$repo=file_get_contents($root.'site/src/Service/CatalogRepository.php');
check58(str_contains($repo,'descendantIds($id)') && str_contains($repo,"$"."descendantProducts"),
 'repository includes published descendant filter');
$view=file_get_contents($root.'site/src/View/Category/HtmlView.php');
check58(str_contains($view,'CategoryDisplayOptions::resolve') &&
 str_contains($view,"$"."display['show_descendant_products']"),
 'view applies independent global/category switches');
$template=file_get_contents($root.'site/tmpl/category/default.php');
check58(str_contains($template,'bootstrapGridClasses') &&
 str_contains($template,'data-mm-category-layout'),'template renders responsive layout');
foreach(['ru-RU','en-GB'] as $lang){
 $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 check58(isset($ini['COM_MINIMARKET_CONFIG_CATEGORIES']) &&
  isset($ini['COM_MINIMARKET_GLOBAL_DESCENDANT_BUNDLES']) &&
  isset($ini['COM_MINIMARKET_CARDS_XXL']),'translated category options '.$lang);
}
echo "MM_058_CATEGORY_OPTIONS_OK: $checks checks\n";
