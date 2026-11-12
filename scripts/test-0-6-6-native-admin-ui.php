<?php
/** MiniMarket 0.6.6: native list/editor contract and independent toolbar. */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$checks=0;
$ok=static function(bool $yes,string $label)use(&$checks):void{
    ++$checks;
    if(!$yes)throw new RuntimeException('MM066_NATIVE_UI_FAIL: '.$label);
};
$component=simplexml_load_file($root.'minimarket.xml');
$module=simplexml_load_file(dirname(__DIR__).'/src/mod_minimarket_toolbar/mod_minimarket_toolbar.xml');
$ok((string)$component->version==='0.6.6','component manifest version');
$ok((string)$module->version==='1.0.0','separately versioned one-time toolbar');
$ok(is_file(dirname(__DIR__).'/scripts/build-toolbar-package.ps1'),
    'one-time module ZIP has independent build entrypoint');
$release=file_get_contents(__DIR__.'/release-to-desktop.ps1');
$ok(!str_contains($release,'mod_minimarket_toolbar-$version.zip')
    &&str_contains($release,'MINIMARKET_DESKTOP_2_FILES_OK'),
    'ordinary component release does not rebuild toolbar');
$categoryForm=simplexml_load_file($root.'admin/forms/category.xml');
$ok(count($categoryForm->xpath('//fieldset[@name="display"]/field[@name="product_grid_note"]'))===1,
    'product grid note separates subcategory and product cards');
$catFields=[
    $root.'admin/src/Field/CategoryattributesField.php',
    $root.'admin/src/Field/CategoryorderoptionsField.php'
];
foreach($catFields as $path){
    $source=file_get_contents($path);
    $ok(str_contains($source,'mm-category-rules-viewport'),'rules table scroll viewport '.basename($path));
    $ok(str_contains($source,'data-mm-category-list-flag'),'list flag field '.basename($path));
    $ok(str_contains($source,'<select class="form-select form-select-sm" name=')
        &&str_contains($source,"Text::_('COM_MINIMARKET_HIDE')")
        &&str_contains($source,"Text::_('COM_MINIMARKET_SHOW')"),
        'native Show/Hide dropdown '.basename($path));
    $ok(!str_contains($source,'type="checkbox"'),
        'no checkbox in assignment list '.basename($path));
}
$css=file_get_contents($root.'media/css/admin-ui.css');
foreach([1,2,3,4] as $i)
    $ok(str_contains($css,'mm-rules-table--state th:nth-child('.$i.')'),
        'four explicit rule-table column widths '.$i);
$ok(str_contains($css,'mm-rules-table--state {width:100%;min-width:660px'),
    'rule tables prevent vertical text on cramped layouts');
$order=file_get_contents($root.'admin/tmpl/order/create.php');
$ok(str_contains($order,'data-mm-manual-remove') &&
    str_contains($order,'btn btn-outline-danger w-100') &&
    str_contains($order,'icon-trash') &&
    str_contains($order,'aria-label='),
    'manual-order remove button aligns under label and has accessible text');
$ok(str_contains($order,'btn-toolbar') &&
    str_contains($order,'btn btn-outline-secondary'),'native order editor actions');
$editors=[
 'review/create','delivery/create','delivery/default','deliveryzone/edit',
 'seofilter/create','promocode/create','order/create',
 'paymentmethods/default','deliverymethods/default'
];
foreach($editors as $editor){
  $path=$root.'admin/tmpl/'.$editor.'.php';
  $s=file_get_contents($path);
  $ok(!str_contains($s,'class="btn btn-danger" href='),
      'Cancel must not look destructive: '.$editor);
}
$lists=[
 'categories','products','bundles','groups','bundlegroups','attributes',
 'orderoptions','brands','collections','orders','deliveries','reviews',
 'promocodes','seofilters','feeds','deliveryzones','values'
];
foreach($lists as $list){
  $s=file_get_contents($root.'admin/tmpl/'.$list.'/default.php');
  $ok(str_contains($s,'id="adminForm"'),'native list form '.$list);
  $ok(str_contains($s,"grid.checkall")&&str_contains($s,"grid.id"),
      'Joomla select all and row selection '.$list);
  $ok(str_contains($s,'mm-native-list-viewport'),'bounded native table '.$list);
  $ok(str_contains($s,'searchtools')||str_contains($s,'shared/searchtools.php'),
      'native filters and search controls '.$list);
  $view=file_get_contents($root.'admin/src/View/'.ucfirst($list).'/HtmlView.php');
  $ok(str_contains($view,'ToolbarHelper::'),'actions in Joomla top toolbar '.$list);
}
// Method directories are mixed settings and installed plugins. They still have
// native selection and an Edit command, but MUST NOT bulk-delete plugins.
$methods=file_get_contents($root.'admin/tmpl/shared/method-list.php');
$ok(str_contains($methods,'grid.checkall')&&str_contains($methods,'grid.id'),
    'delivery/payment/registration method lists have native selection');
foreach(['Deliverymethods','Paymentmethods','Registrationmethods'] as $view){
   $source=file_get_contents($root.'admin/src/View/'.$view.'/HtmlView.php');
   $ok(str_contains($source,'methodsettings.editSelected'),
       'selected method has a real top-toolbar Edit action '.$view);
}
$methodController=file_get_contents($root.'admin/src/Controller/MethodsettingsController.php');
$ok(str_contains($methodController,'function editSelected') &&
    str_contains($methodController,"checkToken('post')") &&
    str_contains($methodController,'InstalledMethodPlugins::list'),
    'selected method action checks CSRF, view scope and allowed installed plugins');
$feed=file_get_contents($root.'admin/src/Controller/FeedController.php');
$zone=file_get_contents($root.'admin/src/Controller/DeliveryzonesController.php');
foreach(['bulkPublish','bulkUnpublish','bulkDelete'] as $action)
    $ok(str_contains($feed,'function '.$action),'feed checked-row action '.$action);
foreach(['publish','unpublish'] as $action)
    $ok(str_contains($zone,'function '.$action),'zone checked-row action '.$action);
$ok(str_contains($feed,"checkToken('post')")&&str_contains($zone,"checkToken('post')"),
    'batch actions enforce Joomla POST tokens');
$ok(str_contains($feed,'core.edit.state')&&str_contains($zone,'core.edit.state'),
    'batch actions check state permissions');
foreach(['ru-RU','en-GB'] as $locale){
   $words=parse_ini_file($root.'admin/language/'.$locale.'/com_minimarket.ini');
   $ok(!empty($words['COM_MINIMARKET_PRODUCT_GRID_HEADING'])
       &&!empty($words['COM_MINIMARKET_PRODUCT_GRID_HELP']),
       'localized grid informers '.$locale);
}
$ok(is_file(dirname(__DIR__).'/sample-data/minimarket_mysql84_nnop3_update_0_6_6.sql'),
    'optional version-matched demo SQL published independently');
echo 'MM066_NATIVE_UI_PASS: '.$checks." checks\n";
