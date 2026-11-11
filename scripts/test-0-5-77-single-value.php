<?php
/** MiniMarket 0.5.77: product characteristics are single valued. */
declare(strict_types=1);
define('_JEXEC',1);
require dirname(__DIR__).'/src/com_minimarket/admin/src/Service/AttributeSettings.php';
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings;
$n=0;
function m77(bool $ok,string $message):void {
    global $n;++$n;
    if (!$ok) throw new RuntimeException('MM77_SINGLE_VALUE_FAIL: '.$message);
}
$root=dirname(__DIR__);
$form=simplexml_load_file($root.'/src/com_minimarket/admin/forms/attribute.xml');
m77($form!==false,'characteristic form parses');
m77(count($form->xpath("//field[@name='is_multiple']"))===0,
    'product characteristic no longer exposes multiple values');
m77(count($form->xpath("//field[@name='filter_multiple']"))===1,
    'several OR values remain possible during catalog filtering');
m77(count($form->xpath("//field[@name='filter_rules_help']"))===0,
    'obsolete compatibility note removed');
$settings=(object)['input_type'=>'text','value_source'=>'preset',
    'is_multiple'=>1,'filter_mode'=>'checkboxes','filter_multiple'=>1,
    'filter_search'=>1,'color_display'=>'both'];
m77(AttributeSettings::normalize($settings)===null,'valid preset characteristic');
m77($settings->is_multiple===0 && $settings->filter_multiple===1,
    'one product value does not disable multiple public filter choices');
$attributeService=(string)file_get_contents($root.'/src/com_minimarket/admin/src/Service/ProductAttributes.php');
m77(!str_contains($attributeService,"if (\$def['is_multiple'])"),
    'no multi-select product options in validator');
m77(str_contains($attributeService,'count($value) !== 1'),
    'server rejects posts with multiple selected attribute options');
m77(!str_contains($attributeService,"if (!empty(\$normalized['free_values']))"),
    'no new free-value arrays are saved');
$attrField=(string)file_get_contents($root.'/src/com_minimarket/admin/src/Field/ProductattributesField.php');
m77(!str_contains($attrField,'data-mm-free-add')
    && !str_contains($attrField,'type="checkbox"')
    && str_contains($attrField,"'<select class=\"form-select\" id=\"'"),
    'one choice displayed in product editor, not a multi-value control');
$ru=(string)file_get_contents($root.'/src/com_minimarket/admin/language/ru-RU/com_minimarket.ini');
m77(str_contains($ru,'COM_MINIMARKET_MODE_CHECKBOXES="Флажки"')
    && str_contains($ru,'COM_MINIMARKET_BUNDLE_UNIT_PRICE="Цена"'),
    'labels are concise and match the screenshot request');
$picker=(string)file_get_contents($root.'/src/com_minimarket/admin/src/Field/BundlepickerField.php');
$script=(string)file_get_contents($root.'/src/com_minimarket/media/js/entity-picker.js');
m77(!str_contains($picker,"': '.\$esc(\$data['price'])")
    && str_contains($picker,"'<span class=\"mm-bundle-unit-price\">'.\$esc(\$data['price'])")
    && !str_contains($script,"(words.unit||'Price')+':'"),
    'bundle unit-price numbers have no repeated prefix');
$migration=(string)file_get_contents($root.'/src/com_minimarket/admin/sql/updates/mysql/0.5.77.sql');
foreach (['attribute_multivalue_archive','INSERT IGNORE INTO','DELETE extra',
    'filter_multiple','idx_mm_pa_one_value','idx_mm_free_one_value'] as $marker) {
    m77(str_contains($migration,$marker),'safe migration includes '.$marker);
}
$manifest=(string)file_get_contents($root.'/src/com_minimarket/minimarket.xml');
m77((bool)preg_match('~<version>([0-9]+\.[0-9]+\.[0-9]+)</version>~', $manifest, $versionMatch)
    && version_compare($versionMatch[1], '0.5.77', '>='), 'single-value characteristic version >= 0.5.77');
echo "MM77_SINGLE_VALUE_UI_PASS: $n checks\n";
