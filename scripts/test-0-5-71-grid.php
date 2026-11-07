<?php
/** Six Bootstrap 5 responsive grid tiers and Joomla form dependencies. */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/site/src/Service/CategoryDisplayOptions.php';
use MiniMarket\Component\Minimarket\Site\Service\CategoryDisplayOptions as Grid;
$n=0;
function check71(bool $ok,string $message):void {
    global $n;++$n;
    if(!$ok)throw new RuntimeException('MM71_GRID_FAIL: '.$message);
}
$root=dirname(__DIR__).'/src/com_minimarket/';
$defaults=['xs'=>1,'sm'=>1,'md'=>2,'lg'=>3,'xl'=>4,'xxl'=>4];
$params=new class {
    public array $values=[];
    public function get(string $key,mixed $default=null):mixed {
        return $this->values[$key]??$default;
    }
};
$category=(object)[];
$v=Grid::resolve($category,$params);
foreach ($defaults as $size=>$default) {
    check71($v['cards_'.$size]===$default,'default '.$size);
}
check71($v['layout']==='list','global list default');
check71(Grid::bootstrapGridClasses($v)==='row row-cols-1 row-cols-sm-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-4 g-3',
    'all six native bootstrap row-cols class names');
$params->values=['category_product_layout'=>'grid',
    'category_cards_xs'=>2,'category_cards_sm'=>3,'category_cards_md'=>4,
    'category_cards_lg'=>5,'category_cards_xl'=>6,'category_cards_xxl'=>6];
$category=(object)['product_layout'=>'grid','cards_xs'=>1,'cards_sm'=>-1,
    'cards_md'=>2,'cards_lg'=>-1,'cards_xl'=>3,'cards_xxl'=>-1];
$v=Grid::resolve($category,$params);
check71($v['layout']==='grid','explicit category grid');
check71($v['cards_xs']===1 && $v['cards_sm']===3 &&
        $v['cards_md']===2 && $v['cards_lg']===5 &&
        $v['cards_xl']===3 && $v['cards_xxl']===6,
    'each of the six columns separately inherits or overrides');
$category->product_layout='list';
check71(Grid::resolve($category,$params)['layout']==='list',
    'category list overrides global grid without deleting counts');
check71(Grid::resolve($category,$params)['cards_xs']===2 &&
    Grid::resolve($category,$params)['cards_xl']===6,
    'disabled category counts do not override global List mode');
$category->product_layout='inherit';
check71(Grid::resolve($category,$params)['layout']==='grid',
    'category default inherits global grid');
check71(Grid::resolve($category,$params)['cards_xs']===2 &&
    Grid::resolve($category,$params)['cards_xl']===6,
    'default category layout inherits global grid sizes, not dormant local overrides');
$category->product_layout='grid';
$category->cards_xxl=99;
check71(Grid::resolve($category,$params)['cards_xxl']===6,
    'unsafe values clamped to Bootstrap limit 6');

$global=simplexml_load_file($root.'admin/config.xml');
$local=simplexml_load_file($root.'admin/forms/category.xml');
check71($global!==false && $local!==false,'Joomla XML parses');
foreach ($defaults as $size=>$default) {
    $key='category_cards_'.$size;$short='cards_'.$size;
    $gf=$global->xpath('//fieldset[@name="categories"]/field[@name="'.$key.'"]');
    $cf=$local->xpath('//fieldset[@name="display"]/field[@name="'.$short.'"]');
    check71(count($gf)===1 && count($cf)===1,'matching global/category field '.$size);
    check71((string)$gf[0]['type']==='Gridcards' &&
        (string)$cf[0]['type']==='Gridcards',
        'native Joomla list with dependency helper '.$size);
    check71((string)$gf[0]['default']===(string)$default &&
        (string)$cf[0]['default']==='-1',
        'correct global default and category inherit '.$size);
    $choices=[];
    foreach($cf[0]->option as $option)$choices[]=(string)$option['value'];
    check71($choices===['-1','1','2','3','4','5','6'],
        'category has independent 1-6 and inherit choices '.$size);
}
$js=file_get_contents($root.'media/js/category-grid-settings.js');
check71(str_contains($js,"['list','inherit']") &&
    str_contains($js,"configure('category_','category_product_layout',['list'])"),
    'disabled category default/list and global list conditions');
check71(str_contains($js,'input.disabled=disabled') &&
    str_contains($js,"mirror.type='hidden'") &&
    str_contains($js,'mirror.value=input.value'),
    'disabled form controls keep submitted values via hidden mirrors');
$php=file_get_contents($root.'admin/src/Field/GridcardsField.php');
check71(str_contains($php,'extends ListField') &&
    str_contains($php,'category-grid-settings.js'),
    'new custom field registers JS in category and com_config options');
$install=file_get_contents($root.'script.php');
check71(str_contains($install,'migrateGridOptions') &&
    str_contains($install,"unset(\$params[\$previous])"),
    'old global options are mapped and cleaned after upgrade');
$migration=file_get_contents($root.'admin/sql/updates/mysql/0.5.71.sql');
check71(substr_count($migration,'RENAME COLUMN')===4 &&
    substr_count($migration,'ADD COLUMN')===2,
    'all four saved old overrides kept with two new slots');
foreach (['ru-RU','en-GB'] as $lang) {
    $strings=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
    check71(is_array($strings),$lang.' ini parses');
    foreach ($defaults as $size=>$_) {
        $key='COM_MINIMARKET_CARDS_'.strtoupper($size);
        check71(isset($strings[$key],$strings[$key.'_DESC']),
            $lang.' breakpoint label and viewport explanation '.$size);
    }
}
echo 'MM71_GRID_OK: '.$n." checks\n";
