<?php
/** MiniMarket 0.6.2: exact / any-applied / unused SEO landing predicates. */
declare(strict_types=1);
define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
require $root.'admin/src/Service/SeoLandingRules.php';
use MiniMarket\Component\Minimarket\Administrator\Service\SeoLandingRules as R;
$n=0;
$check=static function(bool $condition,string $name)use(&$n):void{
    ++$n;if(!$condition)throw new RuntimeException('MM062_SEO_FAIL: '.$name);
};
$rules=R::normalize([
    's20'=>['mode'=>'any'],
    'a10'=>['mode'=>'exact','value'=>'v:101'],
    'a99'=>['mode'=>'unused']
]);
$check(array_keys($rules)===['a10','s20'],'unused omitted and keys sorted');
$check(R::specificity($rules)===1,'exact outranks any');
$check(R::exactFilters($rules)===['a10'=>['v:101']],
    'only exact conditions generate clean landing base filters');
$match=['a10'=>['values'=>['v:101']],'s20'=>['values'=>['v:201']]];
$check(R::matches($rules,$match),'red plus size M');
$check(R::matches($rules,['a10'=>['values'=>['v:101']],
    's20'=>['values'=>['v:201','v:202']]]),'any accepts multiple selected values');
$check(!R::matches($rules,['a10'=>['values'=>['v:101']]]),
    'any REQUIRES an applied filter');
$check(!R::matches($rules,['a10'=>['values'=>['v:101','v:102']],
    's20'=>['values'=>['v:201']]]),'exact accepts only one chosen value');
$check(!R::matches($rules,['a10'=>['values'=>['v:101']],
    's20'=>['values'=>['v:201']],'a99'=>['values'=>['v:99']]]),
    'unused rejects an additional active filter');
$check(!R::matches($rules,['a10'=>['values'=>['v:102']],
    's20'=>['values'=>['v:201']]]),'wrong exact value');
$more=R::normalize([
    'a10'=>['mode'=>'exact','value'=>'v:101'],
    's20'=>['mode'=>'exact','value'=>'v:201']
]);
$pick=R::pick([
    ['id'=>5,'rules'=>$rules],['id'=>9,'rules'=>$more]
],$match);
$check($pick['id']===9,'more exact filters take precedence');
$check(R::overlap($rules,$more),'more specific rule overlaps by design');
$cross=R::normalize([
    'a10'=>['mode'=>'any'],'s20'=>['mode'=>'exact','value'=>'v:201']
]);
$check(R::specificity($cross)===R::specificity($rules)
    &&R::overlap($rules,$cross),'equal rank, intersecting exact/any rejected');
$blue=R::normalize(['a10'=>['mode'=>'exact','value'=>'v:102'],
    's20'=>['mode'=>'any']]);
$check(!R::overlap($rules,$blue),'different exact color is disjoint');
$sizeOnly=R::normalize(['s20'=>['mode'=>'exact','value'=>'v:201']]);
$check(!R::overlap($rules,$sizeOnly),'unused color is incompatible with applied color');
foreach([
    ['a10'=>['mode'=>'exact','value'=>['v:101','v:102']]],
    ['a10'=>['mode'=>'exact','value'=>'v:0']],
    ['a10'=>['mode'=>'bogus']],
    ['badKey'=>['mode'=>'any']],
    ['price'=>['mode'=>'exact','value'=>['min'=>500,'max'=>100]]],
    ['stock'=>['mode'=>'exact','value'=>'b:3']],
    ['a10'=>['mode'=>'any','value'=>'v:101']],
    ['a10'=>['mode'=>'unused']],
    ['s20'=>['mode'=>'any']]
] as $invalid){
    try{R::normalize($invalid);$check(false,'invalid rule accepted');}
    catch(DomainException){$check(true,'invalid condition rejected');}
}
$price=R::normalize(['price'=>['mode'=>'exact','value'=>['min'=>100,'max'=>200]]]);
$check(R::matches($price,['price'=>['min'=>100.0,'max'=>200.0]]),
    'fixed range selected');
$check(!R::matches($price,['price'=>['min'=>100.0,'max'=>300.0]]),
    'range value matters');
$check(R::matches(R::normalize(['sku'=>['mode'=>'exact','value'=>'ABC-1']]),
    ['sku'=>['text'=>'ABC-1']]),'SKU textual exact');
