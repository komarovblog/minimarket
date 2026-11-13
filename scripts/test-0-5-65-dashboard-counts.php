<?php
/**
 * 0.5.65 dashboard: actual view SQL and rendered tile counts, not just static
 * mentions of the ten table names. Joomla is represented by minimal stubs.
 */
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $name):string {return $name;}
        public static function sprintf(string $name,...$args):string{return $name.' '.implode(' ',$args);}
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
    final class AdminInbox {
        public static function load(object $db,int $userId):array{
            if($userId!==7)throw new \RuntimeException('Operator identity mismatch');
            return ['unread'=>4,'capped'=>false];
        }
    }
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
                        public int $id=7;
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
            'order_documents|1=1'=>1,
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
            "orders|status='new' AND deleted_at IS NULL"=>1,
            "shipments|status='new' OR problem_status='open'"=>2,
            "returns|status='new'"=>1,
            "orders|payment_status IN ('unpaid','processing') AND status<>'cancelled' AND deleted_at IS NULL"=>1,
            "support_tickets|status='new'"=>1,
            "fiscal_operations|status IN ('failed','pending')"=>0,
            "cart_snapshots|c.cart_status='active' AND c.item_units>0 AND c.updated_at<=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 60 MINUTE) AND c.updated_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY)"=>2,
        ];
        public function quoteName(string $name,?string $as=null):string{return '`'.$name.'`'.($as!==null?' AS `'.$as.'`':'');}
        public function setQuery(string $query):void{$this->query=$query;$this->queries[]=$query;}
        public function loadResult():int{
            if($this->query==='SELECT COUNT(DISTINCT user_id) FROM `#__minimarket_orders` WHERE user_id>0 AND deleted_at IS NULL')return 1;
            if(!preg_match('/^SELECT COUNT\\(\\*\\) FROM `#__minimarket_([a-z_]+)`(?: AS `c`)? WHERE (.+)$/D',$this->query,$matches)) {
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
    require __DIR__.'/../src/com_minimarket/site/src/Service/CartState.php';
    require __DIR__.'/../src/com_minimarket/site/src/Service/AbandonedCartStore.php';
    require __DIR__.'/../src/com_minimarket/admin/src/View/Dashboard/HtmlView.php';
    Factory::$database=new FakeDatabase();
    $view=new \MiniMarket\Component\Minimarket\Administrator\View\Dashboard\HtmlView();
    $view->display();
    $expected=[
        'categories'=>27,'products'=>78,'bundles'=>3,'brands'=>5,
        'collections'=>6,'groups'=>4,'bundlegroups'=>2,'attributes'=>12,
        'orderoptions'=>7,'feeds'=>3,'orders'=>2,'documents'=>1,'deliveries'=>1,'returns'=>3,'deliveryzones'=>6,
        'reviews'=>2,'seofilters'=>3,'promocodes'=>4,'autodiscounts'=>2,'recommendations'=>3,'payments'=>2,
        'tickets'=>1,'customers'=>1,'workqueue'=>7,'orders_new'=>1,'deliveries_attention'=>2,
        'returns_new'=>1,'payments_attention'=>1,'tickets_new'=>1,'fiscal_attention'=>0,
        'abandonedcarts'=>2,'notifications_unread'=>4,'notifications_capped'=>false
    ];
    verify($view->stats==$expected,'real entity reference counts retained and actionable badges derived from business status');
    verify(count(Factory::$database->queries)===32,'legacy queries plus seven distinct actionable source queries');
    verify(count(array_unique(Factory::$database->queries))===31,
        'seven new actionable SQL predicates are distinct, no synthetic payment rows');
    $html=(new Renderer($view->stats))->html();
    $badgeKeys=['workqueue'=>7,'notifications_unread'=>4,'orders_new'=>1,
        'deliveries_attention'=>2,'returns_new'=>1,'payments_attention'=>1,
        'tickets_new'=>1,'abandonedcarts'=>2,'reviews'=>2];
    foreach($badgeKeys as $key=>$value)
        verify((bool)preg_match('/data-mm-stat="'.preg_quote($key,'/').'"[^>]*>[\\s\\S]*?'
           .(int)$value.'\\s*<\\/span>/u',$html),
           'action-only badge '.$key);
    foreach(['categories','products','bundles','brands','collections','customers',
             'documents','fiscal_attention','feeds','settings','payments'] as $key)
        verify(!str_contains($html,'data-mm-stat="'.$key.'"'),
            'no total or zero badge for '.$key);
    verify(!str_contains($html,'data-mm-stat="none"')
        && !str_contains($html,'<span aria-hidden="true">—</span>'),
        'no meaningless dash badges');
    verify(str_contains($html,'data-mm-stat="orders_new"')
        && !str_contains($html,'class="mm-dashboard-tile mm-dashboard-tile--pending"'),
        'Orders shows new cases instead of all historic orders');
    verify(substr_count($html,'mm-dashboard-band card mm-dashboard-band--')===6 &&
        (substr_count($html,'class="mm-dashboard-tile text-decoration-none"')===41 &&
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
