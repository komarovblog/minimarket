<?php
/** Canonical product-attribute validation and transactional storage. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {
        public function getQuery($new=true);
        public function quoteName($name);
        public function quote($value);
        public function setQuery($query);
        public function loadAssocList();
        public function execute();
    }
}
namespace Joomla\CMS\Filter {
    final class InputFilter {
        public static function getInstance():self{return new self();}
        public function clean(string $value,string $type):string{return $value;}
    }
}
namespace MiniMarket\ProductAttributesSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/CategoryAttributes.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Service/AttributeSettings.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Service/ProductAttributes.php';
    $count=0;
    function check(bool $ok,string $message):void{
        global $count;$count++;
        if(!$ok)throw new \RuntimeException('CANONICAL_PRODUCT_ATTRIBUTES_FAIL: '.$message);
    }
    function rejects(callable $fn,string $name):void {
        try{$fn();check(false,$name);}catch(\InvalidArgumentException){check(true,$name);}
    }
    $definitions=[
        10=>['id'=>10,'title'=>'Color','type'=>'color','value_source'=>'preset',
            'is_multiple'=>true,'state'=>1,'values'=>[
                100=>['title'=>'Red','state'=>1],101=>['title'=>'Blue','state'=>1],
                102=>['title'=>'Unpublished','state'=>0]]],
        11=>['id'=>11,'title'=>'Material','type'=>'text','value_source'=>'preset',
            'is_multiple'=>false,'state'=>1,'values'=>[
                110=>['title'=>'Cotton','state'=>1],111=>['title'=>'Wool','state'=>1]]],
        12=>['id'=>12,'title'=>'Skills','type'=>'text','value_source'=>'preset',
            'is_multiple'=>true,'state'=>1,'values'=>[
                120=>['title'=>'Logic','state'=>1],121=>['title'=>'Motor','state'=>1]]],
        13=>['id'=>13,'title'=>'Width','type'=>'number','value_source'=>'manual',
            'is_multiple'=>false,'state'=>1,'number_min'=>'-20','number_max'=>'200',
            'number_step'=>'0.5','values'=>[]],
        14=>['id'=>14,'title'=>'Summary','type'=>'text','value_source'=>'manual',
            'is_multiple'=>false,'state'=>1,'values'=>[]],
        15=>['id'=>15,'title'=>'Eco','type'=>'boolean','value_source'=>'manual',
            'is_multiple'=>false,'state'=>1,'values'=>[]],
        16=>['id'=>16,'title'=>'Specs','type'=>'number','value_source'=>'preset',
            'is_multiple'=>false,'state'=>1,'values'=>[
                160=>['title'=>'1000','value_payload'=>'1000','state'=>1],
                161=>['title'=>'1500','value_payload'=>'1500','state'=>1]]],
        17=>['id'=>17,'title'=>'Many sizes','type'=>'number','value_source'=>'manual',
            'is_multiple'=>true,'state'=>1,'number_min'=>null,'number_max'=>null,
            'number_step'=>null,'values'=>[]],
        18=>['id'=>18,'title'=>'Documentation','type'=>'document','value_source'=>'manual',
            'is_multiple'=>false,'state'=>1,'values'=>[]],
        19=>['id'=>19,'title'=>'Tint','type'=>'color','value_source'=>'manual',
            'is_multiple'=>false,'state'=>1,'values'=>[]],
        20=>['id'=>20,'title'=>'Unpublished','type'=>'text','value_source'=>'manual',
            'is_multiple'=>false,'state'=>0,'values'=>[]],
        21=>['id'=>21,'title'=>'Editor','type'=>'editor','value_source'=>'manual',
            'is_multiple'=>false,'state'=>1,'values'=>[]],
    ];
    $tree=['parents'=>[1=>0,2=>1,3=>0],
        'rules'=>[1=>[10=>1,11=>1,12=>1,13=>1,14=>1,15=>1],
            2=>[12=>0],3=>[16=>1,17=>1,18=>1,19=>1,20=>1,21=>1]]];
    $map=ProductAttributes::categoryMap($tree,$definitions);
    check($map[1]===[10,11,12,13,14,15],'global category assignment');
    check($map[2]===[10,11,13,14,15],'child disables inherited attribute');
    check($map[3]===[16,17,18,19,20,21],'draft characteristics remain administrable');
    $normalized=ProductAttributes::normalize([
        '10'=>'100','11'=>'110','12'=>'120',
        '13'=>'-12.5','14'=>'Cotton','15'=>'0'
    ],1,$definitions,$map);
    check($normalized['options'][10]===[100],'single predefined color by stable ID');
    check($normalized['options'][11]===[110],'one material by ID');
    check($normalized['options'][12]===[120],'single skill by stable ID');
    check($normalized['scalars'][13]['value']==='-12.5','number typed scalar');
    check($normalized['scalars'][15]['value']==='0','false boolean kept');
    check(ProductAttributes::normalize([],1,$definitions,$map)===
       ['scalars'=>[],'options'=>[],'free_values'=>[]],'clear all values');
    check(ProductAttributes::normalize(['12'=>''],2,$definitions,$map)===
       ['scalars'=>[],'options'=>[],'free_values'=>[]],'cleared disallowed field ignored');
    $other=ProductAttributes::normalize([
        '16'=>'160','17'=>'5','18'=>'files/catalog/manual.pdf',
        '19'=>'#ABCDEF','21'=>'<b>Works</b>'
    ],3,$definitions,$map);
    check($other['options'][16]===[160],'numeric presets store value IDs');
    check($other['scalars'][17]['value']==='5' && $other['free_values']===[],
        'manual numeric characteristic stores exactly one scalar');
    check($other['scalars'][18]['value']==='files/catalog/manual.pdf',
        'manual document path');
    check($other['scalars'][19]['value']==='#ABCDEF','manual color normalizes uppercase');
    check($other['scalars'][21]['value']==='<b>Works</b>','safe HTML persists');
    check(ProductAttributes::normalize(['10'=>['102']],1,$definitions,$map)
        ['options'][10]===[102], 'draft preset values remain editable in admin');
    $required=[13,15,11];
    rejects(fn()=>ProductAttributes::normalize(['13'=>'0','15'=>'0'],1,
        $definitions,$map,$required),'missing required dictionary property');
    $complete=ProductAttributes::normalize(['13'=>'0','15'=>'0','11'=>'111'],
        1,$definitions,$map,$required);
    check($complete['scalars'][13]['value']==='0' &&
        $complete['scalars'][15]['value']==='0' &&
        $complete['options'][11]===[111], 'zero/No and preset satisfy required');
    foreach([
        [2,['12'=>['120']]], [1,['20'=>'no']], [1,['10'=>['999']]],
        [1,['10'=>['110']]], [1,['11'=>['110','111']]],
        [1,['10'=>['100','101']]], [1,['13'=>'1e9']],
        [1,['13'=>'12.12345']], [1,['13'=>'12.3']],
        [1,['13'=>'201']], [1,['14'=>str_repeat('x',1025)]],
        [1,['14'=>['forged']]], [1,['15'=>'yes']],
        [3,['16'=>'1000']], [3,['17'=>['5','5']]],
        [3,['18'=>'javascript:alert(1)']],
        [3,['19'=>'blue']], [3,['21'=>'<script>x</script>']],
        [3,['17'=>array_fill(0,101,'10')]],
    ] as $index=>$case){
        [$category,$input]=$case;
        rejects(fn()=>ProductAttributes::normalize($input,$category,$definitions,$map),
            'invalid canonical input '.$index);
    }
    final class Query {
        public string $op='';public string $table='';public array $values=[];
        public function select($x):self{$this->op='select';return $this;}
        public function from($x):self{$this->table=(string)$x;return $this;}
        public function order($x):self{return $this;}
        public function where($x):self{return $this;}
        public function delete($x):self{$this->op='delete';$this->table=(string)$x;return $this;}
        public function insert($x):self{$this->op='insert';$this->table=(string)$x;return $this;}
        public function columns($x):self{return $this;}
        public function values($x):self{$this->values[]=$x;return $this;}
    }
    final class Db implements DatabaseInterface{
        public ?Query $q=null;public array $calls=[];
        public function getQuery($new=true):Query{return new Query();}
        public function quoteName($x):mixed{return $x;}
        public function quote($x):string{return "'".str_replace("'","''",(string)$x)."'";}
        public function setQuery($q):void{$this->q=$q;}
        public function loadAssocList():array {
            if($this->q->table==='#__minimarket_attributes')return [[
                'id'=>10,'title'=>'Color','input_type'=>'color','is_multiple'=>1,
                'value_source'=>'preset','color_display'=>'both','state'=>1
            ]];
            if($this->q->table==='#__minimarket_attribute_values')return [[
                'id'=>100,'attribute_id'=>10,'title'=>'Red','state'=>1
            ]];
            if($this->q->table==='#__minimarket_product_attribute_scalars')return [[
                'attribute_id'=>15,'value_text'=>null,'value_number'=>null,'value_boolean'=>0
            ]];
            if($this->q->table==='#__minimarket_product_attribute_options')return [[
                'attribute_id'=>10,'value_id'=>100
            ]];
            return [];
        }
        public function execute():void{$this->calls[]=[$this->q->op,$this->q->table,$this->q->values];}
    }
    $db=new Db();
    $loadedDefs=ProductAttributes::definitions($db);
    check($loadedDefs[10]['values'][100]['title']==='Red','definition value IDs loaded');
    check($loadedDefs[10]['is_multiple']===false,'product cardinality always single');
    check(ProductAttributes::load($db,0)===[],'empty unsaved product');
    $loaded=ProductAttributes::load($db,1001);
    check($loaded[15]==='0' && $loaded[10]==='100', 'typed false and one preset ID');
    ProductAttributes::replace($db,1001,$normalized);
    check(count($db->calls)===5,'three canonical tables cleared and two inserted');
    check(count($db->calls[3][2])===3 && count($db->calls[4][2])===3,
       'three scalar rows and three single dictionary links inserted');
    $db->calls=[];
    ProductAttributes::replace($db,1001,['scalars'=>[],'options'=>[],'free_values'=>[]]);
    check(count($db->calls)===3,'clearing values touches three canonical tables only');
    $form=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product.xml');
    check($form!==false && count($form->xpath(
        "//fieldset[@name='attributes']/field[@name='attribute_values' and @type='Productattributes']"))===1,
        'product characteristics still use Joomla Productattributes field');
    echo "PRODUCT_ATTRIBUTES_OK: $count cases\n";
}
