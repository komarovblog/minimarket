<?php
/** Contact-only order requests during UX development; identity integration later. */
declare(strict_types=1);
\define('_JEXEC',1);
$root=dirname(__DIR__).'/src/com_minimarket/';
$a=function(bool $ok,string $reason):void{
 if(!$ok)throw new RuntimeException('MM80_CHECKOUT_PHASE_FAIL: '.$reason);
};
$c=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
$s=(string)file_get_contents($root.'site/src/Service/OrderStorage.php');
$t=(string)file_get_contents($root.'site/tmpl/checkout/default.php');
$v=(string)file_get_contents($root.'site/src/View/Ordercomplete/HtmlView.php');
$a(!str_contains($c,'$identity->id<1'),'guest checkout not blocked');
$a(str_contains($c,"Session::checkToken('post')") && str_contains($c,'hash_equals($expected,$provided)') && str_contains($c,'CartCatalog($db)'),'server validation preserved');
$a(str_contains($s,'$userId<0') && !str_contains($s,'$userId<1'),'owner 0 supported for interim orders');
$a(!str_contains($t,'!$this->authenticated') && str_contains($t,'name="phone"') && str_contains($t,"HTMLHelper::_('form.token')"),'guest form enabled');
$a(str_contains($v,'$owner!==false') && str_contains($v,'(int)$owner===0 && (int)$identity->id===0') && str_contains($v,'com_minimarket.checkout.last_order'),'anonymous confirmation is session scoped');
$a(file_exists($root.'admin/sql/updates/mysql/0.5.81.sql'),'schema version marker');
echo "MM80_CHECKOUT_PHASE_PASS\n";
