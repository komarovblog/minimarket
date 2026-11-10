<?php
/** MiniMarket 0.5.75: stock-aware catalog, faceted UI and cart interaction gate. */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/site/src/Service/FacetedCatalog.php';
use MiniMarket\Component\Minimarket\Site\Service\FacetedCatalog;
$checks=0;
function v75(bool $ok,string $message):void {
    global $checks;
    ++$checks;
    if (!$ok) throw new RuntimeException('MM75_STOREFRONT_FAILED: '.$message);
}
$root=dirname(__DIR__).'/src/com_minimarket/';
$read=static function(string $path) use ($root):string {
    $v=file_get_contents($root.$path);
    if (!is_string($v)) throw new RuntimeException('Missing: '.$path);
    return $v;
};
$facet=[
  'price'=>['type'=>'number','mode'=>'slider','multiple'=>false,'choices'=>[]],
  'color'=>['type'=>'text','mode'=>'dropdown','multiple'=>true,
    'choices'=>[['value'=>'v:1'],['value'=>'v:2']]],
];
$picked=FacetedCatalog::selection(['price'=>['min'=>'999.00','max'=>'1999.00'],
    'color'=>['v:1','v:2']],$facet);
v75(isset($picked['price']['min']) && $picked['price']['min']===999.0,'price min');
v75($picked['price']['max']===1999.0,'price max');
v75(count($picked['color']['values'])===2,'dropdown OR selection');
v75(FacetedCatalog::matches(['price'=>[1500],'color'=>['v:2']],[],$facet,$picked),'slider AND dropdown');
v75(!FacetedCatalog::matches(['price'=>[2500],'color'=>['v:2']],[],$facet,$picked),'range excludes expensive products');
$facets=$read('site/src/Service/FacetedCatalog.php');
v75(str_contains($facets,"'price'=>") || str_contains($facets,"'price'"),'native price facet');
$filters=$read('site/tmpl/category/filters.php');
v75(str_contains($filters,'mm-facet-dropdown') && str_contains($filters,'data-mm-choice-search'),'searchable dropdowns');
v75(str_contains($filters,"'checkbox'") && str_contains($filters,'data-mm-facet-multiple'),'single/multiple dropdown checkboxes');
v75(str_contains($filters,'data-mm-choice-count') && str_contains($filters,'data-mm-facet-group-count'),'facets display counts');
$frontend=$read('media/js/faceted-catalog.js');
v75(str_contains($frontend,'[data-mm-choice-search]'),'option search is live');
v75(str_contains($frontend,'dataset.mmFacetMultiple'),'exclusive checkbox behavior');
$category=$read('site/tmpl/category/default.php');
v75(str_contains($category,'data-mm-sort'),'category sorting shown above products');
$catalog=$read('site/tmpl/catalog/default.php');
v75(str_contains($catalog,'mm-root-sort'),'catalog sorting shown');
$repo=$read('site/src/Service/CatalogRepository.php');
v75(str_contains($repo,'mmv.stock>0'),'positive SKU stock in listing query');
v75(str_contains($repo,'filterListingRows'),'derived bundle stock in listing');
v75(str_contains($repo,'getProduct(int $id)'),'direct SEO product URL remains');
$view=$read('site/src/View/Product/HtmlView.php');
v75(str_contains($view,'$this->outOfStock') && str_contains($view,'getAllProductsPage(0,24'),'out of stock analogs');
$cart=$read('site/src/Controller/CartController.php');
v75(str_contains($cart,'maxQuantity(') && str_contains($cart,'COM_MINIMARKET_CART_CLAMPED'),'server clamps quantity');
$cartPage=$read('site/tmpl/cart/default.php');
v75(str_contains($cartPage,'data-mm-cart-quantity-form')
  && str_contains($cartPage,'<noscript>'),'auto quantity update has no-JS fallback');
$cartScript=$read('media/js/cart.js');
v75(str_contains($cartScript,'requestSubmit()') && str_contains($cartScript,'Math.min(limit'),
  'automatic quantity update clamps client input');
$ru=parse_ini_file($root.'site/language/ru-RU/com_minimarket.ini');
$en=parse_ini_file($root.'site/language/en-GB/com_minimarket.ini');
foreach ([$ru,$en] as $lang) {
    foreach (['COM_MINIMARKET_SORT_LABEL','COM_MINIMARKET_FILTER_PRICE',
        'COM_MINIMARKET_ALTERNATIVES','COM_MINIMARKET_CART_CLAMPED'] as $key) {
        v75(is_array($lang) && isset($lang[$key]),'translation '.$key);
    }
}
$manifest=$read('minimarket.xml');
v75(str_contains($manifest,'<version>0.5.76</version>'),'version 0.5.75');
$update=$read('admin/sql/updates/mysql/0.5.75.sql');
v75(str_contains($update,'SELECT 1;') && !str_contains($update,'DROP TABLE'),'no destructive schema SQL');
$demo=file_get_contents(dirname(__DIR__).'/sample-data/minimarket_demo_gallery_filters_0_5_75.sql');
v75(is_string($demo) && str_contains($demo,'mm-demo-weight') && str_contains($demo,'sample-10.png'),'additive facet/gallery demo');
echo "MM75_STOREFRONT_PASS: $checks checks\n";
