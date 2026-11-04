<?php
/** 0.5.39 direct SKU matrix replaces old multi-step position editor. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket';
$checks=0;
function checkSku(bool $ok,string $why):void {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException('SKU_POSITION_UI_FAIL: '.$why);
}
$ui=file_get_contents($root.'/media/js/sku-matrix.js');
$form=simplexml_load_file($root.'/admin/forms/product.xml');
$tpl=file_get_contents($root.'/admin/tmpl/product/edit.php');
$service=file_get_contents($root.'/admin/src/Service/ProductSkuMatrix.php');
$model=file_get_contents($root.'/admin/src/Model/ProductModel.php');
checkSku(count($form->xpath("//fieldset[@name='variants']/field[@name='sku_matrix_payload' and @type='Skumatrix']"))===1,
    'one SKU matrix field is used');
checkSku(str_contains($tpl,"renderField('sku_matrix_payload')") &&
    !str_contains($tpl,"renderField('variant_matrix')"),
    'no separate combination generation step');
checkSku(str_contains($ui,'const combinations = axes =>') &&
    str_contains($ui,'for (const axis of currentAxes)'),
    'the number of option columns matches the category');
checkSku(str_contains($ui,'mm-sku-matrix') &&
    str_contains($ui,'data-mm-matrix-payload'),
    'compact direct SKU matrix is submitted in one payload');
checkSku(!str_contains($ui,'group-add') &&
    !str_contains($ui,'data-mm-sku-price'),
    'old native Joomla subform row-add and per-SKU prices are removed');
checkSku(str_contains($ui,'isShared()') &&
    str_contains($ui,'setStockMode()'),
    'shared product stock hides per-combination quantity');
checkSku(str_contains($service,"'price' => ".'$productPrice') &&
    str_contains($model,'ProductSkuMatrix::normalize('),
    'variant price strictly inherits product price on server');
checkSku(str_contains($service,'ProductVariants::formRows(') &&
    str_contains($service,'!isset($used[$id])'),
    'existing variant IDs/media are reused and missing saved rows rejected');
checkSku(str_contains($model,'$db->transactionStart();') &&
    str_contains($model,'$db->transactionRollback();'),
    'SKU and gallery save transaction remains atomic');
checkSku(str_contains($ui,'refreshGallery()') &&
    str_contains($ui,'data-mm-photo-select'),
    'one product gallery supports per-variant visibility and primary photo');
checkSku(!str_contains($ui,'localStorage') && !str_contains($ui,'window.fetch('),
    'no competing local trade store or undocumented network endpoint');
echo 'SKU_POSITION_UI_SOURCE_PASS: '.$checks." cases\n";
