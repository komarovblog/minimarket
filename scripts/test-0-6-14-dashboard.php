<?php
/** MiniMarket 0.6.14: reports second, one payments tile, valid icon and links. */
declare(strict_types=1);
define('_JEXEC', 1);
$root=__DIR__.'/../src/com_minimarket/';
$ok=static function(bool $yes,string $message):void {
    if(!$yes)throw new RuntimeException('MM614_DASHBOARD_FAIL: '.$message);
};
$manifest=simplexml_load_file($root.'minimarket.xml');
$ok($manifest!==false&&version_compare((string)$manifest->version,'0.6.14','>='),'version');
$markup=(string)file_get_contents($root.'admin/tmpl/dashboard/default.php');
preg_match_all("/'type' => '(management|reports|shop|marketing|tools|settings)'/", $markup, $matches);
$ok($matches[1]===['management','reports','shop','marketing','tools','settings'],
    'all six sections ordered: management, reports, shop, marketing, tools, settings');
$management=strpos($markup, "'type' => 'management'");
$reports=strpos($markup, "'type' => 'reports'");
$shop=strpos($markup, "'type' => 'shop'");
$ok($management!==false&&$reports!==false&&$shop!==false&&$management<$reports&&$reports<$shop,
    'report second');
$managementSection=substr($markup,$management,$reports-$management);
$reportSection=substr($markup,$reports,$shop-$reports);
$ok(substr_count($markup,"'view' => 'payments'")===1, 'no duplicate payment tile');
$ok(str_contains($managementSection,"'view' => 'payments', 'label' => 'COM_MINIMARKET_PAYMENTS_TITLE'")
    && str_contains($managementSection,"'icon' => 'icon-credit-card', 'count' => 'payments_attention'"),
    'payments remains fourth management item with original counter');
$ok(str_contains($reportSection,"'view' => 'stockreport'")
    && substr_count($reportSection,"'view' => 'stockreport'")===1,
    'stock report occurs once, inside second band (other reports may be added)');
$ok(str_contains($markup, "['view'] === 'paymentmethods' || " . '$item' . "['view'] === 'payments'")
    && str_contains($markup, '<rect x="2" y="5" width="20" height="14" rx="2"/>'),
    'payments shares visible inline SVG card icon with payment methods');
$links=(string)file_get_contents($root.'admin/src/Service/AdminEntityLinks.php');
$ok(str_contains($links,"'payments'")&&str_contains($links,"'stockreport'"), 'both routes registered');
$view=(string)file_get_contents($root.'admin/src/View/Dashboard/HtmlView.php');
$ok(str_contains($view, "\$this->stats['payments']=\$this->stats['orders'];"),
    'existing payments counter unchanged');
$css=(string)file_get_contents($root.'media/css/dashboard.css');
$ok(str_contains($css,'repeat(4, minmax(0, 1fr))') &&
    str_contains($css,'repeat(6, minmax(0, 1fr))'),
    'existing responsive tile grids preserved');
$ok(is_file($root.'admin/sql/updates/mysql/0.6.14.sql'), 'no-schema upgrade marker');
echo "MM614_DASHBOARD_PASS\n";