$text=R::normalize(['a11'=>['mode'=>'exact','value'=>['text'=>'cotton']]]);
$check(R::matches($text,['a11'=>['text'=>'cotton']]),'text attribute exact');
$check(R::matches($text,R::rawSelection(['a11'=>'cotton'],$text)),
    'direct landing text input reconstructed');
$range=R::normalize(['a12'=>['mode'=>'exact','value'=>['min'=>80,'max'=>100]]]);
$check(R::matches($range,R::rawSelection(['a12'=>['min'=>'80','max'=>'100']])),
    'numeric characteristic exact range');
$check(!R::matches($rules,R::rawSelection(['a10'=>['v:101']])),
    'wildcard not satisfied when not used');
$check(R::matches($rules,R::rawSelection([
    'a10'=>['v:101'],'s20'=>['v:201','v:202']
])),'raw multiple choice satisfies only any');
$schema=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$upgrade=(string)file_get_contents($root.'admin/sql/updates/mysql/0.6.2.sql');
$check(str_contains($schema,'conditions_json')
    &&str_contains($upgrade,'ADD COLUMN conditions_json TEXT NULL'),
    'new nullable field preserves legacy published SEO data');
$check(!preg_match('/\\b(?:DROP|DELETE|TRUNCATE)\\b/i',$upgrade),
    'no destructive DDL');
$data=(string)file_get_contents($root.'admin/src/Service/SeoFilterData.php');
$controller=(string)file_get_contents($root.'admin/src/Controller/SeofilterController.php');
$adminView=(string)file_get_contents($root.'admin/tmpl/seofilter/create.php');
$editor=(string)file_get_contents($root.'media/js/seo-category-editor.js');
$siteCategory=(string)file_get_contents($root.'site/src/View/Category/HtmlView.php');
$siteSeo=(string)file_get_contents($root.'site/src/View/Seofilter/HtmlView.php');
$ajax=(string)file_get_contents($root.'media/js/faceted-catalog.js');
$check(str_contains($data,'SeoLandingRules::normalize')
    &&str_contains($data,"'conditions_json'"),'canonical rule storage');
$check(str_contains($controller,'SeoCategoryChoices::validateConditions')
    &&str_contains($controller,'SeoLandingResolver::assertUnambiguous')
    &&str_contains($controller,'transactionStart()'),
    'published conflict check under DB transaction');
$check(str_contains($controller,"function state():void")
    &&substr_count($controller,'SeoLandingResolver::assertUnambiguous')===2,
    'both save and separate publication validate overlapping SEO pages');
$bulk=(string)file_get_contents($root.'admin/src/Controller/SeofiltersController.php');
$check(str_contains($bulk,'SeoLandingRules::overlap')
    &&str_contains($bulk,'SeoLandingRules::specificity')
    &&str_contains($bulk,'SeoCategoryChoices::validateConditions')
    &&str_contains($bulk,'ORDER BY id ASC FOR UPDATE')
    &&str_contains($bulk,'transactionStart()'),
    'bulk publication validates overlapping equal-rank rules under locks');

$check(str_contains($adminView,'data-mm-seo-conditions')
    &&str_contains($editor,"data-mm-seo-state")
    &&!str_contains($editor,"input.type='checkbox'"),
    'three-state editor replaces multi-checkbox rules');
$check(str_contains($siteCategory,'SeoLandingResolver::candidates')
    &&str_contains($siteCategory,'SeoLandingRules::pick')
    &&str_contains($siteCategory,'$app->redirect'),
    'storefront resolves canonical landing after normalized facet selection');
$check(str_contains($siteSeo,'SeoLandingRules::rawSelection')
    &&str_contains($siteSeo,"'noindex,follow'")
    &&str_contains($siteSeo,'SeoHelper::setCanonical'),
    'filtered wildcard variants noindex and clean canonical');
$check(str_contains($ajax,'destination.pathname!==location.pathname')
    &&str_contains($ajax,'window.location.assign'),
    'AJAX filter switching changes full Joomla page when SEO route changes');
foreach(['ru-RU','en-GB'] as $locale){
    $ini=(string)file_get_contents($root.'admin/language/'.$locale.'/com_minimarket.ini');
    foreach(['COM_MINIMARKET_SEO_STATE_UNUSED=','COM_MINIMARKET_SEO_STATE_ANY=',
        'COM_MINIMARKET_SEO_STATE_EXACT=','COM_MINIMARKET_SEOFILTER_CONFLICT='] as $key){
        $check(str_contains($ini,$key),$locale.' '.$key);
    }
}
echo "MM062_SEO_PASS: $n checks\n";
