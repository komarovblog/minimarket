<?php
/** 0.5.45 isolated regressions: multiple numeric values, uniform source and typed presets. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace MiniMarket\UnifiedAttributeSmoke {
    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/TextPresetRows.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/AttributeSettings.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/AttributeValueRows.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/ProductAttributes.php';

    use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings as S;
    use MiniMarket\Component\Minimarket\Administrator\Service\AttributeValueRows as Rows;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes as Products;
    $count=0;
    function ok(bool $value, string $label): void {
        global $count;
        ++$count;
        if (!$value) throw new \RuntimeException('UNIFIED_ATTRIBUTES_FAIL: '.$label);
    }
    $number=(object)[
        'input_type'=>'number','value_source'=>'manual','color_display'=>'both',
        'input_mode'=>'presets','is_multiple'=>1,'is_filterable'=>0,
        'filter_mode'=>'none','filter_multiple'=>0,'text_options'=>'',
        'number_unit'=>'Вт','number_options'=>'','number_min'=>'0',
        'number_max'=>'15','number_step'=>'5'
    ];
    ok(S::normalize($number)===null && $number->is_multiple===1,
        'multiple numbers survive normalization');
    ok($number->input_mode==='auto','modern numbers do not use legacy preset mode');
    $defs=[
        1=>['id'=>1,'title'=>'Мощность','type'=>'number','value_source'=>'manual',
            'is_multiple'=>true,'number_min'=>'0','number_max'=>'15','number_step'=>'5',
            'number_options'=>'','values'=>[]],
        2=>['id'=>2,'title'=>'Мощности из справочника','type'=>'number','value_source'=>'preset',
            'is_multiple'=>true,'values'=>[
                201=>['title'=>'5 Вт','value_payload'=>'5','state'=>1],
                202=>['title'=>'10 Вт','value_payload'=>'10','state'=>1]]],
        3=>['id'=>3,'title'=>'Основной цвет','type'=>'color','value_source'=>'manual',
            'is_multiple'=>false,'values'=>[]],
    ];
    $map=[10=>[1,2,3]];
    $data=Products::normalize([
        '1'=>['5','10','15'],'2'=>['201','202'],'3'=>'#00aaff'
    ],10,$defs,$map,[1,2,3]);
    ok(($data['free_values'][1] ?? [])===['5','10','15'],
        'three distinct numeric values stored as ordered manual rows');
    ok($data['options'][2]===[201,202],'numeric presets use stable value IDs');
    ok($data['scalars'][3]['value']==='#00AAFF','manual HEX is canonical');
    foreach ([
        ['1'=>['5','5']], ['1'=>['5','100']], ['1'=>['3','5']],
        ['2'=>['201','201']], ['3'=>'invalid'],
    ] as $i=>$invalid) {
        try {
            Products::normalize($invalid,10,$defs,$map);
            ok(false,'reject invalid set '.$i);
        } catch (\InvalidArgumentException $error) {
            ok(true,'reject invalid set '.$i);
        }
    }
    $row=Rows::normalize([[
        'id'=>0,'title'=>'5 Вт','code'=>'five','state'=>1,
        'value_payload'=>'5.0000'
    ]],[], 'number');
    ok($row[0]['value_payload']==='5','numeric typed preset canonicalized');
    $doc=Rows::normalize([[
        'id'=>0,'title'=>'Инструкция','code'=>'manual','state'=>1,
        'document_file'=>'files/manual.pdf'
    ]],[], 'document');
    ok($doc[0]['value_payload']==='files/manual.pdf','document preset stored as typed path');
    try {
        Rows::normalize([[
            'id'=>0,'title'=>'Bad','code'=>'bad','state'=>1,'document_file'=>'../secret.pdf'
        ]],[], 'document');
        ok(false,'reject escaped document');
    } catch (\InvalidArgumentException $error) {
        ok(true,'reject escaped document');
    }
    $root=__DIR__.'/../src/com_minimarket/';
    $xml=simplexml_load_file($root.'admin/forms/attribute.xml');
    ok($xml!==false && count($xml->xpath("//field[@name='value_source']"))===1,
        'Joomla form has independent source choice');
    ok(count($xml->xpath("//field[@name='unified_values' and @type='subform']"))===1,
        'one Joomla repeatable dictionary');
    $migration=file_get_contents($root.'admin/sql/updates/mysql/0.5.45.sql');
    ok(str_contains($migration,'product_attribute_free_values') &&
        str_contains($migration,'value_payload') && str_contains($migration,'value_source'),
        'additive upgrade includes typed presets and long manual multi-values');
    echo 'UNIFIED_ATTRIBUTES_OK: '.$count." cases\n";
}
