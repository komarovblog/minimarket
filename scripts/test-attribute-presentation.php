<?php
/** Six canonical data types, independent product and facet presentations. */
declare(strict_types=1);
\define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/admin/src/Service/AttributeSettings.php';
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings as S;
$count=0;
function valid(bool $yes,string $case):void {
    global $count;$count++;
    if(!$yes)throw new RuntimeException('ATTRIBUTE_PRESENTATION_FAIL: '.$case);
}
function field(string $type,string $source='manual',string $filter='none'):object {
    return (object)[
        'input_type'=>$type,'value_source'=>$source,
        'filter_mode'=>$filter,'filter_multiple'=>0,'is_multiple'=>0,
        'is_filterable'=>$filter!=='none'?1:0,
        'number_unit'=>'','number_min'=>null,'number_max'=>null,
        'number_step'=>null,'color_display'=>'both',
    ];
}
valid(S::TYPES===['text','number','editor','boolean','document','color'],
    'exactly six supported characteristic types');
foreach(S::TYPES as $type){
    $f=field($type);
    valid(S::normalize($f)===null,'valid '.$type);
}
foreach(['list','textarea','url','date'] as $old){
    valid(S::normalize(field($old))==='COM_MINIMARKET_ATTRIBUTE_TYPE_INVALID',
        'obsolete type denied '.$old);
}
valid(S::normalize(field('text','legacy'))==='COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID',
    'obsolete value source not accepted');
$f=field('boolean','preset','boolean');
$f->is_multiple=1;
valid(S::normalize($f)===null && $f->value_source==='manual' && $f->is_multiple===0,
    'boolean always manually entered once');
$f=field('number','manual','slider');
$f->number_min='0';$f->number_max='3000';$f->number_step='100';$f->number_unit='Вт';
valid(S::normalize($f)===null,'number supports bounds/step/slider');
$f->number_min='4000';
valid(S::normalize($f)==='COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID',
    'reversed range rejected');
$f->number_min='0';$f->number_step='0';
valid(S::normalize($f)==='COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID',
    'zero step rejected');
$f=field('number','preset','checkboxes');$f->filter_multiple=1;
valid(S::normalize($f)===null && $f->filter_multiple===1,
    'numeric presets allow multiple checkbox filtering when explicitly selected');
$f=field('number','manual','radio');
valid(S::normalize($f)===null && $f->filter_mode==='checkboxes',
    'legacy radio facet is normalized to independent checkbox presentation');
$f=field('text','preset','dropdown');$f->is_multiple=1;
$f->filter_multiple=0;
valid(S::normalize($f)===null && $f->filter_multiple===0,
    'multi product choices and single filter dropdown independent');
$f=field('text','manual','values');$f->filter_multiple=1;
valid(S::normalize($f)===null,'text can filter by available values');
$f=field('color','preset','swatches');$f->filter_multiple=1;
valid(S::normalize($f)===null && $f->filter_multiple===1,
    'legacy color swatches keep independently selected multiple values');
foreach(['editor','document'] as $type){
    $f=field($type,'manual','checkboxes');
    valid(S::normalize($f)==='COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID',
        'non-faceted '.$type);
}
$f=field('text');$f->number_unit='old';$f->number_min='1';
valid(S::normalize($f)===null && $f->number_min===null && $f->number_unit==='',
    'non-numeric metadata normalized to empty');
valid(S::decimal('3.5000') && !S::decimal('3e6')
    && S::canonicalDecimal('3.5000')==='3.5','numeric decimals canonical');
echo "ATTRIBUTE_PRESENTATION_OK: $count cases\n";
