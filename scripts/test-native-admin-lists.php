<?php
/** Joomla 6 native SearchTools contract without a Joomla runtime. */
declare(strict_types=1);
define('_JEXEC', 1);
$count=0;
function ok(bool $yes,string $name):void {global $count;++$count;if(!$yes)throw new RuntimeException('NATIVE_ADMIN_LIST_FAIL: '.$name);}
$root=__DIR__.'/../src/com_minimarket/admin/';
foreach(['products','attributes','categories'] as $name){
    $xml=simplexml_load_file($root.'forms/filter_'.$name.'.xml');
    ok($xml!==false,'filter XML valid: '.$name);
    ok(count($xml->xpath("//fields[@name='filter']/field[@name='search']"))===1,'native SearchTools search: '.$name);
    ok(count($xml->xpath("//fields[@name='filter']/field[@name='published']"))===1,'native published filter: '.$name);
    $view=file_get_contents($root.'src/View/'.ucfirst($name).'/HtmlView.php');
    ok(str_contains($view,"get('FilterForm')") && str_contains($view,"get('ActiveFilters')"),
        'Joomla ListModel filter state provided: '.$name);
    $tpl=file_get_contents($root.'tmpl/'.$name.'/default.php');
    ok(str_contains($tpl,"joomla.searchtools.default"),'core SearchTools rendered: '.$name);
    ok(substr_count($tpl,'name="adminForm"')===1,'one POST form containing native filters and bulk actions: '.$name);
    ok(str_contains($tpl,'grid.checkall') && str_contains($tpl,'grid.id'),
        'Joomla bulk checkboxes retained: '.$name);
    ok(str_contains($tpl,'PublishedButton'), 'Joomla native status indicator: '.$name);
    ok(str_contains($tpl,'name="boxchecked"') && str_contains($tpl,'form.token'),
        'CSRF protection and bulk selection: '.$name);
}
foreach(['products','attributes'] as $name){
    $xml=simplexml_load_file($root.'forms/filter_'.$name.'.xml');
    ok(count($xml->xpath("//fields[@name='list']/field[@name='fullordering']"))===1,
        'sort dropdown: '.$name);
    ok(count($xml->xpath("//fields[@name='list']/field[@name='limit']"))===1,
        'pagination limit dropdown: '.$name);
    $tpl=file_get_contents($root.'tmpl/'.$name.'/default.php');
    ok(str_contains($tpl,'searchtools.sort'),'sortable column headers: '.$name);
    ok(str_contains($tpl,'getListFooter'),'pagination footer: '.$name);
    $model=file_get_contents($root.'src/Model/'.ucfirst($name).'Model.php');
    ok(str_contains($model,"getState('filter.search')"),'filter model uses Joomla saved state: '.$name);
    ok(str_contains($model,"getState('list.ordering'"),'model uses sort allow-list: '.$name);
}
$tree=file_get_contents($root.'tmpl/categories/default.php');
ok(str_contains($tree,'mm-category-tree') && !str_contains($tree,'mm-tree-toggle')
    && !str_contains($tree,'data-mm-depth') && str_contains($tree,'$depth > 0'),
    'native category table keeps visible hierarchy without collapsing controls');
ok(str_contains($tree,"COM_MINIMARKET_CATEGORY_COUNT_BRANCH"),
    'category subtree counts retained');
echo 'NATIVE_ADMIN_LIST_OK: '.$count." cases\n";
