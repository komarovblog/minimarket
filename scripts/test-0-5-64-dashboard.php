<?php
/** Render the real MiniMarket dashboard with Joomla-compatible stubs. */
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $value):string{return $value;}
        public static function sprintf(string $value,...$args):string{return $value.' '.implode(' ',$args);}
    }
}
namespace Joomla\CMS\Uri {
    final class Uri {
        public static function root():string{return 'https://example.local/';}
    }
}
namespace MiniMarket\Component\Minimarket\Administrator\Service {
    final class AdminEntityLinks {
        public static function view(string $name):string{
            if (!in_array($name,[
                'categories','products','bundles','brands','collections',
                'groups','bundlegroups','attributes','orderoptions','import','feeds','orders','deliveries','returns','returns','reviews','seofilters','promocodes','autodiscounts','recommendations','settings',
                'deliverymethods','paymentmethods','registrationmethods','payments','notificationmethods','stockreport','customers','tickets','workqueue','salesreport','returnsreport','businessreport','planfactreport','documents','fiscalization','notifications','abandonedcarts','fiscalproviders','notificationevents'
            ],true))throw new \RuntimeException('Unknown MiniMarket dashboard view '.$name);
            if ($name==='deliveries') return 'index.php?option=com_minimarket&amp;view=deliveries';
            if ($name==='reviews') return 'index.php?option=com_minimarket&amp;view=reviews&amp;filter_status=pending';
            if ($name==='settings') return 'index.php?option=com_config&amp;view=component&amp;component=com_minimarket';
            return 'index.php?option=com_minimarket&amp;view='.$name;
        }
    }
}
namespace MiniMarket\DashboardTest {
    class View {
        public array $stats=['categories'=>7,'products'=>26,'bundles'=>5,'feeds'=>3,'orders'=>2,'deliveries'=>1,'returns'=>1,'payments'=>2,'documents'=>1,'reviews'=>2,'seofilters'=>3,'promocodes'=>4];
        public function output():string {
            ob_start();
            try {
                require __DIR__.'/../src/com_minimarket/admin/tmpl/dashboard/default.php';
                return (string)ob_get_clean();
            }catch(\Throwable $e){
                ob_end_clean();throw $e;
            }
        }
    }
    $checks=0;
    function verify(bool $ok,string $what):void {
        global $checks; ++$checks;
        if(!$ok)throw new \RuntimeException('MM064_DASHBOARD_FAIL: '.$what);
    }
    \define('_JEXEC',1);
    $html=(new View())->output();
    verify(substr_count($html,'mm-dashboard-band card mm-dashboard-band--')===6,
        'six native dashboard panels including Reports');
    verify((substr_count($html,'class="mm-dashboard-tile text-decoration-none"')===39 &&
        substr_count($html,'class="mm-dashboard-tile mm-dashboard-tile--pending"')===0),
        'thirty-nine working tiles across six groups');
    verify(substr_count($html,'mm-dashboard-tiles')===6,
        'each band has its own responsive grid');
    $names=['COM_MINIMARKET_DASHBOARD_MANAGEMENT',
        'COM_MINIMARKET_DASHBOARD_REPORTS',
        'COM_MINIMARKET_DASHBOARD_SHOP',
        'COM_MINIMARKET_DASHBOARD_MARKETING',
        'COM_MINIMARKET_DASHBOARD_TOOLS',
        'COM_MINIMARKET_DASHBOARD_SETTINGS'];
    $positions=array_map(static fn($name)=>strpos($html,$name),$names);
    verify($positions[0]!==false && $positions[1]!==false
        && $positions[2]!==false && $positions[0]<$positions[1]
        && $positions[1]<$positions[2]
        && $positions[2]<$positions[3] && $positions[3]<$positions[4] && $positions[4]<$positions[5],
        'correct section order Management / Reports / Shop / Marketing / Tools / Settings');
    verify(substr_count($html,'COM_MINIMARKET_DASHBOARD_ORDERS')>=1
        && substr_count($html,'mm-dashboard-tile--pending')===0
        && str_contains($html,'href="index.php?option=com_minimarket&amp;view=orders"'),
        'Orders opens a real administrator list');
    foreach(['categories','products','bundles','brands','collections','groups',
        'bundlegroups','attributes','orderoptions','import','feeds','orders','deliveries',
        'seofilters','promocodes','autodiscounts','recommendations','deliverymethods','paymentmethods','registrationmethods',
        'payments','notificationmethods','stockreport','customers','tickets','workqueue','salesreport','returnsreport','businessreport','planfactreport','documents','fiscalization','notifications','abandonedcarts','fiscalproviders','notificationevents'] as $view) {
        verify(substr_count($html,'&amp;view='.$view.'"')===1,
            'unique functional link for '.$view);
    }
    verify(str_contains($html,'&amp;view=deliveries') && str_contains($html,'&amp;filter_status=pending') &&
        str_contains($html,'option=com_config&amp;view=component&amp;component=com_minimarket'),
        'Deliveries and Settings cards resolve to functional targets');
    verify(!str_contains($html,'data-mm-stat="categories"')
        && !str_contains($html,'data-mm-stat="products"')
        && !str_contains($html,'data-mm-stat="feeds"'),
        'reference catalogs and tools do not show meaningless total-count badges');
    verify(str_contains($html,'mm-dashboard-band-header') &&
        str_contains($html,'mm-dashboard-tile-top') &&
        !str_contains($html,'mm-dashboard-grid'),
        'six stacked full-width Joomla panels with icon above labels');
    verify(str_contains($html,'btn btn-primary d-inline-flex') &&
        str_contains($html,'https://example.local/index.php?option=com_minimarket&amp;view=catalog'),
        'Storefront button is solid and still points to public catalog');
    verify(!str_contains($html,'list-group') &&
        !str_contains($html,'mm-dashboard-section') &&
        !str_contains($html,'mm-dashboard-cards'),
        'old vertical menus and three narrow column cards entirely gone');
    verify(substr_count($html,'<svg class="mm-dashboard-icon"')>=24
      && str_contains($html,'
    $css=(string)file_get_contents(__DIR__.'/../src/com_minimarket/media/css/dashboard.css');
    verify(str_contains($css,'.mm-dashboard-band > .card-body') &&
       str_contains($css,'.mm-dashboard-tile') &&
       str_contains($css,'max-width: none'),
       'responsive tiled layout has scoped Atum-aware styles');
    echo "MM064_DASHBOARD_RENDER_PASS: $checks assertions\n";
}
 . 'not-a-literal')===false,
      'visible inline SVG icons do not depend on missing Joomla glyphs');
    $css=(string)file_get_contents(__DIR__.'/../src/com_minimarket/media/css/dashboard.css');
    verify(str_contains($css,'.mm-dashboard-band > .card-body') &&
       str_contains($css,'.mm-dashboard-tile') &&
       str_contains($css,'max-width: none'),
       'responsive tiled layout has scoped Atum-aware styles');
    echo "MM064_DASHBOARD_RENDER_PASS: $checks assertions\n";
}
