<?php
/** Catalog facets: AND across attributes, OR within, exact SKU combos. */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/site/src/Service/FacetedCatalog.php';
use MiniMarket\Component\Minimarket\Site\Service\FacetedCatalog as Facet;
$n=0;
function checkFacet(bool $ok,string $description):void {
    global $n;$n++;
    if (!$ok)throw new RuntimeException('FACET_FAIL: '.$description);
}
$facets=[
 'a1'=>['type'=>'text','mode'=>'checkboxes','multiple'=>true,
         'choices'=>[['value'=>'v:1'],['value'=>'v:2']]],
 'a2'=>['type'=>'number','mode'=>'range','multiple'=>false,'choices'=>[]],
 'a3'=>['type'=>'text','mode'=>'text','multiple'=>false,'choices'=>[]],
 's3'=>['type'=>'sku','mode'=>'checkboxes','multiple'=>true,
         'choices'=>[['value'=>'v:10'],['value'=>'v:11']]],
 's4'=>['type'=>'sku','mode'=>'checkboxes','multiple'=>true,
         'choices'=>[['value'=>'v:20'],['value'=>'v:21']]]
];
$facts=['a1'=>['v:1','v:2'],'a2'=>[10.0,22.0],'a3'=>['t:Creative Builder']];
$skus=[['s3'=>'v:10','s4'=>'v:20'],['s3'=>'v:11','s4'=>'v:21']];
checkFacet(Facet::matches($facts,$skus,$facets,[]),'no active filters');
checkFacet(Facet::matches($facts,$skus,$facets,['a1'=>['values'=>['v:1','v:2']]]),'OR within facet');
checkFacet(Facet::matches($facts,$skus,$facets,
    ['a1'=>['values'=>['v:1']],'a2'=>['min'=>20]]),'AND across facets');
checkFacet(!Facet::matches($facts,$skus,$facets,['a2'=>['min'=>23]]),'numeric minimum');
checkFacet(Facet::matches($facts,$skus,$facets,['a2'=>['max'=>10]]),'inclusive maximum');
checkFacet(!Facet::matches($facts,$skus,$facets,['a2'=>['min'=>11,'max'=>21]]),'range requires one real value');
checkFacet(Facet::matches($facts,$skus,$facets,['a3'=>['text'=>'builder']]),'case-insensitive text');
checkFacet(!Facet::matches($facts,$skus,$facets,['a3'=>['text'=>'missing']]),'missing text');
checkFacet(Facet::matches($facts,$skus,$facets,
    ['s3'=>['values'=>['v:10']],'s4'=>['values'=>['v:20']]]),'available SKU combination');
checkFacet(!Facet::matches($facts,$skus,$facets,
    ['s3'=>['values'=>['v:10']],'s4'=>['values'=>['v:21']]]),'no synthetic SKU combinations');
checkFacet(!Facet::matches($facts,[],$facets,
    ['s3'=>['values'=>['v:10']]]),'unavailable SKU never matches');
$selection=Facet::selection([
  'a1'=>['v:1','v:2','v:999'],
  's3'=>'v:10',
  'a2'=>['min'=>'0','max'=>'25'],
  'unknown'=>['v:1']
],$facets);
checkFacet(count($selection['a1']['values'])===3
    && in_array('__invalid__',$selection['a1']['values'],true),
    'unknown values fail closed rather than exposing all products');
checkFacet(!isset($selection['unknown']),'unknown facet ignored');
checkFacet($selection['a2']['min']===0.0 &&
    $selection['a2']['max']===25.0,'numeric zero is valid');
checkFacet(Facet::active($selection),'selection is active');
checkFacet(!Facet::active([]),'empty selection is inactive');
echo 'FACET_ENGINE_OK: '.$n." checks\n";
