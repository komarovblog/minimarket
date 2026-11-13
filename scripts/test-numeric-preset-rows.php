<?php
/** Canonical number dictionary rows, no legacy CSV option storage. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace Joomla\CMS\Filter {
    final class OutputFilter {
        public static function stringURLSafe(string $s):string {
            return strtolower(str_replace(' ','-',$s));
        }
    }
}
namespace MiniMarket\Tests\NumericDictionary {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/AttributeSettings.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Service/AttributeValueRows.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\AttributeValueRows as Values;
    $checks=0;
    function ok(bool $yes,string $why):void{
        global $checks;$checks++;
        if(!$yes)throw new \RuntimeException('NUMERIC_DICTIONARY_FAIL: '.$why);
    }
    $rows=Values::normalize([
       ['id'=>0,'title'=>'1500 Вт','code'=>'power-1500','state'=>1,'value_payload'=>'1500.0000'],
       ['id'=>0,'title'=>'1000 Вт','code'=>'power-1000','state'=>1,'value_payload'=>'1000'],
       ['id'=>0,'title'=>'-0.5 Вт','code'=>'power-minus','state'=>1,'value_payload'=>'-0.5000']
    ],[],'number');
    ok(array_column($rows,'value_payload')===['1500','1000','-0.5'],
       'numeric values normalized and preserve ordering');
    ok(array_column($rows,'sort_order')===[0,1,2],
       'drag/drop rows assign independent positions');
    ok(Values::normalize([],[],'number')===[],
       'clearing last dictionary value produces zero rows');
    foreach([
        ['title'=>'bad','value_payload'=>'1e4'],
        ['title'=>'bad','value_payload'=>'hello'],
        ['title'=>'bad','value_payload'=>'5.12345']
    ] as $invalid) {
        try {
           Values::normalize([array_merge(
              ['id'=>0,'code'=>'invalid','state'=>1],$invalid
           )],[],'number');
           ok(false,'invalid numeric dictionary rejected');
        } catch(\InvalidArgumentException) {
           ok(true,'invalid numeric dictionary rejected');
        }
    }
    $root=__DIR__.'/../src/com_minimarket/admin/';
    $form=simplexml_load_file($root.'forms/attribute.xml');
    $row=simplexml_load_file($root.'forms/attribute_unified_value_row.xml');
    ok($form!==false && $row!==false,'both canonical XML forms parse');
    $dictionary=$form->xpath("//fieldset[@name='details']/field[@name='unified_values']");
    ok(count($dictionary)===1 && (string)$dictionary[0]['type']==='subform'
       && (string)$dictionary[0]['showon']==='value_source:preset[AND]input_type!:boolean'
       && (string)$dictionary[0]['buttons']==='add,remove,move',
       'numeric values edited using the common preset dictionary');
    ok(count($row->xpath("//field[@name='value_payload']"))===1,
       'numeric values stored as typed dictionary payload');
    ok(count($form->xpath("//field[@name='number_options']"))===0
       && count($form->xpath("//field[@name='number_preset_rows']"))===0,
       'no legacy CSV storage or old numeric subform');
    $model=file_get_contents($root.'src/Model/AttributeModel.php');
    ok(str_contains($model,'AttributeValueRows::normalize')
       && str_contains($model,'AttributeValueRows::apply'),
       'attribute model persists numeric dictionary via stable value IDs');
    echo "NUMERIC_DICTIONARY_OK: $checks checks\n";
}
