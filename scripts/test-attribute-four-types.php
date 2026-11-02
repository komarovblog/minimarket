<?php
/** Source-level regression for 0.5.15 six visible types, old lists preserved. */
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__ . '/../src/com_minimarket/admin/src/Service/TextPresetRows.php';
use MiniMarket\Component\Minimarket\Administrator\Service\TextPresetRows;
$c=0;
function check4(bool $good, string $name): void {global $c; ++$c; if (!$good) throw new RuntimeException('FOUR_TYPES_FAIL: '.$name);}
$values=TextPresetRows::encode([
    'row0'=>['value'=>'Джинс'],'row1'=>['value'=>'Хлопок']
]);
check4(TextPresetRows::values($values)===['Джинс','Хлопок'],'text presets roundtrip');
check4(TextPresetRows::formRows($values)['row1']['value']==='Хлопок','presets in Joomla Subform');
check4(TextPresetRows::encode([['value'=>'']])==='[]','unused blank text row ignored');
foreach ([[['value'=>'A'],['value'=>'a']], [['value'=>'<script>']]] as $invalid) {
    try {TextPresetRows::encode($invalid);check4(false,'reject invalid text');}
    catch(InvalidArgumentException $e){check4(true,'reject invalid text');}
}
$root=__DIR__.'/../src/com_minimarket/admin/';
$attr=simplexml_load_file($root.'forms/attribute.xml');
check4($attr!==false,'attribute form XML');
check4((string)$attr->xpath("//field[@name='input_type']")[0]['default']==='text',
    'new attribute defaults to Text');
check4(count($attr->xpath("//field[@name='input_type']/option[@value='editor']"))===1,
    'Editor is supported data type');
check4(count($attr->xpath("//field[@name='text_preset_rows' and @type='subform']"))===1,
    'Text presets use standard repeatable Joomla form');
check4(count($attr->xpath("//field[@name='number_preset_rows' and @type='subform']"))===1,
    'Number presets still use standard repeatable Joomla form');
$settings=file_get_contents($root.'src/Service/AttributeSettings.php');
check4(str_contains($settings,"NEW_TYPES = ['text','number','editor','boolean','document','color']"),
    'six types for newly created attributes');
$table=file_get_contents($root.'src/Table/AttributeTable.php');
check4(str_contains($table,'COM_MINIMARKET_LEGACY_TYPE_NEW_FORBIDDEN'),
    'old ID-based list can be edited but not newly created');
$model=file_get_contents($root.'src/Model/AttributeModel.php');
check4(str_contains($model,'TextPresetRows::encode') && str_contains($model,'TextPresetRows::formRows'),
    'Text preset load and save through numeric-independent code');
$field=file_get_contents($root.'src/Field/ProductattributesField.php');
check4(str_contains($field,'Editor::getInstance'), 'product field uses native Joomla HTML editor');
check4(str_contains($field,"$"."type === 'text' && $"."mode === 'presets'"),
    'typed text choices are select instead of free text');
$products=file_get_contents($root.'src/Service/ProductAttributes.php');
check4(str_contains($products,"'editor'"),'product service saves rich text');
check4(str_contains($products,'TextPresetRows::values'),'product validates text presets');
echo 'FOUR_TYPES_OK: '.$c." cases\n";
