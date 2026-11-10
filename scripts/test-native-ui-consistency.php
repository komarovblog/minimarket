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
checkUi(str_contains($category,'class="table table-striped mm-admin-list"'),
    'categories use native striped list with responsive presentation');
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
checkUi(!str_contains($reverseField, 'padding-inline-start:'),
    'reverse category table uses standard Joomla row padding');
checkUi(str_contains($reverseField, "str_repeat('— '"),
    'category hierarchy uses a text prefix rather than custom table padding');
$attributeSettingsJs=file_get_contents($base.'media/js/attribute-settings.js');
checkUi(str_contains($attributeSettingsJs,'const filters =')
    && str_contains($attributeSettingsJs,'display.replaceChildren(')
    && str_contains($attributeSettingsJs,"['filter_multiple',multipleAllowed]")
    && str_contains($attributeSettingsJs,"['filter_search',searchAllowed]")
    && !str_contains($attributeSettingsJs,'input_mode'),
    'canonical filter modes, selection count and search are independent controls');
$ruUi=file_get_contents($base.'admin/language/ru-RU/com_minimarket.ini');
$enUi=file_get_contents($base.'admin/language/en-GB/com_minimarket.ini');
checkUi(str_contains($ruUi, 'COM_MINIMARKET_MODE_PRESETS="Выбор из заданных значений"')
    && str_contains($enUi, 'COM_MINIMARKET_MODE_PRESETS="Choose preset values"'),
    'text and number share a neutral preset-choice translation');
$form=simplexml_load_file($base.'admin/forms/product.xml');
$axes=$form->xpath("//fieldset[@name='variants']/field[@name='variant_dimensions' and @type='subform']");
checkUi(count($axes)===1,'axes use native Joomla subform');
checkUi((string)$axes[0]['max']==='4','four option axes supported');
checkUi((string)$axes[0]['buttons']==='add,remove,move','axis add/remove/move matches gallery');
$sku=$form->xpath("//fieldset[@name='variants']/field[@name='variants' and @type='subform']");
checkUi(count($sku)===1,'SKU uses native Joomla subform');
checkUi((string)$sku[0]['layout']==='joomla.form.field.subform.repeatable',
    'SKU combination grid uses native Joomla controls with a compact option scroll area');
checkUi(count($form->xpath("//fieldset[@name='variants']/field[@name='category_option_preview' and @type='Productcategoryoptions']"))===1,
    'product sees category option catalog without duplicate option data');
checkUi(count($form->xpath("//fieldset[@name='variants']/field[@name='variants_workflow' and @type='note']"))===1,
    'SKU editor has an explicit step-by-step configuration guide');

$dimension=simplexml_load_file($base.'admin/forms/product_variant_dimension.xml');
$values=$dimension->xpath("//field[@name='values' and @type='subform']");
checkUi(count($values)===1,'option values use nested native Subform');
checkUi((string)$values[0]['buttons']==='add,remove,move',
    'values have same plus/remove/move buttons as gallery');
checkUi((string)$values[0]['layout']==='joomla.form.field.subform.repeatable',
    'nested option values use responsive repeatable cards');
$skuForm=simplexml_load_file($base.'admin/forms/product_variant.xml');
checkUi(count($skuForm->xpath("//field[@name='variant_label']"))===1,
    'SKU variant combination clearly named in table');
$editor=file_get_contents($base.'admin/tmpl/product/edit.php');
checkUi(str_contains($editor, "renderField('sku_matrix_payload')"),
    'one visible SKU matrix is rendered in product editor');
checkUi(!str_contains($editor, "renderField('variants')") &&
    !str_contains($editor, "renderField('variant_dimensions')"),
    'old nested SKU forms no longer occupy the product UI');
$client=file_get_contents($base.'media/js/sku-matrix.js');
checkUi(str_contains($client,'data-mm-matrix-payload') &&
    str_contains($client,'table table-striped table-sm'),
    'matrix has one payload and a native readable Joomla table');
