<?php
/** Six canonical types; text dictionaries use stable IDs and optional images. */
declare(strict_types=1);
define('_JEXEC',1);
$root=__DIR__.'/../src/com_minimarket/admin/';
$form=simplexml_load_file($root.'forms/attribute.xml');
$subform=simplexml_load_file($root.'forms/attribute_unified_value_row.xml');
$c=0;
function check4(bool $yes,string $message):void{
    global $c;$c++;
    if(!$yes)throw new RuntimeException('SIX_TYPES_FAIL: '.$message);
}
check4($form!==false && $subform!==false,'Joomla form definitions parse');
$type=$form->xpath("//fieldset[@name='details']/field[@name='input_type']");
check4(count($type)===1 && (string)$type[0]['default']==='text','new attributes default to text');
$choices=[];
foreach($type[0]->option as $item)$choices[]=(string)$item['value'];
check4($choices===['text','number','editor','boolean','document','color'],
    'only canonical six types visible');
$source=$form->xpath("//fieldset[@name='details']/field[@name='value_source']");
check4(count($source)===1 && (string)$source[0]['type']==='list',
    'manual/dictionary source is a single native Joomla select');
$sourceValues=[];
foreach($source[0]->option as $opt)$sourceValues[]=(string)$opt['value'];
check4($sourceValues===['manual','preset'],'no legacy source option');
$dict=$form->xpath("//fieldset[@name='details']/field[@name='unified_values']");
check4(count($dict)===1 && (string)$dict[0]['type']==='subform'
    && (string)$dict[0]['showon']==='value_source:preset[AND]input_type!:boolean'
    && (string)$dict[0]['multiple']==='true',
    'all dictionary values share a single repeatable form');
foreach(['id','title','code','value_payload','sample_image','color_hex','state'] as $part){
    check4(count($subform->xpath("//field[@name='$part']"))===1,'dictionary row '.$part);
}
foreach(['input_mode','number_options','text_options','text_preset_rows','number_preset_rows','values'] as $old){
    check4(count($form->xpath("//field[@name='$old']"))===0,'removed obsolete field '.$old);
}
$settings=file_get_contents($root.'src/Service/AttributeSettings.php');
check4(str_contains($settings,"TYPES = ['text','number','editor','boolean','document','color']"),
    'server supports the same six types');
$model=file_get_contents($root.'src/Model/AttributeModel.php');
check4(str_contains($model,'AttributeValueRows::normalize')
    && str_contains($model,'AttributeValueRows::apply')
    && !str_contains($model,'TextPresetRows::'),'ID-based dictionary saved transactionally');
$field=file_get_contents($root.'src/Field/ProductattributesField.php');
check4(str_contains($field,'EditorField::class'),'native HTML editor available');
check4(str_contains($field,"$".'source === '."'preset'"),
    'product field renders text dictionary by value ID');
$products=file_get_contents($root.'src/Service/ProductAttributes.php');
check4(str_contains($products,"'editor'")&&str_contains($products,"'options'"),
    'product service handles HTML and stable dictionary values');
echo "SIX_TYPES_OK: $c checks\n";
