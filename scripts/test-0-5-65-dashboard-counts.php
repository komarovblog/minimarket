<?php
/**
 * 0.5.65 dashboard: actual view SQL and rendered tile counts, not just static
 * mentions of the ten table names. Joomla is represented by minimal stubs.
 */
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $name):string {return $name;}
    }
}
namespace Joomla\CMS\Uri {
    final class Uri {
        public static function root():string{return 'https://store.example/';}
    }
}
namespace Joomla\CMS\Toolbar {
    final class ToolbarHelper {
        public static function title(string $title):void {}
        public static function preferences(string $component):void {}
    }
}
namespace Joomla\CMS\MVC\View {
    class HtmlView {
        public function display($tpl=null) {}
    }
}
namespace MiniMarket\Component\Minimarket\Administrator\Service {
    final class AdminEntityLinks {
        public static function view(string $name):string {
            return 'index.php?option=com_minimarket&amp;view='.$name;
        }
    }
}
namespace Joomla\CMS {
    final class Factory {
        public static object $database;
        public static function getContainer():object{
            return new class {
                public function get(string $name):object{return Factory::$database;}
            };
        }
        public static function getApplication():object{
            return new class {
                public function getDocument():object {
                    return new class {
                        public function getWebAssetManager():object {
                            return new class {
                                public function registerAndUseStyle(string $name,string $path):object {
                                    if ($name!=='com_minimarket.dashboard' ||
                                        $path!=='media/com_minimarket/css/dashboard.css') {
                                        throw new \RuntimeException('Wrong dashboard stylesheet');
                                    }
                                    return $this;
                                }
                            };
                        }
                    };
                }
                public function getIdentity():object {
                    return new class {
                        public function authorise(string $name,string $target):bool{return false;}
                    };
                }
            };
        }
    }
}
namespace MiniMarket\DashboardCountsTest {
    use Joomla\CMS\Factory;
    $checks=0;
    function verify(bool $ok,string $label):void {
        global $checks;$checks++;
        if (!$ok)throw new \RuntimeException('DASHBOARD_065_FAIL: '.$label);
    }
    final class FakeDatabase {
        public array $queries=[];
        private string $query='';
        private const EXPECTED=[
            'categories|1=1'=>27,
            "products|product_type='simple'"=>78,
            "products|product_type='bundle'"=>3,
            'brands|1=1'=>5,
            'collections|1=1'=>6,
            "product_groups|type<>'bundle'"=>4,
            "product_groups|type='bundle'"=>2,
            'attributes|1=1'=>12,
            'order_options|1=1'=>7,
            'feeds|1=1'=>3,
            'orders|deleted_at IS NULL'=>2,
            "support_tickets|status IN ('new','in_progress')"=>1,
            "orders|payment_status IN ('unpaid','processing') AND status NOT IN ('cancelled','completed') AND deleted_at IS NULL"=>1,
            "shipments|problem_status='open'"=>2,
            "shipments|status IN ('new','in_transit')"=>1,
            "returns|status IN ('new','approved','received')"=>3,
            'delivery_zones|1=1'=>6,
            'reviews|state=0'=>2,
            'seo_filters|1=1'=>3,
            'promo_codes|1=1'=>4,
            'auto_discounts|1=1'=>2,
            'recommendation_settings|1=1'=>3,
        ];
        public function quoteName(string $name):string{return '`'.$name.'`';}
        public function setQuery(string $query):void{$this->query=$query;$this->queries[]=$query;}
        public function loadResult():int{
            if($this->query==='SELECT COUNT(DISTINCT user_id) FROM `#__minimarket_orders` WHERE user_id>0 AND deleted_at IS NULL')return 1;
            if(!preg_match('/^SELECT COUNT\\(\\*\\) FROM `#__minimarket_([a-z_]+)` WHERE (.+)$/D',$this->query,$matches)) {
                throw new \RuntimeException('Unexpected count SQL: '.$this->query);
            }
            $key=$matches[1].'|'.$matches[2];
            if(!array_key_exists($key,self::EXPECTED)) {
                throw new \RuntimeException('Wrong count table/filter: '.$key);
            }
            return self::EXPECTED[$key];
        }
    }
    class Renderer {
        public function __construct(public array $stats){}
        public function html():string {
            ob_start();
            try {
                include __DIR__.'/../src/com_minimarket/admin/tmpl/dashboard/default.php';
                return (string)ob_get_clean();
            }catch(\Throwable $error) {
                ob_end_clean();throw $error;
            }
        }
    }
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/View/Dashboard/HtmlView.php';
    Factory::$database=new FakeDatabase();
    $view=new \MiniMarket\Component\Minimarket\Administrator\View\Dashboard\HtmlView();
    $view->display();
    $expected=[
        'categories'=>27,'products'=>78,'bundles'=>3,'brands'=>5,
        'collections'=>6,'groups'=>4,'bundlegroups'=>2,'attributes'=>12,
        'orderoptions'=>7,'feeds'=>3,'orders'=>2,'deliveries'=>1,'returns'=>3,'deliveryzones'=>6,
        'reviews'=>2,'seofilters'=>3,'promocodes'=>4,'autodiscounts'=>2,'recommendations'=>3,'payments'=>2,
        'tickets'=>1,'customers'=>1,'workqueue'=>7
    ];
    verify($view->stats==$expected,'all real entity counters plus payment obligations use actual records');
    verify(count(Factory::$database->queries)===24,'twenty-four real count queries including promotions and workqueue');
    verify(count(array_unique(Factory::$database->queries))===23,
        'the returns count is deliberately reused for workqueue, other count queries remain distinct');
    $html=(new Renderer($view->stats))->html();
    foreach($expected as $key=>$count){
        if($key==='deliveryzones'){
            verify(!str_contains($html,'data-mm-stat="deliveryzones"'),
                'delivery-zone count does not reintroduce removed dashboard tile');
            continue;
        }
        verify((bool)preg_match('/data-mm-stat="'.preg_quote($key,'/').'"[^>]*>\\s*'.(int)$count.'\\s*<\\/span>/u',$html),
            'real server-rendered number for '.$key);
    }
    verify(substr_count($html,'data-mm-stat="none"')===11,
        'Import, Settings, provider methods and three new financial reports have non-numeric counters');
    verify(substr_count($html,'<span aria-hidden="true">—</span>')===11,
        'All eleven report/settings/tool tiles without counts show dashes, never zero');
    verify(str_contains($html,'data-mm-stat="orders"')
        && !str_contains($html,'class="mm-dashboard-tile mm-dashboard-tile--pending"'),
        'Orders now has a real counter and active link');
    verify(substr_count($html,'mm-dashboard-band card mm-dashboard-band--')===6 &&
        (substr_count($html,'class="mm-dashboard-tile text-decoration-none"')===33 &&
        substr_count($html,'class="mm-dashboard-tile mm-dashboard-tile--pending"')===0),
        'all section panels and native-size tiles preserved');
    $css=(string)file_get_contents(__DIR__.'/../src/com_minimarket/media/css/dashboard.css');
    verify(str_contains($css,'background: #272e36') &&
        str_contains($css,'background: #151c24') &&
        str_contains($css,'.mm-dashboard-count') &&
        !str_contains($css,'background: var(--bs-body-bg, #fff)'),
        'white fallback removed: dark Atum tiles and dark badges');
    verify(str_contains($css,'a.mm-dashboard-tile:hover') &&
        str_contains($css,'a.mm-dashboard-tile:focus-visible'),
        'hover and keyboard focus remain visible');
    foreach(['ru-RU','en-GB'] as $lang) {
        $ini=parse_ini_file(__DIR__.'/../src/com_minimarket/admin/language/'.$lang.'/com_minimarket.ini');
        verify(isset($ini['COM_MINIMARKET_DASHBOARD_NO_COUNT']),
            'historical no-count description translated: '.$lang);
    }
    echo 'DASHBOARD_065_COUNTS_AND_DARK_OK: '.$checks." checks\n";
}
