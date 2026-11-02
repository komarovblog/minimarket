<?php
/**
 * Attribute list/value navigation contract for 0.5.8.
 * Joomla rendering still requires a real-browser smoke after installation.
 */
declare(strict_types=1);

\define('_JEXEC', 1);
$dir = __DIR__ . '/../src/com_minimarket/admin/';
$edit = file_get_contents($dir . 'tmpl/attribute/edit.php');
$view = file_get_contents($dir . 'src/View/Attribute/HtmlView.php');
$productField = file_get_contents($dir . 'src/Field/ProductattributesField.php');
$valueModel = file_get_contents($dir . 'src/Model/ValueModel.php');
$attributeForm = simplexml_load_file($dir . 'forms/attribute.xml');
$valueForm = simplexml_load_file($dir . 'forms/value.xml');
$tests = [
    'attribute edit has values tab' => str_contains($edit, "'minimarket-attribute', 'values'"),
    'values only for List' => str_contains($edit, "\$this->item->input_type ?? ''") && str_contains($edit, "=== 'list'"),
    'new List must be saved' => str_contains($edit, "COM_MINIMARKET_ATTR_VALUES_SAVE_FIRST"),
    'attribute values are listed' => str_contains($edit, 'dictionaryValues as $value'),
    'attribute links to new value with id' => str_contains($edit, 'view=value&layout=edit&attribute_id='),
    'attribute links to existing value' => str_contains($edit, 'view=value&layout=edit&id='),
    'attribute values are a single inline list' => str_contains($edit, 'data-mm-values-table')
        && !str_contains($edit, 'COM_MINIMARKET_EDIT_VALUES'),
    'values list only for saved List attributes' => str_contains($view, "=== 'list'") && str_contains($view, '$this->item->id ?? 0'),
    'dictionary read filtered by attribute' => str_contains($view, "'attribute_id'") && str_contains($view, 'loadObjectList()'),
    'values list returns to owning attribute' => str_contains(file_get_contents($dir . 'tmpl/values/default.php'), 'COM_MINIMARKET_BACK_ATTRIBUTE'),
    'value form returns to owning attribute' => str_contains(file_get_contents($dir . 'tmpl/value/edit.php'), 'name="return"'),
    'value create preselects parent' => str_contains($valueModel, "getInt('attribute_id', 0)") && str_contains($valueModel, '$item->attribute_id = $attributeId'),
    'product missing values gets help' => str_contains($productField, 'COM_MINIMARKET_PRODUCT_ATTRIBUTES_NO_VALUES'),
    'product editor links to dictionary' => str_contains($productField, 'view=values&attribute_id='),
    'original attribute type unchanged' => $attributeForm && count($attributeForm->xpath("//field[@name='input_type']/option[@value='list']")) === 1,
    'value still uses attribute foreign key' => $valueForm && count($valueForm->xpath("//field[@name='attribute_id' and @type='sql']")) === 1,
];
foreach (['ru-RU','en-GB'] as $lang) {
    $ini = file_get_contents($dir . "language/$lang/com_minimarket.ini");
    foreach (['COM_MINIMARKET_ATTR_VALUES_TAB','COM_MINIMARKET_ATTR_VALUES_ADD',
        'COM_MINIMARKET_ATTR_VALUES_SAVE_FIRST','COM_MINIMARKET_PRODUCT_ATTRIBUTES_NO_VALUES'] as $key) {
        $tests["$lang translation $key"] = str_contains($ini, $key . '="');
    }
}
$fail = [];
foreach ($tests as $name => $ok) {
    if (!$ok) {
        $fail[] = $name;
    }
}
if ($fail) {
    fwrite(STDERR, 'ATTRIBUTE_VALUES_NAV_FAIL: ' . implode('; ', $fail) . "\n");
    exit(1);
}
echo 'ATTRIBUTE_VALUES_NAV_OK: ' . count($tests) . " cases\n";
