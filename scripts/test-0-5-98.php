<?php
/** MiniMarket 0.5.98: visible settings must have real product-page consumers. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$n=0;
$check=static function(bool $ok,string $name) use (&$n):void {
    ++$n;if(!$ok)throw new RuntimeException('MM98_FAIL: '.$name);
};
$config=simplexml_load_file($root.'admin/config.xml');
$check($config!==false,'Joomla XML parses');
$base=$config->xpath('//fieldset[@name="basic"]/field');
$names=array_map(static fn($f)=>(string)$f['name'],$base);
$check(end($names)==='offcanvas_menu_target','command controls last in Basic');
$check(count($config->xpath('//field[@name="mm_commands_note"]'))===1,'one commands informer');
$check(count($config->xpath('//field[@name="category_children_per_page"]'))===0,'no child category pagination setting');
$check(count($config->xpath('//field[@name="mm_price_section" or @name="mm_sku_section" or @name="mm_stock_section"]'))===0,'no duplicate attribute headings');
foreach([
  'price'=>['filter_price_mode','system_price_display_location'],
  'sku'=>['filter_sku_mode','system_sku_display_location'],
  'stock'=>['filter_stock_enabled','system_stock_display_location']
] as $name=>$pair){
    $first=array_search($pair[0],$names,true);
    $second=array_search($pair[1],$names,true);
    $check($first!==false && $second===$first+1,'two settings for '.$name);
}
foreach(['buyer','delivery','payment'] as $step){
    $path='checkout_'.$step.'_description';
    $check(count($config->xpath('//fieldset[@name="checkout"]/field[@name="'.$path.'" and @type="editor" and @filter="safehtml"]'))===1,
      'safe HTML editor '.$step);
}
foreach(['product_detail_layout','product_detail_show_similar',
    'product_detail_show_bought_together','product_detail_gallery_effect',
    'product_detail_sticky_bar','product_detail_section_nav'] as $name){
    $check(count($config->xpath('//fieldset[@name="display"]/field[@name="'.$name.'"]'))===1,
      'product-page option '.$name);
}
$authOptions=$config->xpath('//field[@name="checkout_auth_mode"]/option');
$check(count($authOptions)===2,'existing compatible auth modes');
foreach(['ru-RU','en-GB'] as $lang){
    $admin=(string)file_get_contents($root.'admin/language/'.$lang.'/com_minimarket.ini');
    $site=(string)file_get_contents($root.'site/language/'.$lang.'/com_minimarket.ini');
    $check(str_contains($admin,'COM_MINIMARKET_PRODUCT_DETAIL_LAYOUT=')
        &&str_contains($admin,'COM_MINIMARKET_CHECKOUT_AUTH_EMAIL=')
        &&str_contains($site,'COM_MINIMARKET_PRODUCT_SIMILAR_TITLE=')
        &&str_contains($site,'COM_MINIMARKET_PRODUCT_BOUGHT_TOGETHER_TITLE='),
        'both locales describe features '.$lang);
}
$categoryView=(string)file_get_contents($root.'site/src/View/Category/HtmlView.php');
$categoryTemplate=(string)file_get_contents($root.'site/tmpl/category/default.php');
$check(str_contains($categoryView,'getChildCategories')
    &&!str_contains($categoryView,'childPageCount')
    &&!str_contains($categoryTemplate,'mm_child_page'),
    'all published direct child categories shown');
$productView=(string)file_get_contents($root.'site/src/View/Product/HtmlView.php');
$product=(string)file_get_contents($root.'site/tmpl/product/default.php');
$consumer=(string)file_get_contents($root.'site/src/Service/ProductRecommendations.php');
$catalog=(string)file_get_contents($root.'site/src/Service/CatalogRepository.php');
$check(str_contains($productView,'product_detail_show_similar')
    &&str_contains($productView,'product_detail_show_bought_together')
    &&str_contains($productView,'reviews_enabled')
    &&str_contains($productView,'product_detail_sticky_bar')
    &&str_contains($productView,'product_detail_section_nav'), 'options wired to product view');
$check(str_contains($product,'data-mm-product-tab')
    &&str_contains($product,'data-mm-tab-panel="reviews"')
    &&str_contains($product,'data-mm-section-link')
    &&str_contains($product,'data-mm-product-sticky'), 'real product-page UI regions');
$check(str_contains($consumer,"o.status IN ('confirmed','completed')")
    &&str_contains($consumer,'o.deleted_at IS NULL')
    &&str_contains($catalog,'getVisibleRecommendationsByIds')
    &&str_contains($catalog,'catalogListing()'), 'order-based suggestions with public availability');
$checkout=(string)file_get_contents($root.'site/tmpl/checkout/default.php');
$check(substr_count($checkout,"InputFilter::getInstance()->clean(\$this->")>=3,
    'all three checkout step HTML descriptions safely rendered');
$js=(string)file_get_contents($root.'media/js/product-page.js');
$check(str_contains($js,'form.requestSubmit(originalButton)')
    &&str_contains($js,'selectTab(')
    &&str_contains($js,'sticky.hidden = !past'), 'sticky bar posts original form and tab JS progressive');
$check(!is_file($root.'admin/sql/updates/mysql/0.5.98.sql'),
    'no unnecessary schema migration for component options');
echo "MM98_PASS: $n checks\n";
