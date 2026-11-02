<?php
/** Pure PHP regression for real variants and media: no Joomla or test DB required. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace MiniMarket\VariantSmoke {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/TextPresetRows.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Service/MediaPath.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Service/ProductGallery.php';
    require __DIR__.'/../src/com_minimarket/admin/src/Service/ProductVariants.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductVariants as V;
    $count=0;
    function ok(bool $good,string $description):void{
        global $count;++$count;
        if(!$good)throw new \RuntimeException('VARIANTS_FAIL: '.$description);
    }
    $definitions=[
        10=>['id'=>10,'title'=>'Цвет','type'=>'color','input_mode'=>'auto','is_variant'=>true,
            'state'=>1,'values'=>[100=>['title'=>'Красный','state'=>1,'color_hex'=>'#FF0000'],
                101=>['title'=>'Синий','state'=>1,'color_hex'=>'#0000FF']]],
        11=>['id'=>11,'title'=>'Размер','type'=>'text','input_mode'=>'presets','is_variant'=>true,
            'text_options'=>'["S","M","L"]','state'=>1,'values'=>[]],
        12=>['id'=>12,'title'=>'Материал','type'=>'text','input_mode'=>'presets','is_variant'=>false,
            'text_options'=>'["Металл","Камень"]','state'=>1,'values'=>[]],
        13=>['id'=>13,'title'=>'Hidden','type'=>'color','is_variant'=>true,'state'=>0,'values'=>[]],
    ];
    $axes=V::axes(2,$definitions,[2=>[10,11,12,13]]);
    ok(count($axes)===2,'only published SKU-defining attributes become axes');
    ok($axes[10]['options'][0]['value']==='d:100','color dictionary ID used, not title');
    ok($axes[11]['options'][1]['value']==='t:M','text presets support size SKU');
    $a=[
      'id'=>'0','sku'=>'RED-S','price'=>'1590.00','stock'=>'5',
      'selection'=>json_encode(['10'=>'d:100','11'=>'t:S']),
      'image'=>'images/variants/red-s.webp#joomlaImage://local-images/variants/red-s.webp?width=200&height=100',
      'image_alt'=>'Красная S','state'=>'1',
      'photos'=>[['path'=>'images/variants/red-s-back.webp','alt'=>'Сзади']],
    ];
    $b=$a;
    $b['sku']='RED-M';$b['stock']='0';$b['price']='1790';$b['image']='images/variants/red-m.webp';
    $b['selection']=json_encode(['10'=>'d:100','11'=>'t:M']);
    $b['photos']=[];
    $rows=V::normalize([$a,$b],$axes);
    ok(count($rows)===2,'two real combinations');
    ok($rows[0]['price']==='1590.00','exact monetary format');
    ok($rows[0]['stock']===5 && $rows[1]['stock']===0,'stock tracks each size; zero is valid');
    ok($rows[0]['image']==='images/variants/red-s.webp','Joomla media metadata stripped');
    ok($rows[0]['photos'][0]['path']==='images/variants/red-s-back.webp','variant multi-photo gallery');
    ok($rows[0]['signature']!==$rows[1]['signature'],'distinct combination signature');
    ok($rows[0]['selection_json']==='{"10":"d:100","11":"t:S"}','stable typed selector JSON');
    ok(V::normalize([],[])===[],'legacy product without variants remains valid');
    ok(V::normalize('{}',[])===[],'empty native subform removes all variants');
    try { V::normalize([$a], []); ok(false,'non-empty SKU without axes must reject'); }
    catch (\InvalidArgumentException $e) {
        ok($e->getMessage()==='COM_MINIMARKET_VARIANTS_NO_AXES',
            'clear server error when selected category has no variant-defining attributes');
    }
    $withRichPhoto=$a;
    $withRichPhoto['photos'][0]['path'] =
        'images/variants/red-s-back.webp#joomlaImage://local-images/variants/red-s-back.webp?width=1140&height=600';
    $cleaned=V::normalize([$withRichPhoto],$axes);
    ok($cleaned[0]['photos'][0]['path']==='images/variants/red-s-back.webp',
        'nested Joomla Media photo metadata stripped before DB write');
    foreach ([
        [[$a,$a],'duplicate combination and SKU'],
        [[$a,array_replace($b,['sku'=>'RED-S'])],'duplicate SKU'],
        [[$a,array_replace($b,['selection'=>$a['selection']])],'duplicate combination'],
        [[array_replace($a,['selection'=>'{"10":"d:100"}'])],'missing size'],
        [[array_replace($a,['selection'=>'{"10":"d:999","11":"t:S"}'])],'foreign option ID'],
        [[array_replace($a,['selection'=>'{"10":"d:100","11":"t:Fake"}'])],'foreign text preset'],
        [[array_replace($a,['selection'=>'{"10":"d:100","11":"t:S","99":"x"}'])],'extra forged property'],
        [[array_replace($a,['stock'=>'-1'])],'negative stock'],
        [[array_replace($a,['stock'=>'4294967296'])],'overlimit stock'],
        [[array_replace($a,['price'=>'1e9'])],'scientific price'],
        [[array_replace($a,['price'=>'1.234'])],'price >2 decimals'],
        [[array_replace($a,['image'=>'images/../../configuration.php'])],'path traversal'],
        [[array_replace($a,['photos'=>[['path'=>'images/../../evil.webp']]])],'photo traversal'],
        [[array_replace($a,['id'=>'123abc'])],'invalid variant id'],
        [[array_replace($a,['sku'=>'<script>'])],'unsafe SKU'],
        [[array_replace($a,['state'=>'7'])],'unsafe publish state'],
    ] as [$bad,$label]) {
        try {V::normalize($bad,$axes);ok(false,$label);}
        catch (\InvalidArgumentException $e) {ok(true,$label);}
    }
    require __DIR__.'/../src/com_minimarket/admin/src/Service/VariantDependencies.php';
    $deps = new class implements \Joomla\Database\DatabaseInterface {
        public array $values=['{"10":"d:100","11":"t:M"}'];
        public function getQuery($new=true):object {
            return new class {
                public function select($v):self{return $this;}
                public function from($v):self{return $this;}
                public function where($v):self{return $this;}
            };
        }
        public function quoteName($value):string{return (string)$value;}
        public function quote($value):string{return "'".str_replace("'","''",(string)$value)."'";}
        public function setQuery($q):void{}
        public function loadColumn():array{return $this->values;}
    };
    $guard=\MiniMarket\Component\Minimarket\Administrator\Service\VariantDependencies::class;
    $guard::assertAttributeChange($deps,10,'color','auto',1,'',
        [['id'=>100],['id'=>101]]);
    ok(true,'used color option can be updated without deletion');
    foreach ([
        [10,'color','auto',0,'',[['id'=>100]]],
        [10,'color','auto',1,'',[['id'=>101]]],
        [11,'text','presets',1,'["S","L"]',[]],
    ] as [$id,$type,$mode,$flag,$text,$dict]) {
        try {
            $guard::assertAttributeChange($deps,$id,$type,$mode,$flag,$text,$dict);
            ok(false,'referenced variant axis cannot be invalidated');
        } catch (\RuntimeException $error) {
            ok($error->getMessage()==='COM_MINIMARKET_VARIANT_REFERENCES_IN_USE',
                'referenced SKU value protected');
        }
    }

    $form=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product_variant.xml');
    $parent=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product.xml');
    ok($form!==false && $parent!==false,'XML forms valid');
    ok(count($parent->xpath("//fieldset[@name='variants']/field[@name='variants' and @type='subform']"))===1,
        'native Joomla subform for SKU rows');
    ok(count($form->xpath("//field[@name='photos' and @type='subform']"))===1,
        'nested Joomla photo subform');
    ok(count($form->xpath("//field[@name='image' and @type='media']"))===1,
        'native Joomla Media for variant cover');
    ok(str_contains(file_get_contents(__DIR__.'/../src/com_minimarket/admin/src/Model/ProductModel.php'),
        'ProductVariants::replace'),'product save uses transaction-backed real SKU rows');
    $client=file_get_contents(__DIR__.'/../src/com_minimarket/media/js/variants.js');
    ok(str_contains($client,'data-mm-variant-axis') && str_contains($client,'details.stock'),
        'site controls respond to chosen SKU and stock');
    echo 'PRODUCT_VARIANTS_OK: '.$count." cases\n";
}
