<?php
/** MiniMarket 0.6.3: settings order, SEO notices, native two-column feeds. */
declare(strict_types=1);
$root=__DIR__.'/../src/com_minimarket/';
$n=0;
$check=static function(bool $ok,string $why) use (&$n): void {
    ++$n;
    if(!$ok)throw new RuntimeException('MM063_UI_FAIL: '.$why);
};
$manifest=simplexml_load_file($root.'minimarket.xml');
$check($manifest!==false && version_compare((string)$manifest->version,'0.6.3','>='),
    'component version 0.6.3');
$module=simplexml_load_file(__DIR__.'/../src/mod_minimarket_toolbar/mod_minimarket_toolbar.xml');
$check($module!==false && (string)$module->version==='1.0.0',
    'one-time module can remain older than component');
$items=$manifest->administration->submenu->menu;
$check(count($items)>0 && in_array((string)$items[count($items)-1],['COM_MINIMARKET_CONFIG_SETTINGS_MENU','COM_MINIMARKET_GENERAL_SETTINGS_TITLE'],true),
    'native left submenu has Settings as its final item');
$dashboard=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
$settingsAt=strpos($dashboard,"['view' => 'settings'");
$registrationAt=strpos($dashboard,"['view' => 'registrationmethods'");
$check($settingsAt!==false && $registrationAt!==false && $settingsAt>$registrationAt,
    'Settings dashboard tile appears after method tiles');
$seo=(string)file_get_contents($root.'admin/tmpl/seofilter/create.php');
$blue=strpos($seo,'<div class="alert alert-info">');
$notice=strpos($seo,"Text::_('COM_MINIMARKET_SEO_FILTER_CATEGORY_NOTICE')");
$help=strpos($seo,"Text::_('COM_MINIMARKET_SEO_FILTER_CHOICE_HELP')");
$check($blue!==false && $notice!==false && $help!==false &&
    $blue<$notice && $notice<$help && !str_contains($seo,'alert alert-secondary'),
    'former dark SEO notice merged at beginning of blue notice');
$feed=(string)file_get_contents($root.'admin/tmpl/feed/default.php');
$name=strpos($feed,"Text::_('COM_MINIMARKET_FEED_NAME')");
$url=strpos($feed,'id="mm-feed-public-url"');
$type=strpos($feed,'id="mm-feed-provider"');
$company=strpos($feed,'id="mm-feed-company"');
$check($name!==false && $url!==false && $type!==false && $company!==false &&
    $name<$url && $url<$type && $type<$company,
    'feed general settings order: name, URL, provider, company');
$check(substr_count($feed,'id="mm-feed-public-url"')===1 &&
    str_contains($feed,'COM_MINIMARKET_FEED_URL_PENDING') &&
    str_contains($feed,'COM_MINIMARKET_FEED_URL_INACTIVE'),
    'new and unpublished feeds explain URL availability');
$check(str_contains($feed,'type="hidden" name="feed[selection_mode]" value="auto"') &&
    !str_contains($feed,'id="mm-feed-mode"'),
    'obsolete auto/manual selection control removed without schema migration');
$selectionStart=strpos($feed,'name="feed[selection_mode]"');
$selectionEnd=strpos($feed,"HTMLHelper::_('uitab.endTab');",$selectionStart);
$selectionMarkup=substr($feed,$selectionStart,$selectionEnd-$selectionStart);
$check($selectionStart!==false && $selectionEnd!==false &&
    substr_count($selectionMarkup,'<div class="col-lg-6">')===2 &&
    str_contains($selectionMarkup,'data-mm-feed-category-count') &&
    str_contains($selectionMarkup,'data-mm-feed-count'),
    'two equal-width Bootstrap selection columns with live counts');
$check(str_contains($feed,'data-mm-feed-category-search') &&
    str_contains($feed,'data-mm-feed-search') &&
    str_contains($feed,'data-mm-feed-cats-all') &&
    str_contains($feed,'data-mm-feed-cats-none') &&
    str_contains($feed,'data-mm-feed-all') &&
    str_contains($feed,'data-mm-feed-none'),
    'parallel search and bulk actions for categories and products');
$check(str_contains($feed,'data-mm-feed-product-filter-option') &&
    str_contains($feed,'data-mm-feed-product-filter-row') &&
    str_contains($feed,'data-mm-feed-product-filter-count'),
    'right-hand multi-category browsing filter is present');
$script=(string)file_get_contents($root.'media/js/feed-editor.js');
$check(str_contains($script,'const excluded=new Set(') &&
    str_contains($script,'const eligible=') &&
    str_contains($script,'syncFilterChoices') &&
    str_contains($script,'const toggleProducts=checked=>'),
    'independent browsing preserves product overrides and category scope');
foreach(['ru-RU','en-GB'] as $lang){
    $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
    foreach(['COM_MINIMARKET_FILTER_PRICE','COM_MINIMARKET_FEED_NAME',
        'COM_MINIMARKET_FEED_URL_PENDING','COM_MINIMARKET_FEED_URL_INACTIVE',
        'COM_MINIMARKET_FEED_FILTER_EMPTY','COM_MINIMARKET_FEED_NO_PRODUCTS']
        as $key){
        $check(isset($ini[$key]) && $ini[$key]!==$key,
            $lang.' translation: '.$key);
    }
}
echo 'MM063_UI_PASS: '.$n." assertions\n";
