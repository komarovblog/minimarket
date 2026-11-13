<?php
/**
 * Pure calculation + Joomla form/display source contracts for product badges.
 * No Joomla runtime or database needed, so CI runs on PHP 8.4 and 8.5.
 */
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $key): string {return $key;}
        public static function sprintf(string $key,int $number): string {return $key.':'.$number;}
    }
}
namespace MiniMarket\ProductBadgeSmoke {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/ProductBadges.php';
    require __DIR__.'/../src/com_minimarket/site/src/Helper/BadgeHelper.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductBadges as Badges;
    use MiniMarket\Component\Minimarket\Site\Helper\BadgeHelper;
    $checks=0;
    function ok(bool $success,string $message):void {
        global $checks;++$checks;
        if(!$success)throw new \RuntimeException('BADGE_TEST_FAIL: '.$message);
    }
    function product(array $fields):object {
        return (object)array_merge([
            'price'=>'1490.00','old_price'=>null,'badge_sale'=>0,
            'badge_new'=>0,'badge_new_days'=>null,'badge_new_since'=>null,
            'badge_popular'=>0,'badge_popular_days'=>null,'badge_popular_since'=>null,
        ],$fields);
    }
    $at=new \DateTimeImmutable('2026-11-05 12:00:00',new \DateTimeZone('UTC'));
    $missingFields = product(['price'=>'89.00','old_price'=>'99.00','badge_sale'=>1]);
    $oldHandler = set_error_handler(static function(int $severity,string $text):never {
        throw new \ErrorException($text,0,$severity);
    });
    try {
        $defaults = Badges::forProduct($missingFields,$at);
        ok($defaults['sale_display']==='percent' &&
            $defaults['benefit_display']==='money',
            'legacy products without display columns fall back without notices');
        $invalid = product(['badge_sale_display'=>'unexpected',
            'badge_benefit_display'=>'unexpected']);
        $defaults = Badges::forProduct($invalid,$at);
        ok($defaults['sale_display']==='percent' &&
            $defaults['benefit_display']==='money',
            'unknown display enum values fall back safely');
    } finally {
        restore_error_handler();
    }
    $sale=product(['price'=>'1490.00','old_price'=>'1990.00','badge_sale'=>1]);
    ok(Badges::forProduct($sale,$at)['sale']===26,'ceil(500/1990*100) is 26 percent');
    ok(Badges::forProduct($sale,$at)['old_price']==='1990.00','old price preserved');
    ok(Badges::forProduct(product(['price'=>'1499.99','old_price'=>'1500.00','badge_sale'=>1]),$at)['sale']===1,
        'fraction of one percent rounds up to one');
    ok(Badges::forProduct(product(['price'=>'0.00','old_price'=>'500.00','badge_sale'=>1]),$at)['sale']===100,
        'free vs old paid price is 100 percent');
    ok(Badges::forProduct(product(['price'=>'1490.00','old_price'=>'1490.00','badge_sale'=>1]),$at)['sale']===null,
        'no misleading sale when old price equals current');
    ok(Badges::forProduct(product(['price'=>'2000.00','old_price'=>'1490.00','badge_sale'=>1]),$at)['sale']===null,
        'old price lower than current disables sale');
    ok(Badges::forProduct(product(['price'=>'1490.00','old_price'=>'1990.00','badge_sale'=>0]),$at)['sale']===null,
        'manual toggle can hide the badge without removing comparison price');
    ok(Badges::forProduct(product(['price'=>'1490.00','old_price'=>'1990.00','badge_sale'=>0]),$at)['old_price']==='1990.00',
        'comparison remains independently visible');
    $start='2026-11-01 12:00:00';
    $new=product(['badge_new'=>1,'badge_new_days'=>7,'badge_new_since'=>$start]);
    ok(Badges::forProduct($new,new \DateTimeImmutable('2026-11-08 11:59:59',new \DateTimeZone('UTC')))['new'],
        '7-day badge remains visible until boundary');
    ok(!Badges::forProduct($new,new \DateTimeImmutable('2026-11-08 12:00:00',new \DateTimeZone('UTC')))['new'],
        '7-day badge expires exactly at boundary');
    $infinite=product(['badge_new'=>1,'badge_new_days'=>null,'badge_new_since'=>$start]);
    ok(Badges::forProduct($infinite,new \DateTimeImmutable('2050-01-01',new \DateTimeZone('UTC')))['new'],
        'empty duration never expires');
    $popular=product(['badge_popular'=>1,'badge_popular_days'=>1,'badge_popular_since'=>'2026-11-04 12:00:00']);
    ok(!Badges::forProduct($popular,$at)['popular'],'popular badge expires after one day');
    ok(!Badges::forProduct(product(['badge_popular'=>0,'badge_popular_days'=>null]),$at)['popular'],
        'unchecked badge hidden');
    ok(Badges::activationSince(true,false,'2026-10-01 00:00:00','2026-11-05 12:00:00')==='2026-11-05 12:00:00',
        'new toggle starts at new timestamp');
    ok(Badges::activationSince(true,true,'2026-10-01 00:00:00','2026-11-05 12:00:00')==='2026-10-01 00:00:00',
        'ordinary save does not restart timer');
    ok(Badges::activationSince(false,true,'2026-10-01 00:00:00','2026-11-05 12:00:00')===null,
        'turning badge off clears activation timestamp');
    ok(Badges::activationSince(true,false,null,'2026-11-05 12:00:00')==='2026-11-05 12:00:00',
        're-enabling restarts duration');
    $bundle=product(['price'=>'2250.00','old_price'=>'3000.00','badge_sale'=>1]);
    ok(Badges::forProduct($bundle,$at)['sale']===25,'computed bundle price uses the same calculation');
    $bundle->price='2430.00';
    ok(Badges::forProduct($bundle,$at)['sale']===19,
        'changing component-derived bundle price dynamically updates discount');
    $out=BadgeHelper::render($sale);
    ok(str_contains($out,'COM_MINIMARKET_BADGE_SALE_LABEL:26') &&
        str_contains($out,'mm-product-badge--sale'),'badge markup is derived from percentage');
    $moneySale=product(['price'=>'1490.00','old_price'=>'1990.00',
        'badge_sale'=>1,'badge_sale_display'=>'money']);
    ok(str_contains(BadgeHelper::render($moneySale),'500.00') &&
       !str_contains(BadgeHelper::render($moneySale),'BADGE_SALE_LABEL:26'),
       'money-only sale renders amount, not percentage');
    $pctBundle=product(['price'=>'1000.00','product_type'=>'bundle',
        'bundle_price_mode'=>'discount','badge_benefit'=>1,
        'bundle_discount_amount'=>'125.00','bundle_discount_pct'=>'12.50',
        'badge_benefit_display'=>'percent']);
    ok(str_contains(BadgeHelper::render($pctBundle),'12.5%'),
       'percent-only savings uses configured component discount');
    $pctBundle->badge_benefit_display='money';
    ok(str_contains(BadgeHelper::render($pctBundle),'125.00'),
       'money-only savings uses derived amount');
    ok(str_contains(BadgeHelper::oldPrice($sale),'<del') &&
        str_contains(BadgeHelper::oldPrice($sale),'1990.00'),'old price marked as comparison');
    $base=__DIR__.'/../src/com_minimarket/';
    $adminTemplate=file_get_contents($base.'admin/tmpl/product/edit.php');
    ok(str_contains($adminTemplate,"getInput(\$field)")
        && str_contains($adminTemplate,'COM_MINIMARKET_BADGE_SHOW_FLAG')
        && str_contains($adminTemplate,'COM_MINIMARKET_BADGE_SALE_NAME')
        && str_contains($adminTemplate,'COM_MINIMARKET_BADGE_BENEFIT_NAME')
        && str_contains($adminTemplate,"renderField('badge_sale_display')")
        && str_contains($adminTemplate,"renderField('badge_benefit_display')"),
        'flag names are below Show flag; money and percent remain independent');
    $badgeJs=file_get_contents($base.'media/js/product-badges.js');
    ok(str_contains($badgeJs,'wrap.hidden=discounted')
        && !str_contains($badgeJs,'sale.disabled=!eligible')
        && str_contains($badgeJs,'row(saleDisplay).hidden=discounted||!sale?.checked')
        && str_contains($badgeJs,'row(benefitDisplay).hidden=!discounted||!benefit?.checked'),
        'manual sale remains selectable for fixed price; units follow active flags');

