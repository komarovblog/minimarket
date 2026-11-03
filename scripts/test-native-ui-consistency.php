<?php
/** Interface contract: same native Joomla controls in MiniMarket admin pages. */
declare(strict_types=1);
$base=__DIR__.'/../src/com_minimarket/';
$n=0;
function checkUi(bool $condition,string $description):void {
    global $n; $n++;
    if (!$condition) throw new RuntimeException('NATIVE_UI_FAIL: '.$description);
}
$category=file_get_contents($base.'admin/tmpl/categories/default.php');
checkUi(str_contains($category,"joomla.searchtools.default"),'categories share core Joomla SearchTools');
checkUi(str_contains($category,'class="table table-striped"'),'categories use striped table');
checkUi(str_contains($category,'PublishedButton'),'native category publication control');
checkUi(!str_contains($category,'mm-tree-toggle')&&!str_contains($category,'aria-expanded'),
    'all category levels visible, no custom collapse');
checkUi(str_contains($category,'$depth > 0')&&str_contains($category,'>— </span>'),
    'category child hierarchy like Articles categories');
checkUi(str_contains($category,'$editUrl')&&str_contains($category,'<img'),
    'category thumbnails open the editor');
$categoryField=file_get_contents($base.'admin/src/Field/CategoryattributesField.php');
$reverseField=file_get_contents($base.'admin/src/Field/AttributecategoriesField.php');
foreach ([$categoryField,$reverseField] as $source) {
    checkUi(str_contains($source,'table table-striped'),'attribute relation uses a Joomla table');
    checkUi(str_contains($source,'form-select form-select-sm'),'compact native rule select');
    checkUi(str_contains($source,'d-block text-muted'),'inheritance appears as quiet row metadata');
}
$form=simplexml_load_file($base.'admin/forms/product.xml');
$axes=$form->xpath("//fieldset[@name='variants']/field[@name='variant_dimensions' and @type='subform']");
checkUi(count($axes)===1,'axes use native Joomla subform');
checkUi((string)$axes[0]['max']==='4','four option axes supported');
checkUi((string)$axes[0]['buttons']==='add,remove,move','axis add/remove/move matches gallery');
$sku=$form->xpath("//fieldset[@name='variants']/field[@name='variants' and @type='subform']");
checkUi(count($sku)===1,'SKU uses native Joomla subform');
checkUi((string)$sku[0]['layout']==='joomla.form.field.subform.repeatable-table',
    'SKU rows use Joomla table layout, not oversized cards');
$dimension=simplexml_load_file($base.'admin/forms/product_variant_dimension.xml');
$values=$dimension->xpath("//field[@name='values' and @type='subform']");
checkUi(count($values)===1,'option values use nested native Subform');
checkUi((string)$values[0]['buttons']==='add,remove,move',
    'values have same plus/remove/move buttons as gallery');
checkUi((string)$values[0]['layout']==='joomla.form.field.subform.repeatable-table',
    'option values use native table');
$skuForm=simplexml_load_file($base.'admin/forms/product_variant.xml');
checkUi(count($skuForm->xpath("//field[@name='variant_label']"))===1,
    'SKU variant combination clearly named in table');
$editor=file_get_contents($base.'admin/tmpl/product/edit.php');
checkUi(str_contains($editor,"renderField('variant_dimensions')") &&
    str_contains($editor,"renderField('variants')"),'both native forms rendered');
$client=file_get_contents($base.'media/js/variants-local.js');
checkUi(!str_contains($client,"input[name=\"jform[variant_config]\"]"),
    'no old hidden JSON is the source of truth for option values');
checkUi(str_contains($client,'table table-striped table-sm') &&
    str_contains($client,'querySelectorAll'), 'native table for combination preview');
foreach (['categories','products','attributes','values'] as $entity) {
    $list=file_get_contents($base.'admin/tmpl/'.$entity.'/default.php');
    checkUi(str_contains($list,'table table-striped'),
        $entity.': ordinary Joomla striped table');
    checkUi(str_contains($list,'PublishedButton')
        && str_contains($list,"'task_prefix' => '".$entity.".'"),
        $entity.': native publication action wired to bulk controller');
    $head=explode('</thead>',explode('<thead>',$list,2)[1]??'',2)[0];
    $check=strpos($head,'grid.checkall');
    $state=strpos($head,'COM_MINIMARKET_BULK_PUBLISHED');
    $title=strpos($head,'JGLOBAL_TITLE');
    checkUi($check!==false && $state!==false && $title!==false
        && $check<$state && $state<$title,
        $entity.': columns ordered checkbox, publication, title');
}
$skuBuilder=file_get_contents($base.'admin/src/Field/VariantbuilderField.php');
checkUi(str_contains($skuBuilder,'data-mm-add-sku')
    && str_contains($skuBuilder,'data-mm-sku-apply'),
    'manual SKU positions and bulk edit use existing Joomla-native SKU form');
checkUi(str_contains($client,'refreshSkuEditors(axes)')
    && str_contains($client,'data-mm-sku-select'),
    'each native SKU row exposes local option selection and explicit bulk checkbox');
checkUi(str_contains($client, "appendRow({ title: '', selection: {} }, axes, true)"),
    'manual SKU position has no invented selection or SKU code');
checkUi(str_contains($client,"['price', 'stock', 'state']"),
    'copying SKU position excludes identity and personal media by default');
echo 'NATIVE_UI_OK: '.$n." cases\n";
