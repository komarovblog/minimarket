<?php
/**
 * Source-level regression for the 0.5.24 SKU position UI.
 * Does not require Joomla, a browser or a database.
 * A successful result does not replace manual Joomla browser verification.
 */
declare(strict_types=1);
$root = dirname(__DIR__) . '/src/com_minimarket';
$n = 0;
function skuUiCheck(bool $condition, string $description): void
{
    global $n;
    ++$n;
    if (!$condition) {
        throw new RuntimeException('SKU_POSITION_UI_FAIL: ' . $description);
    }
}
$builder = (string) file_get_contents($root . '/admin/src/Field/VariantbuilderField.php');
$editor = (string) file_get_contents($root . '/media/js/variants-local.js');
$form = (string) file_get_contents($root . '/admin/forms/product.xml');
$variant = (string) file_get_contents($root . '/admin/forms/product_variant.xml');
$productModel = (string) file_get_contents($root . '/admin/src/Model/ProductModel.php');
$server = (string) file_get_contents($root . '/admin/src/Service/ProductVariants.php');

foreach ([
    'data-mm-add-sku', 'data-mm-sku-all', 'data-mm-sku-price',
    'data-mm-sku-stock', 'data-mm-sku-apply',
] as $field) {
    skuUiCheck(str_contains($builder, $field), 'Joomla toolbar has ' . $field);
}
skuUiCheck(str_contains($builder, 'btn btn-outline-primary btn-sm') &&
    str_contains($builder, 'form-control form-control-sm'),
    'SKU actions use Joomla/Bootstrap native controls');
skuUiCheck(str_contains($form, 'joomla.form.field.subform.repeatable-table'),
    'SKU positions still use native Joomla repeatable table');
foreach (['id', 'sku', 'price', 'stock', 'image', 'photos', 'state'] as $field) {
    skuUiCheck(str_contains($variant, 'name="' . $field . '"'),
        'position keeps separate native field ' . $field);
}
skuUiCheck(str_contains($editor, 'refreshSkuEditors(axes)'),
    'option selectors operate within actual native SKU positions');
skuUiCheck(str_contains($editor, "addSku?.addEventListener('click'") &&
    str_contains($editor, "appendRow({ title: '', selection: {} }, axes, true)"),
    'manual position begins without fake SKU identity or combination');
skuUiCheck(str_contains($editor, "copy.dataset.mmSkuCopy = '1'") &&
    str_contains($editor, "['price', 'stock', 'state']"),
    'copy carries trade fields but resets unique SKU and combination');
skuUiCheck(str_contains($editor, 'data-mm-sku-select') &&
    str_contains($editor, "selectAllSku?.addEventListener('change'"),
    'mass updates target chosen rows, not all products');
skuUiCheck(str_contains($editor, 'Number(stock) > 4294967295') &&
    str_contains($editor, "if (value === '') continue;"),
    'browser checks quantity bounds and skips untouched columns');
skuUiCheck(str_contains($editor, "selectionInput.value = JSON.stringify(selected)") &&
    str_contains($editor, "new Event('change', { bubbles: true })"),
    'local option choice updates the native hidden input before Joomla save');
skuUiCheck(str_contains($server, 'COM_MINIMARKET_VARIANTS_DUPLICATE') &&
    str_contains($server, 'count($rawSelection) !== count($selection)'),
    'server still rejects duplicate or incomplete combinations');
skuUiCheck(str_contains($productModel, '$db->transactionStart();') &&
    str_contains($productModel, '$db->transactionRollback();'),
    'positions still save through server transaction');
skuUiCheck(!str_contains($editor, 'window.fetch(') &&
    !str_contains($editor, 'localStorage'),
    'no competing browser trade data store or undocumented endpoint');

foreach (['ru-RU', 'en-GB'] as $lang) {
    $ini = (string) file_get_contents($root . '/admin/language/' . $lang . '/com_minimarket.ini');
    foreach ([
        'COM_MINIMARKET_SKU_ADD_POSITION',
        'COM_MINIMARKET_SKU_COPY_POSITION',
        'COM_MINIMARKET_SKU_BULK_PRICE',
        'COM_MINIMARKET_SKU_BULK_STOCK',
        'COM_MINIMARKET_SKU_BULK_APPLY',
    ] as $key) {
        skuUiCheck((bool) preg_match('/^' . $key . '="/m', $ini),
            $lang . ' has ' . $key);
    }
}
echo 'SKU_POSITION_UI_SOURCE_PASS: ' . $n . " checks\n";
