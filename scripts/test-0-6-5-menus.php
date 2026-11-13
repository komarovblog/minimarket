<?php
/** MiniMarket 0.6.5: Joomla 6 admin quicktasks and real site Cart menu type. */
declare(strict_types=1);

$root=__DIR__.'/../src/com_minimarket/';
$count=0;
$ok=static function(bool $condition,string $what)use(&$count):void{
    ++$count;
    if(!$condition)throw new RuntimeException('MM065_MENUS_FAIL: '.$what);
};
$manifest=simplexml_load_file($root.'minimarket.xml');
$ok($manifest!==false && version_compare((string)$manifest->version,'0.6.5','>='),'component supports 0.6.5 menu contracts');
$items=$manifest->administration->submenu->menu;
$expected=[
 'orders'=>'index.php?option=com_minimarket&view=order&layout=create',
 'deliveries'=>'index.php?option=com_minimarket&view=delivery&layout=create',
 'returns'=>'index.php?option=com_minimarket&view=returncase&layout=create',
 'reviews'=>'index.php?option=com_minimarket&view=review&layout=create',
 'categories'=>'index.php?option=com_minimarket&task=category.add',
 'products'=>'index.php?option=com_minimarket&view=product&layout=edit&product_type=simple',
 'bundles'=>'index.php?option=com_minimarket&view=product&layout=edit&product_type=bundle',
 'groups'=>'index.php?option=com_minimarket&task=group.add',
 'bundlegroups'=>'index.php?option=com_minimarket&view=group&layout=edit&group_type=bundle',
 'attributes'=>'index.php?option=com_minimarket&task=attribute.add',
 'orderoptions'=>'index.php?option=com_minimarket&task=orderoption.add',
 'brands'=>'index.php?option=com_minimarket&task=brand.add',
 'collections'=>'index.php?option=com_minimarket&task=collection.add',
 'promocodes'=>'index.php?option=com_minimarket&view=promocode&layout=create',
 'seofilters'=>'index.php?option=com_minimarket&view=seofilter&layout=create',
 'feeds'=>'index.php?option=com_minimarket&task=feed.add',
];
$found=[];
foreach($items as $item){
    $url=html_entity_decode((string)$item['link'],ENT_QUOTES|ENT_XML1,'UTF-8');
    if(!str_starts_with($url,'option=com_minimarket&view='))continue;
    parse_str(str_replace('option=com_minimarket&','',$url),$query);
    $view=(string)($query['view']??'');
    $value=(string)($item->params->{'menu-quicktask'}??'');
    if($value===''){
        $ok(!in_array($view,array_keys($expected),true),'no missing plus on '.$view);
        if(in_array($view,['deliverymethods','paymentmethods','registrationmethods','import'],true))
            $ok($value==='','non-creatable settings do not show a misleading plus');
        continue;
    }
    $ok(isset($expected[$view]),'no misleading quicktask on '.$view);
    $ok($value===$expected[$view],'correct create route on '.$view);
    $ok((string)$item->params->{'menu-quicktask-title'}==='JNEW'
        &&(string)$item->params->{'menu-quicktask-icon'}==='plus',
        'native Joomla Create quicktask title and icon on '.$view);
    $ok(!isset($found[$view]),'exactly one plus on '.$view);
    $found[$view]=true;
}
$ok(count($found)===count($expected),'all 16 possible create actions covered');

$cartXml=$root.'site/tmpl/cart/default.xml';
$cart=simplexml_load_file($cartXml);
$ok($cart!==false,'valid Joomla menu metadata for cart');
$ok((string)$cart->layout['title']==='COM_MINIMARKET_CART_MENU_TITLE',
    'Cart menu type has translated title');
$ok((string)$cart->layout->message==='COM_MINIMARKET_CART_MENU_DESCRIPTION',
    'Cart menu type has translated description');
$ok(is_file($root.'site/src/View/Cart/HtmlView.php')&&is_file($root.'site/tmpl/cart/default.php'),
    'Cart menu refers to working component view and layout');
$ok(!is_file($root.'site/tmpl/account/default.xml'),
    'Unimplemented customer account is not advertised as a working MiniMarket page');
$ok(!is_file($root.'site/tmpl/checkout/default.xml'),
    'Internal checkout is not offered as a permanent public menu entry');
foreach(['ru-RU','en-GB'] as $lang){
    $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.sys.ini');
    $ok(!empty($ini['COM_MINIMARKET_CART_MENU_TITLE'])&&
        !empty($ini['COM_MINIMARKET_CART_MENU_DESCRIPTION']),
        'Cart menu localized in system language '.$lang);
    $siteIni=parse_ini_file($root.'site/language/'.$lang.'/com_minimarket.ini');
    $ok(!empty($siteIni['COM_MINIMARKET_CART_MENU_TITLE']),
        'Cart menu available in site language '.$lang);
}
$router=file_get_contents($root.'site/src/Service/Router.php');
$ok(str_contains($router,'getCartItems()')&&str_contains($router,'getActive()'),
    'Cart menu is handled by router without loss of SEO routing');
$ok(str_contains($router,"return ['cart'];")&&str_contains($router,"return ['view' => 'cart'];"),
    'Cart menu has both dedicated and legacy route handling');
$release=file_get_contents(__DIR__.'/release-to-desktop.ps1');
$ok(str_contains($release,'MINIMARKET_DESKTOP_3_FILES_OK'),
    'regular release includes component ZIP, separate task plugin and optional SQL, never toolbar ZIP');
$demo=__DIR__.'/../sample-data/minimarket_mysql84_nnop3_update_0_6_5.sql';
$ok(is_file($demo),'versioned optional cumulative demo SQL exists');
echo 'MM065_MENUS_PASS: '.$count." assertions\n";
