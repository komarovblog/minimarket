<?php
/** Seven types, independent input and filter presentation, numeric presets. */
declare(strict_types=1);
\define('_JEXEC', 1);
require __DIR__ . '/../src/com_minimarket/admin/src/Service/TextPresetRows.php';
require __DIR__ . '/../src/com_minimarket/admin/src/Service/AttributeSettings.php';

use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings as S;
$count = 0;
function valid(bool $assertion, string $name): void {
    global $count;
    ++$count;
    if (!$assertion) throw new RuntimeException('ATTRIBUTE_PRESENTATION_FAIL: ' . $name);
}
function attr(string $type, string $input='auto', string $filter='none'): object {
    return (object)[
        'input_type'=>$type,'input_mode'=>$input,'filter_mode'=>$filter,
        'is_multiple'=>0,'is_filterable'=>($filter!=='none' ? 1 : 0),
        'filter_multiple'=>0,'number_options'=>'','number_unit'=>'',
        'number_min'=>null,'number_max'=>null,'number_step'=>null,
    ];
}
foreach (S::TYPES as $type) {
    $a=attr($type);
    valid(S::normalize($a)===null, "valid type $type");
}
valid(S::numericOptions('1000, 1500.0000;2000') === ['1000','1500','2000'],
    'numerical preset normalization');
try {S::numericOptions('1000,1000.00'); valid(false,'duplicate numeric value');}
catch(InvalidArgumentException $e) {valid(true,'duplicate numeric value rejected');}
try {S::numericOptions('15a,200'); valid(false,'non numeric preset');}
catch(InvalidArgumentException $e) {valid(true,'non numeric preset rejected');}
$a=attr('number','presets','slider');
$a->number_options='1000, 1500, 2000';
$a->number_unit='Вт';$a->number_min='1000';$a->number_max='2000';$a->number_step='500';
valid(S::normalize($a)===null && $a->number_options==='1000,1500,2000',
    'power can be preset input and slider output');
$a->number_options='1000, 2500';
valid(S::normalize($a)==='COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID',
    'preset outside max rejected');
$a->number_options='1000,1500';$a->number_min='3000';
valid(S::normalize($a)==='COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID',
    'minimum greater than maximum rejected');
$a=attr('number','auto','checkboxes');
valid(S::normalize($a)===null && $a->filter_multiple===1,
    'checkbox filter can be multiple although product stores one number');
$a=attr('list','radio','checkboxes');$a->is_multiple=1;
valid(S::normalize($a)==='COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID',
    'radio cannot store multiple product values');
$a=attr('list','checkboxes','dropdown');$a->is_multiple=1;$a->filter_multiple=0;
valid(S::normalize($a)===null && $a->filter_multiple===0,
    'multi-checkbox product and single dropdown filter are independent');
$a=attr('text','auto','values');$a->filter_multiple=1;
valid(S::normalize($a)===null,'text supports existing values facet');
$a=attr('date','auto','date_range');
valid(S::normalize($a)===null,'date range is supported as filter setting');
$a=attr('date','auto','slider');
valid(S::normalize($a)==='COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID',
    'date cannot use numeric slider');
$a=attr('url','auto','none');$a->is_filterable=1;
valid(S::normalize($a)==='COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID',
    'URL not faceted');
$a=attr('textarea','auto','none');$a->is_filterable=1;
valid(S::normalize($a)==='COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID',
    'long descriptions not faceted');
$a=attr('boolean','auto','boolean');
valid(S::normalize($a)===null,'boolean filtering mode supported');
$a=attr('list','auto','checkboxes');$a->is_multiple=0;
valid(S::normalize($a)===null && $a->filter_multiple===1,
    'one value per product, multiple values in filter');
echo 'ATTRIBUTE_PRESENTATION_OK: '.$count." cases\n";
