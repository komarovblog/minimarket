<?php
/** Isolated category-card visibility contract: modes, flags, ancestry, and wiring. */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/admin/src/Service/CategoryListVisibility.php';
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryListVisibility as V;
$checks=0;
function checkList(bool $ok,string $message):void{
 global $checks;
 ++$checks;
 if(!$ok)throw new RuntimeException('CATEGORY_CARD_VISIBILITY_FAIL: '.$message);
}
$input=['10'=>'2','11'=>'-1','v10'=>'0','v11'=>'1','12'=>'0','v12'=>'1'];
checkList(V::modesOnly($input)===[10=>'2',11=>'-1',12=>'0'],
    'existing integer category modes retained in unchanged form field');
checkList(V::flags($input,[10,11,12])===[10=>0,11=>1,12=>1],
    'separate card toggles normalized and integer-coded');
checkList(V::formValues([10=>2,11=>0],[10=>0])===[10=>2,11=>0,'v10'=>0,'v11'=>1],
    're-open saved category retains explicit hidden flag and default true');
foreach ([
 ['v1'=>'2'],['v1'=>[]],['v2'=>'1'],['v0'=>'1'],['va'=>'0'],
] as $bad){
 try{V::flags($bad,[1]);checkList(false,'invalid override accepted');}
 catch(InvalidArgumentException){checkList(true,'invalid override rejected');}
}
$parents=[1=>0,2=>1,3=>2,4=>1,5=>6,6=>5];
$rules=[1=>[10=>1,11=>2],2=>[10=>2,11=>0,12=>1],3=>[11=>1],4=>[10=>0]];
$flags=[1=>[10=>0,11=>1],2=>[10=>1,11=>1,12=>0],3=>[11=>0]];
checkList(V::resolve(1,$parents,$rules,$flags)===[10=>false,11=>true],
    'root category flag independent of product required mode');
checkList(V::resolve(2,$parents,$rules,$flags)===[10=>true,12=>false],
    'child override shows inherited-hidden and hides locally linked');
checkList(V::resolve(3,$parents,$rules,$flags)===[10=>true,12=>false,11=>false],
    'grandchild re-enables previously excluded property as hidden');
checkList(V::resolve(4,$parents,$rules,$flags)===[11=>true],
    'disabled attribute absent from category list');
checkList(V::resolve(5,$parents,$rules,$flags)===[],
    'cyclic tree returns empty without exposing properties');
checkList(V::resolve(999,$parents,$rules,$flags)===[],
    'unknown category returns empty');
$base=dirname(__DIR__).'/src/com_minimarket/';
$schema=(string)file_get_contents($base.'admin/sql/install.mysql.sql');
$migration=(string)file_get_contents($base.'admin/sql/updates/mysql/0.5.93.sql');
checkList(substr_count($migration,'ADD COLUMN show_in_list')===2
 && substr_count($schema,'show_in_list')>=2,
 'fresh schema and additive upgrade cover both relationship tables');
$forms=['CategoryattributesField.php','CategoryorderoptionsField.php'];
foreach($forms as $field) {
 $html=(string)file_get_contents($base.'admin/src/Field/'.$field);
 checkList(!str_contains($html,'data-mm-category-list-flag')
    && !str_contains($html,'COM_MINIMARKET_SHOW_IN_PRODUCT_LIST'),
 'category form delegates display exclusively to characteristic/option '.$field);
}
$catModel=(string)file_get_contents($base.'admin/src/Model/CategoryModel.php');
checkList(str_contains($catModel,'CategoryListVisibility::flags')
 && str_contains($catModel,'CategoryListVisibility::modesOnly')
 && str_contains($catModel,'$attributeListFlags')
 && str_contains($catModel,'$optionListFlags'),
 'model validates flags and saves both link types within transaction');
foreach(['CategoryAttributes.php'=>'replaceForAttribute',
         'CategoryOrderOptions.php'=>'replaceForOption'] as $file=>$method){
 $service=(string)file_get_contents($base.'admin/src/Service/'.$file);
 checkList(str_contains($service,'show_in_list')
    && str_contains($service,'$existing')
    && str_contains($service,'function '.$method),
 'reverse entity editor preserves saved card flags '.$file);
}
$public=(string)file_get_contents($base.'site/src/Service/ProductAttributeCatalog.php');
$view=(string)file_get_contents($base.'site/src/View/Category/HtmlView.php');
checkList(!str_contains($public,"\$context==='category' && !\$assignment['list']")
    &&str_contains($public,'display_location'),
 'characteristic display uses its global setting');
checkList(str_contains($view,'CategoryOrderOptions::resolveModes')
    &&!str_contains($view,'CategoryListVisibility::resolve'),
 'option display uses its global setting after category eligibility');
echo 'CATEGORY_CARD_VISIBILITY_PASS: '.$checks." checks\n";
