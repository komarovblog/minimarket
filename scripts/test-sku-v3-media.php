<?php
/** Standalone checks for SKU-v3 gallery inheritance and text/video overrides. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace Joomla\CMS\Filter {
    final class InputFilter {
        public static function getInstance():self{return new self();}
        public function clean(string $html,string $type):string {
            return preg_replace('~<script\b[^>]*>.*?</script>~is','',$html)??'';
        }
    }
}
namespace MiniMarket\SkuV3Smoke {
    \define('_JEXEC',1);
    foreach (['MediaPath','ProductGallery','ProductVideos','TextPresetRows',
              'VariantValueGallery','VariantMatrix','ProductVariants'] as $name) {
        require __DIR__.'/../src/com_minimarket/admin/src/Service/'.$name.'.php';
    }
    use MiniMarket\Component\Minimarket\Administrator\Service\VariantMatrix as M;
    use MiniMarket\Component\Minimarket\Administrator\Service\VariantValueGallery as G;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductVariants as S;

    $n=0;
    function check(bool $condition,string $description):void {
        global $n; ++$n;
        if(!$condition)throw new \RuntimeException('SKU_V3_FAIL: '.$description);
    }
    $photos=G::normalize([
        ['path'=>'images/red.webp#joomlaImage://local-images/red.webp?width=100','alt'=>'Red front'],
        ['path'=>'images/red-back.webp','alt'=>'Red back'],
    ]);
    check(count($photos)===2,'gallery stores multiple photos once per option value');
    check($photos[0]['path']==='images/red.webp','Joomla Media metadata removed');
    foreach ([
        [['path'=>'images/../config.php','alt'=>'evil']],
        [['path'=>'https://evil.test/image.png','alt'=>'external']],
    ] as $invalid) {
        try {G::normalize([$invalid[0]]);check(false,'unsafe media accepted');}
        catch(\InvalidArgumentException $error){check(true,'invalid media rejected');}
    }
    try {G::normalize([$photos[0],$photos[0]]);check(false,'duplicate path accepted');}
    catch(\InvalidArgumentException $error){check(true,'duplicate path rejected');}
    $dimensions=M::normalize([
        ['code'=>'color','title'=>'Цвет','values'=>[
            ['code'=>'red','title'=>'Красный','photos'=>$photos],
            ['code'=>'blue','title'=>'Синий']]],
        ['code'=>'size','title'=>'Размер','values'=>['S','M']],
    ]);
    check(count($dimensions)===2,'option dimensions still normalize');
    check(count($dimensions[0]['values'][0]['photos'])===2,
        'value-gallery references carried to transaction layer');
    check(count(M::preview($dimensions))===4,
        'gallery does not change SKU matrix identity');
    $axes=[];
    foreach ($dimensions as $dimension) {
        $axes[$dimension['code']]=[
            'title'=>$dimension['title'],
            'options'=>array_map(static fn(array $value):array=>[
                'value'=>'v:'.$value['code'],'title'=>$value['title'],
            ],$dimension['values'])
        ];
    }
    $base=[
        'id'=>'0','sku'=>'RED-S','price'=>'1500.00','stock'=>'5',
        'selection'=>'{"color":"v:red","size":"v:s"}',
        'image'=>'','image_alt'=>'','state'=>1,
        'photos'=>[],
    ];
    $own=$base;
    $own['description_mode']='1';
    $own['introtext_override']='<p>SKU intro</p><script>alert(1)</script>';
    $own['description_override']='<p>SKU full description</p>';
    $own['video_mode']='1';
    $own['videos']=[[
        'title'=>'SKU demo',
        'source'=>'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'media_path'=>'','poster'=>'images/red.webp'
    ]];
    $saved=S::normalize([$own],$axes)[0];
    check($saved['description_mode']===1,'SKU explicitly overrides descriptions');
    check($saved['introtext_override']==='<p>SKU intro</p>','HTML filtered by Joomla');
    check($saved['description_override']==='<p>SKU full description</p>','full description retained');
    check($saved['video_mode']===1 && count($saved['videos'])===1,
        'SKU-specific video normalized');
    check($saved['videos'][0]['source']==='https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'canonical YouTube video source');
    $inherited=S::normalize([$base],$axes)[0];
    check($inherited['description_mode']===0 &&
        $inherited['introtext_override']===null &&
        $inherited['description_override']===null,
        'historical SKU inherits description by default');
    check($inherited['video_mode']===0 && $inherited['videos']===[],
        'historical SKU inherits model videos by default');
    $dormant=$own;
    $dormant['description_mode']='0';
    $dormant['video_mode']='0';
    $cached=S::normalize([$dormant],$axes)[0];
    check($cached['description_mode']===0 &&
        $cached['introtext_override']==='<p>SKU intro</p>',
        'inherited mode keeps dormant own description for later editing');
    check($cached['video_mode']===0 &&
        str_contains(file_get_contents(__DIR__.'/../src/com_minimarket/admin/src/Service/ProductVariants.php'),
            "if (\$r['video_mode'] === 1)"),
        'inherited video mode preserves existing own video references');
    $without=$base;
    $without['video_mode']='2';
    check(S::normalize([$without],$axes)[0]['video_mode']===2,
        'SKU can explicitly hide model videos');
    foreach ([
        ['description_mode'=>'3'],
        ['video_mode'=>'9'],
        ['description_mode'=>1,'introtext_override'=>str_repeat('x',60001)],
        ['video_mode'=>1,'videos'=>[[
            'title'=>'Malicious','source'=>'javascript:alert(1)','media_path'=>'','poster'=>''
        ]]],
    ] as $bad) {
        try {S::normalize([array_replace($base,$bad)],$axes);
            check(false,'invalid override accepted');
        }catch(\InvalidArgumentException $error){
            check(true,'invalid description/video override rejected');
        }
    }
    $variantForm=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product_variant.xml');
    foreach (['description_mode','introtext_override','description_override','video_mode','videos'] as $name) {
        check(count($variantForm->xpath('//field[@name="'.$name.'"]'))===1,
            'Joomla SKU editor field '.$name);
    }
    $valueForm=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product_variant_value.xml');
    check(count($valueForm->xpath('//field[@name="photos" and @type="subform"]'))===1,
        'color photos use Joomla-native gallery +/remove/move');
    $site=file_get_contents(__DIR__.'/../src/com_minimarket/site/tmpl/product/default.php');
    check(str_contains($site,'data-mm-variant-intro') && str_contains($site,'data-mm-sku-videos'),
        'site exposes content and video switches');
    check(!str_contains($site,'data-mm-variant-axis="<?php echo (int) $axisId'),
        'non-numeric option codes survive rendering');
    echo 'SKU_V3_MEDIA_PASS: '.$n." cases\n";
}
