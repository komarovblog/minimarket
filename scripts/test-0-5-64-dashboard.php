<?php
/** Render the real MiniMarket dashboard with Joomla-compatible stubs. */
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $value):string{return $value;}
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
                'groups','bundlegroups','attributes','orderoptions','import','feeds','orders','deliveries','reviews','seofilters','promocodes','settings',
                'deliverymethods','paymentmethods','registrationmethods'
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
        public array $stats=['categories'=>7,'products'=>26,'bundles'=>5,'feeds'=>3,'orders'=>2,'deliveries'=>1,'reviews'=>2,'seofilters'=>3,'promocodes'=>4];
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
    verify(substr_count($html,'mm-dashboard-band card mm-dashboard-band--')===4,
        'four independent native dashboard panels including settings');
    verify((substr_count($html,'class="mm-dashboard-tile text-decoration-none"')===20 &&
        substr_count($html,'class="mm-dashboard-tile mm-dashboard-tile--pending"')===0),
        'twenty working management/store/tools/settings tiles');
    verify(substr_count($html,'mm-dashboard-tiles')===4,
        'each band has its own responsive grid');
    $names=['COM_MINIMARKET_DASHBOARD_MANAGEMENT',
        'COM_MINIMARKET_DASHBOARD_SHOP',
        'COM_MINIMARKET_DASHBOARD_TOOLS',
        'COM_MINIMARKET_DASHBOARD_SETTINGS'];
    $positions=array_map(static fn($name)=>strpos($html,$name),$names);
    verify($positions[0]!==false && $positions[1]!==false
        && $positions[2]!==false && $positions[0]<$positions[1]
        && $positions[1]<$positions[2]
        && $positions[2]<$positions[3],
        'correct section order Management / Store / Tools / Settings');
    verify(substr_count($html,'COM_MINIMARKET_DASHBOARD_ORDERS')>=1
        && substr_count($html,'mm-dashboard-tile--pending')===0
        && str_contains($html,'href="index.php?option=com_minimarket&amp;view=orders"'),
        'Orders opens a real administrator list');
    foreach(['categories','products','bundles','brands','collections','groups',
        'bundlegroups','attributes','orderoptions','import','feeds','orders','deliveries',
        'seofilters','promocodes','deliverymethods','paymentmethods','registrationmethods'] as $view) {
        verify(substr_count($html,'&amp;view='.$view.'"')===1,
            'unique functional link for '.$view);
    }
    verify(str_contains($html,'&amp;view=deliveries') && str_contains($html,'&amp;filter_status=pending') &&
        str_contains($html,'option=com_config&amp;view=component&amp;component=com_minimarket'),
        'Deliveries and Settings cards resolve to functional targets');
    verify(preg_match('/>\\s*7\\s*<\\/span>/', $html)===1 &&
        preg_match('/>\\s*26\\s*<\\/span>/', $html)===1 &&
        preg_match('/>\\s*5\\s*<\\/span>/', $html)===1 &&
        preg_match('/>\\s*3\\s*<\\/span>/', $html)===1,
        'live category/product/bundle/feed counters render alongside buttons');
    verify(str_contains($html,'mm-dashboard-band-header') &&
        str_contains($html,'mm-dashboard-tile-top') &&
        !str_contains($html,'mm-dashboard-grid'),
        'four stacked full-width Joomla panels with icon above labels');
    verify(str_contains($html,'btn btn-primary d-inline-flex') &&
        str_contains($html,'https://example.local/index.php?option=com_minimarket&amp;view=catalog'),
        'Storefront button is solid and still points to public catalog');
    verify(!str_contains($html,'list-group') &&
        !str_contains($html,'mm-dashboard-section') &&
        !str_contains($html,'mm-dashboard-cards'),
        'old vertical menus and three narrow column cards entirely gone');
    foreach(['icon-cart','icon-folder','icon-cube','icon-wrench','icon-share'] as $icon) {
        verify(str_contains($html,'class="'.$icon.' mm-dashboard-'),
            'native icon present to the left of action for '.$icon);
    }
    $css=(string)file_get_contents(__DIR__.'/../src/com_minimarket/media/css/dashboard.css');
    verify(str_contains($css,'.mm-dashboard-band > .card-body') &&
       str_contains($css,'.mm-dashboard-tile') &&
       str_contains($css,'max-width: none'),
       'responsive tiled layout has scoped Atum-aware styles');
    echo "MM064_DASHBOARD_RENDER_PASS: $checks assertions\n";
}
