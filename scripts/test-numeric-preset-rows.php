<?php
/** PHP-only regression for native Joomla numeric presets and stable CSV storage. */
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__ . '/../src/com_minimarket/admin/src/Service/AttributeSettings.php';
require __DIR__ . '/../src/com_minimarket/admin/src/Service/NumericPresetRows.php';
use MiniMarket\Component\Minimarket\Administrator\Service\NumericPresetRows;
$checks=0;
function ensure(bool $ok,string $name):void {global $checks;++$checks;if(!$ok)throw new RuntimeException('NUMERIC_PRESETS_FAIL: '.$name);}
$rows=NumericPresetRows::formRows('1000,1500.0000,2000');
ensure($rows === ['row0'=>['number'=>'1000'],'row1'=>['number'=>'1500'],'row2'=>['number'=>'2000']],
    'legacy comma-separated numbers load as repeatable rows without losing order');
ensure(NumericPresetRows::normalize([
    'row2'=>['number'=>'2000'],
    'row0'=>['number'=>'1000'],
    'row1'=>['number'=>'1500']
])==='2000,1000,1500','dragged positions control stored numeric ordering');
ensure(NumericPresetRows::normalize([])==='','removing last row works');
ensure(NumericPresetRows::normalize('{}')==='','empty Joomla JSON subform supported');
ensure(NumericPresetRows::normalize([['number'=>'-0.5'],['number'=>'0.0001']])==='-0.5,0.0001',
    'fractional and negative numeric options stored exactly');
foreach([
    [['number'=>'not-number']],
    [['number'=>'1000'],['number'=>'1000.0000']],
    [['number'=>null]],
    [['number'=>['1600']]],
    [['title'=>'1900']],
] as $bad){
    try{NumericPresetRows::normalize($bad);ensure(false,'invalid input rejected');}
    catch(InvalidArgumentException $e){ensure(true,'invalid preset blocked');}
}
$root=__DIR__.'/../src/com_minimarket/admin/';
$form=simplexml_load_file($root.'forms/attribute.xml');
$row=simplexml_load_file($root.'forms/attribute_number_preset_row.xml');
ensure($form!==false && $row!==false,'Joomla XML parses');
$sub=$form->xpath("//fieldset[@name='details']/field[@name='number_preset_rows']");
ensure(count($sub)===1 && (string)$sub[0]['type']==='subform'
    && (string)$sub[0]['buttons']==='add,remove,move'
    && (string)$sub[0]['multiple']==='true', 'standard Joomla Add/Remove/Move used');
ensure(count($form->xpath("//field[@name='number_options' and @type='hidden']"))===1,
    'CSV compatibility field is not displayed separately');
ensure(count($row->xpath("//field[@name='number' and @type='number']"))===1,
    'each row is typed number, not a list option');
$model=file_get_contents($root.'src/Model/AttributeModel.php');
ensure(str_contains($model,'NumericPresetRows::formRows')
    && str_contains($model,'NumericPresetRows::normalize'),
    'attribute editor loads and saves repeatable numbers');
echo 'NUMERIC_PRESETS_OK: '.$checks." cases\n";
