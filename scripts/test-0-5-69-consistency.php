<?php
/** MiniMarket 0.5.69 unified admin UI and frozen stock contracts. */
declare(strict_types=1);
$root=dirname(__DIR__);
$n=0;
function ok69(bool $ok,string $label):void {
    global $n;$n++;
    if(!$ok)throw new RuntimeException('MM069_FAIL: '.$label);
}
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$forms=['attribute','category','product','orderoption','group','value','brand','collection'];
foreach($forms as $entity){
    $doc=simplexml_load_string($read('src/com_minimarket/admin/forms/'.$entity.'.xml'));
    ok69($doc!==false,$entity.' valid form XML');
    ok69(count($doc->xpath('/form/fieldset[@name="publishing"]/field[@name="state"]'))===1,
        $entity.' publishes in dedicated tab');
    ok69(count($doc->xpath('/form/fieldset[@name="publishing"]/field[@name="sort_order"]'))===1,
        $entity.' orders in dedicated tab');
    ok69(count($doc->xpath('/form/fieldset[@name="details"]/field[@name="sort_order"]'))===0,
        $entity.' has no sort field in Details');
}
$a=simplexml_load_string($read('src/com_minimarket/admin/forms/attribute.xml'));
foreach(['is_filterable','filter_mode','filter_multiple','filter_planned_notice'] as $field){
    ok69(count($a->xpath('/form/fieldset[@name="filter"]/field[@name="'.$field.'"]'))===1,
        'filter '.$field.' separate tab');
}
ok69(count($a->xpath('/form/fieldset[@name="details"]/field[@name="value_source_ui"]/option[@value="legacy"]'))===0,
    'old compatibility choice absent');
ok69(count($a->xpath('/form/fieldset[@name="details"]/field[@name="value_source" and @type="hidden"]'))===1,
    'hidden value source keeps compatibility storage');
$cfg=$read('src/com_minimarket/admin/config.xml');
ok69(str_contains($cfg,'name="inventory_enabled"') &&
    !str_contains($cfg,'showon="category_product_layout:grid"'),
    'inventory toggle plus visible global device card counts');
$editor=$read('src/com_minimarket/admin/tmpl/product/edit.php');
ok69(strpos($editor,"renderField('option_stock_mode')") <
    strpos($editor,"renderField('stock')",strpos($editor,'} else {')),
    'stock mode shown before simple product quantity');
$stock=$read('src/com_minimarket/media/js/stock-controls.js');
$matrix=$read('src/com_minimarket/media/js/sku-matrix.js');
ok69(str_contains($stock,'copy.value=stock.value') &&
    str_contains($stock,'stock.disabled=freeze') &&
    str_contains($matrix,'field.disabled = !stockTracking || isShared()') &&
    str_contains($matrix,'data-mm-stock-notice'),
    'disabled stock values preserved by mirror and matrix');
$backend=$read('src/com_minimarket/admin/src/Model/ProductModel.php');
ok69(str_contains($backend,'$storedStock = $dbStock->loadResult()') &&
    str_contains($backend,'$inventoryEnabled') &&
    str_contains($read('src/com_minimarket/admin/src/Service/ProductSkuMatrix.php'),'$old'."['stock']"),
    'server protects frozen product and SKU quantities');
$group=$read('src/com_minimarket/media/js/group-editor.js');
ok69(str_contains($group,'group._finderResults = items') &&
    str_contains($group,'paintResults(items)') &&
    str_contains($group,'toolbar.after(addExisting())') &&
    str_contains($group,'mount.prepend(toolbar)'),
    'group picker retains results and uses top Add/search');
$picker=$read('src/com_minimarket/media/js/entity-picker.js');
ok69(str_contains($picker,'root.insertBefore(finder,chosen)') &&
    str_contains($picker,'add.disabled=true;'),
    'shared pickers keep search rows and place finder above selected items');
ok69(str_contains($read('src/com_minimarket/admin/tmpl/import/default.php'),'<th>XLSX</th>') &&
    !str_contains($read('src/com_minimarket/admin/tmpl/import/default.php'),'<th>Excel (XLSX)</th>'),
    'official format name XLSX only');
ok69(str_contains($read('src/com_minimarket/admin/tmpl/feeds/default.php'),'COM_MINIMARKET_FEED_CREATE') &&
    str_contains($read('src/com_minimarket/admin/tmpl/feed/default.php'),"'publishing'"),
    'feed translated create button and publication tab');
foreach(['ru-RU','en-GB'] as $lang){
    $ini=parse_ini_file($root.'/src/com_minimarket/admin/language/'.$lang.'/com_minimarket.ini');
    foreach(['ATTRIBUTE_FILTER_TAB','CONFIG_INVENTORY','CONFIG_INVENTORY_ENABLED',
            'STOCK_DISABLED_NOTICE','STOCK_SHARED_NOTICE','FEED_CREATE'] as $key){
        ok69(isset($ini['COM_MINIMARKET_'.$key]),$lang.' '.$key);
    }
}
ok69(str_contains($read('src/com_minimarket/site/src/Controller/FeedController.php'),
        "'inventory_enabled',1") &&
    str_contains($read('src/com_minimarket/admin/src/Service/FeedYml.php'),'!$inventoryEnabled'),
    'YML availability respects global stock-off');
ok69(str_contains($read('src/com_minimarket/admin/sql/updates/mysql/0.5.69.sql'),
        'ADD COLUMN') &&
    str_contains($read('src/com_minimarket/admin/sql/install.mysql.sql'),
        '`sort_order` INT UNSIGNED NOT NULL DEFAULT 0,'),
    'feed order added through additive schema in upgrade/fresh install');
echo 'MM069_CONSISTENCY_OK: '.$n." checks\n";
