<?php
/** 0.5.58 bundle table and price summary regression, no database needed. */
declare(strict_types=1);
function expectUi(bool $ok,string $message):void {if(!$ok)throw new RuntimeException('BUNDLE_UI_FAIL '.$message);}
$root=__DIR__.'/../src/com_minimarket/';
$php=file_get_contents($root.'admin/src/Field/BundlepickerField.php');
$js=file_get_contents($root.'media/js/entity-picker.js');
$css=file_get_contents($root.'media/css/admin-ui.css');
$edit=file_get_contents($root.'admin/tmpl/product/edit.php');
expectUi(str_contains($php,'mm-bundle-grid-head') && str_contains($php,'mm-bundle-product')
    && str_contains($php,'mm-bundle-quantity'),'server rows and header use aligned cells');
expectUi(str_contains($js,"el('div','mm-bundle-product')") &&
    str_contains($js,"el('label','mm-bundle-quantity')"),
    'newly appended rows use same table layout');
expectUi(str_contains($css,'grid-template-columns: minmax(13rem,1fr) 9rem 8rem 8rem 2.75rem'),
    'five visible bundle columns share one grid');
expectUi(str_contains($js,"querySelectorAll('[data-mm-bundle-totals]')") &&
    str_contains($js,"totalBox.hasAttribute('data-mm-discount-only')"),
    'both pricing summaries update on quantity or discount changes');
expectUi(str_contains($edit,'data-mm-discount-only') &&
    substr_count($edit,'data-mm-bundle-totals')===2 &&
    substr_count($edit,'data-mm-bundle-final-total')===2,
    'bundle details and composition have synchronized summaries');
foreach(['ru-RU','en-GB'] as $lang) {
 $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 foreach(['COM_MINIMARKET_BUNDLE_PRICE_COMPONENT_TOTAL',
   'COM_MINIMARKET_BUNDLE_PRICE_FINAL_DISCOUNT','COM_MINIMARKET_BUNDLE_PRICE_FINAL_FIXED',
   'COM_MINIMARKET_BUNDLE_LINE_TOTAL'] as $key) expectUi(isset($ini[$key]),"$lang $key");
}
echo "MM_058_BUNDLE_UI_OK\n";