    $form=simplexml_load_file($base.'admin/forms/product.xml');
    ok($form!==false,'product form parses');
    ok(count($form->xpath("//fieldset[@name='details']/field[@name='old_price']"))===1,
        'old price next to selling price');
    foreach(['badge_sale','badge_new','badge_popular'] as $flag) {
        ok(count($form->xpath("//fieldset[@name='badges']/field[@name='".$flag."' and @type='checkbox']"))===1,
            'native checkbox '.$flag);
    }
    foreach(['badge_new_days','badge_popular_days'] as $field) {
        ok(count($form->xpath("//fieldset[@name='badges']/field[@name='".$field."' and @type='number']"))===1,
            'optional days '.$field);
    }
    $model=file_get_contents($base.'admin/src/Model/ProductModel.php');
    ok(str_contains($model,'ProductBadges::activationSince') &&
        str_contains($model,"unset(\$data['badge_new_since']") &&
        str_contains($model,'transactionRollback()'),
        'activation dates are server-owned and saved in transaction');
    $table=file_get_contents($base.'admin/src/Table/ProductTable.php');
    ok(str_contains($table,'COM_MINIMARKET_BADGE_SALE_INVALID') &&
        str_contains($table,'COM_MINIMARKET_BADGE_DAYS_INVALID'),
        'sale and duration inputs validated server-side');
    $migration=file_get_contents($base.'admin/sql/updates/mysql/0.5.53.sql');
    ok(str_contains($migration,'ADD COLUMN') &&
        str_contains($migration,'badge_new_since') &&
        str_contains($migration,'badge_popular_since') &&
        !preg_match('/\bDROP\b|\bDELETE\b|\bTRUNCATE\b/i',$migration),
        'migration is additive');
    $repo=file_get_contents($base.'site/src/Service/CatalogRepository.php');
    ok(substr_count($repo,"quoteName('p.badge_sale_display')")===2
       && substr_count($repo,"quoteName('p.badge_benefit_display')")===2
       && substr_count($repo,"quoteName('p.bundle_discount_pct')")===2
       && str_contains($repo,"'badge_sale_display'")
       && str_contains($repo,"'badge_benefit_display'")
       && str_contains($repo,"'bundle_discount_pct'"),
       'badge display and benefit discount present in all public reads');
    ok(substr_count($repo,"quoteName('p.old_price')")===2
       && substr_count($repo,"quoteName('p.badge_new_since')")===2
       && str_contains($repo,"'old_price'")
       && str_contains($repo,"'badge_new_since'"),
       'public product, catalog and category select all badge fields');
    foreach(['catalog','category','product'] as $page) {
        $template=file_get_contents($base.'site/tmpl/'.$page.'/default.php');
        ok(str_contains($template,'BadgeHelper::render(') &&
            str_contains($template,'BadgeHelper::oldPrice('),
            'consistent public badge and old price in '.$page);
        $view=file_get_contents($base.'site/src/View/'.ucfirst($page).'/HtmlView.php');
        ok(str_contains($view,'com_minimarket.badges'),
            'badge stylesheet loaded in '.$page);
    }
    foreach(['admin','site'] as $surface){
        foreach(['ru-RU','en-GB'] as $lang){
            $ini=file_get_contents($base.$surface.'/language/'.$lang.'/com_minimarket.ini');
            foreach(['BADGE_SALE_LABEL','BADGE_NEW_LABEL','BADGE_POPULAR_LABEL'] as $key)
                ok(str_contains($ini,'COM_MINIMARKET_'.$key.'='),
                    $surface.'/'.$lang.' translation '.$key);
        }
    }
    echo 'PRODUCT_BADGES_OK: '.$checks." cases\n";
}
