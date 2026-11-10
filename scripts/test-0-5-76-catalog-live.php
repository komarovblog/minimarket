<?php
/** MiniMarket 0.5.76: root catalog inherits global pagination and sorts via AJAX. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$count=0;
function mm76Catalog(bool $yes,string $name):void {
    global $count; $count++;
    if (!$yes) throw new RuntimeException('MM76_ROOT_CATALOG_FAIL: '.$name);
}
$menu=simplexml_load_file($root.'site/tmpl/catalog/default.xml');
mm76Catalog($menu!==false,'valid Joomla catalog menu XML');
$opts=$menu->xpath("//field[@name='products_per_page']/option");
$values=array_map(static fn($node)=>(string)$node['value'],$opts);
mm76Catalog($values===['0','10','20','30','50'],
    'menu has global inherit and all accepted card counts');
$view=(string)file_get_contents($root.'site/src/View/Catalog/HtmlView.php');
mm76Catalog(str_contains($view,"category_products_per_page")
    && str_contains($view,'[10, 20, 30, 50]'), 'global store limit inherited');
mm76Catalog(str_contains($view,"params?->get('products_per_page', 0)")
    && str_contains($view,'$globalLimit'),
    'local menu page-size override is optional');
mm76Catalog(str_contains($view,'com_minimarket.catalog-live'),
    'root catalog JS is registered');
$template=(string)file_get_contents($root.'site/tmpl/catalog/default.php');
foreach (['data-mm-root-content','data-mm-root-sort-form','data-mm-root-sort',
    'data-mm-root-list','data-mm-root-pages','data-mm-root-next'] as $marker) {
    mm76Catalog(str_contains($template,$marker),'root template marker '.$marker);
}
$js=(string)file_get_contents($root.'media/js/catalog-live.js');
mm76Catalog(str_contains($js,'new DOMParser()')
    && str_contains($js,'history.pushState')
    && str_contains($js,'IntersectionObserver')
    && str_contains($js,'document.importNode'),
    'progressive AJAX sorting and lazy page append');
mm76Catalog(str_contains($js,"window.location.assign(url.toString())"),
    'navigation fallback on fetch failure');
$attrs=(string)file_get_contents($root.'admin/src/Field/ProductattributesField.php');
mm76Catalog(strpos($attrs,'$source = (string)')<strpos($attrs,"if ($source ==="),
    'legacy undefined source warning is fixed');
$filters=(string)file_get_contents($root.'site/tmpl/category/filters.php');
mm76Catalog(str_contains($filters,'data-mm-dual-range')
    && str_contains($filters,'COM_MINIMARKET_FILTER_ALL_VALUES')
    && str_contains($filters,"\$selection[\$boundary]??\$facet[\$boundary]"),
    'two-thumb range, all-values label and initialized input bounds');
echo "MM76_ROOT_CATALOG_PASS: $count checks\n";