foreach (['categories','products','attributes','values','groups'] as $entity) {
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
$attrForm=simplexml_load_file($base.'admin/forms/attribute.xml');
checkUi(count($attrForm->xpath('//field[@name="is_variant"]'))===0,
    'descriptive attributes have no SKU flag');
checkUi(count($form->xpath('//field[@name="variant_info"]'))===0,
    'product editor has no historical category-attribute SKU controls');
checkUi(!str_contains($editor,'$legacySku') &&
    !str_contains($editor,"renderField('variant_info')"),
    'only one order-option editor in product card');
checkUi(!is_file($base.'media/js/variants-admin.js') &&
    !is_file($base.'admin/src/Field/VariantinfoField.php'),
    'historical SKU editor files removed');
$matrixField=file_get_contents($base.'admin/src/Field/SkumatrixField.php');
checkUi(str_contains($matrixField,'ProductCatalogAxes::forAllCategories(') &&
    str_contains($matrixField,'ProductVariants::formRows('),
    'category options and saved physical SKUs are sources of truth');
checkUi(str_contains($client,'const combinations = axes =>') &&
    str_contains($client,'for (const axis of currentAxes)'),
    'each option generates one visible matrix column');
checkUi(str_contains($client,'isShared()') &&
    !str_contains($client,'data-mm-sku-price') &&
    !str_contains($client,'group-add'),
    'shared stock hides stock column and no SKU price or Joomla add-row control');
checkUi(str_contains($client,'refreshGallery()') &&
    str_contains($client,'mm-photo-sku-checklist'),
    'gallery photo permissions and SKU covers are edited alongside matrix');
foreach (['attribute','category','group','orderoption','product','value'] as $name) {
    $template = file_get_contents($base.'admin/tmpl/'.$name.'/edit.php');
    checkUi(str_contains($template,'card card-body mm-admin-panel'),
        $name.' renders a matching framed editable tab panel');
}
$cssFrame=file_get_contents($base.'media/css/admin-ui.css');
checkUi(str_contains($cssFrame,'#adminForm .mm-admin-panel') &&
    str_contains($cssFrame,'#adminForm .mm-admin-panel .mm-rules-table'),
    'all category/option/attribute tables use matching bordered styling');
checkUi(str_contains($cssFrame,'.mm-group-toolbar--top') &&
    str_contains($cssFrame,'.mm-group-finder__search--groups'),
    'group search uses the same framed finder and footer buttons');
$categoryRules=file_get_contents($base.'admin/src/Field/CategoryattributesField.php');
$optionRules=file_get_contents($base.'admin/src/Field/CategoryorderoptionsField.php');
checkUi(str_contains($categoryRules,'mm-rules-table--state') &&
    str_contains($optionRules,'mm-rules-table--state') &&
    str_contains($categoryRules,'d-block text-muted') &&
    str_contains($optionRules,"Text::_('JSTATUS')"),
    'category attributes and options have stacked code and separate publication status');
checkUi(str_contains($categoryRules,"AdminEntityLinks::edit('attribute',") &&
    str_contains($optionRules,"AdminEntityLinks::edit('orderoption',"),
    'entity links open the actual Joomla edit task instead of the dashboard');
$dashboard=file_get_contents($base.'admin/tmpl/dashboard/default.php');
checkUi(!str_contains($dashboard,'mm-dashboard-grid') &&
    str_contains($dashboard,'mm-dashboard-band card mm-dashboard-band--') &&
    str_contains($dashboard,'mm-dashboard-band-header') &&
    str_contains($dashboard,'mm-dashboard-tiles') &&
    str_contains($dashboard,'mm-dashboard-tile-top') &&
    str_contains($dashboard,'mm-dashboard-tile-title') &&
    str_contains($dashboard,'COM_MINIMARKET_DASHBOARD_MANAGEMENT') &&
    str_contains($dashboard,'COM_MINIMARKET_DASHBOARD_SHOP') &&
    str_contains($dashboard,'COM_MINIMARKET_DASHBOARD_TOOLS') &&
    str_contains($dashboard,'COM_MINIMARKET_DASHBOARD_ORDERS') &&
    str_contains($dashboard,'mm-dashboard-icon--bundle') &&
    str_contains($dashboard,'btn btn-primary d-inline-flex') &&
    !str_contains($dashboard,'list-group'),
    'three stacked full-width Joomla panels with icons above labels and no vertical menus');
checkUi(str_contains($editor,'sku_matrix_payload') &&
    str_contains($cssFrame,'.mm-sku-viewport') &&
    str_contains($cssFrame,'[data-mm-pinned="sku"]'),
    'compact SKU matrix is the only component area with scrolling option axes');
// 0.5.44: links to an edit task, not the MiniMarket component home.
foreach ([
    'admin/src/Field/CategoryattributesField.php' => "AdminEntityLinks::edit('attribute',",
    'admin/src/Field/CategoryorderoptionsField.php' => "AdminEntityLinks::edit('orderoption',",
    'admin/src/Field/ProductattributesField.php' => "AdminEntityLinks::edit('attribute',",
    'media/js/sku-matrix.js' => 'task=category.edit&id=',
] as $file => $expected) {
    checkUi(str_contains(file_get_contents($base.$file), $expected),
        'entity link opens actual Joomla editor: '.$file);
}
$landing=file_get_contents($base.'admin/tmpl/dashboard/default.php');
checkUi(str_contains($landing,'mm-dashboard-band card mm-dashboard-band--') &&
    str_contains($landing,'COM_MINIMARKET_FEEDS_TITLE') &&
    str_contains($landing,'COM_MINIMARKET_IMPORT_TITLE') &&
    str_contains($landing,'view=catalog'),
    'MiniMarket home has full-width Tools panel, feeds and storefront button');
$categoryAttrs=file_get_contents($base.'admin/src/Field/CategoryattributesField.php');
$categoryOpts=file_get_contents($base.'admin/src/Field/CategoryorderoptionsField.php');
checkUi(str_contains($categoryAttrs,'d-block text-muted') &&
    str_contains($categoryOpts,'d-block text-muted') &&
    str_contains($categoryAttrs,'mm-rules-state') &&
    str_contains($categoryOpts,'mm-rules-state'),
    'attribute/option codes are below names; publication has separate column');
$matrixScript=file_get_contents($base.'media/js/sku-matrix.js');
$adminCss=file_get_contents($base.'media/css/admin-ui.css');
checkUi(str_contains($matrixScript,'mm-sku-viewport') &&
    str_contains($matrixScript,'dataset.mmPinned') &&
    str_contains($matrixScript,'displayCategoryOptions()') &&
    str_contains($adminCss,'position: sticky') &&
    str_contains($adminCss,'overflow-x: auto'),
    'matrix has its own horizontal viewport and pinned SKU, stock, active columns');
checkUi(str_contains($matrixScript,'mm-photo-sku-checklist') &&
    str_contains($matrixScript,'const owners = new Map()'),
    'photo assignments use active SKU checkboxes with a single-main-photo guard');
$optionValues=file_get_contents($base.'admin/forms/orderoptionvalue.xml');
checkUi(str_contains($optionValues,'name="color_hex"') &&
    str_contains($optionValues,'name="sample_image"'),
    'each order option value can have a color and an image');
$groupScript=file_get_contents($base.'media/js/group-editor.js');
checkUi(str_contains($groupScript,'mm-group-item__save-actions') &&
    str_contains($groupScript,'submitGroupChanges'),
    'relationship Save is visibly separated from Add product');

echo 'NATIVE_UI_OK: '.$n." cases\n";

