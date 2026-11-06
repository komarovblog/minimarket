<?php
declare(strict_types=1);
define('_JEXEC',1);
$base=__DIR__.'/../src/com_minimarket/';
$checks=0;
function ensure(bool $ok,string $message):void {
    global $checks; ++$checks;
    if(!$ok) throw new RuntimeException('MINIMARKET_052_UI_FAIL: '.$message);
}
$xml=simplexml_load_file($base.'minimarket.xml');
ensure($xml!==false && version_compare((string)$xml->version,'0.5.55','>='),'0.5.52 sidebar contracts preserved in current release');
$menus=$xml->xpath('//administration/submenu/menu');
$views=[];
foreach($menus as $menu) {
    if(isset($menu['link']))$views[]=(string)$menu['link'];
}
ensure(count($xml->xpath('//administration/submenu/menu[@type="separator"]'))===6,
    'native Joomla menu has six divider lines');
foreach(['categories','products','bundles'] as $type) {
    $m=$xml->xpath('//administration/submenu/menu[@link="option=com_minimarket&view='.$type.'"]');
    ensure(count($m)===1 && count($m[0]->xpath('./params/menu-quicktask'))===1,
        'native quick-add beside '.$type);
}
foreach(['groups','bundlegroups','attributes','orderoptions','brands','collections','import'] as $view) {
    ensure(in_array('option=com_minimarket&view='.$view,$views,true),
        'native sidebar link: '.$view);
}
ensure((string)$xml->administration->menu['img']==='class:cart',
    'MiniMarket has shopping-cart component icon');
$form=simplexml_load_file($base.'admin/forms/product.xml');
ensure(count($form->xpath("//field[@name='collection_ids' and @type='Collectionpicker']"))===1,
    'searchable collection picker replaces all-checked checkbox list');
ensure(count($form->xpath("//field[@name='bundle_items' and @type='Bundlepicker']"))===1,
    'bundle uses searchable SKU/product picker');
foreach(['bundle_price_mode','bundle_discount_pct'] as $key) {
    ensure(count($form->xpath("//fieldset[@name='bundle_pricing']/field[@name='".$key."']"))===1,
        'bundle pricing form field '.$key);
}
$js=(string)file_get_contents($base.'media/js/entity-picker.js');
ensure(str_contains($js,'[data-mm-picker]') && str_contains($js,'data-mm-') &&
    str_contains($js,'fetch(') && str_contains($js,'AbortController') &&
    str_contains($js,'replaceChildren') && !str_contains($js,'innerHTML'),
    'safe DOM picker search without HTML injection');
$controller=(string)file_get_contents($base.'admin/src/Controller/ProductpickerController.php');
ensure(str_contains($controller,'function collections()') &&
    str_contains($controller,'function bundleCandidates()') &&
    str_contains($controller,'$this->ensureAllowed();') &&
    str_contains($controller,'$db->setQuery($q,0,25)'),
    '25-item CSRF protected asynchronous search endpoints');
foreach(['Bundles','Bundlegroups'] as $type) {
    foreach(['src/Model/'.$type.'Model.php','src/View/'.$type.'/HtmlView.php',
        'src/Controller/'.$type.'Controller.php'] as $part) {
        ensure(is_file($base.'admin/'.$part),'MVC view for '.$type.' '.$part);
    }
    $plural=strtolower($type);
    ensure(is_file($base.'admin/tmpl/'.$plural.'/default.php'),
        'native list for '.$type);
}
$links=(string)file_get_contents($base.'admin/src/Service/AdminEntityLinks.php');
ensure(str_contains($links,"'bundles', 'bundlegroups'"),
    'links allow both admin list views');
$grp=(string)file_get_contents($base.'admin/src/Service/ProductGroups.php');
ensure(str_contains($grp,"'bundle'") &&
    str_contains($grp,'COM_MINIMARKET_GROUP_SCOPE_INVALID'),
    'separate group scope validates bundle-only membership');
$models=(string)file_get_contents($base.'admin/src/Model/ProductsModel.php');
ensure(str_contains($models,'protected string $entityType') &&
    str_contains($models,'BundlePrice::calculate'),
    'separate product/bundle lists use derived price');
$schema=(string)file_get_contents($base.'admin/sql/updates/mysql/0.5.52.sql');
ensure(str_contains($schema,'bundle_price_mode') &&
    str_contains($schema,'bundle_discount_pct') &&
    !preg_match('/\bDROP\s+TABLE|\bDELETE\b|\bTRUNCATE\b/i',$schema),
    'additive price-mode migration does not destroy product data');
echo 'MINIMARKET_052_UI_OK: '.$checks." cases\n";
