<?php
/**
 * Native admin quicklink render/settings regression without Joomla bootstrap.
 * A Joomla live browser check remains a separate acceptance step.
 */
declare(strict_types=1);

namespace Joomla\CMS {
    final class Factory {
        public static object $app;
        public static function getApplication(): object { return self::$app; }
    }
}
namespace Joomla\CMS\Helper {
    final class ModuleHelper {
        public static string $source;
        public static function getLayoutPath(string $name, string $layout='default'): string {
            if ($name!=='mod_minimarket_quicklink'||$layout!=='default') {
                throw new \RuntimeException('Invalid quicklink layout request');
            }
            return self::$source . '/tmpl/default.php';
        }
    }
}
namespace Joomla\CMS\Router {
    final class Route {
        public static function _(string $target, bool $xhtml=true): string {
            return $target;
        }
    }
}
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $key): string {
            return 'MOD_MINIMARKET_QUICKLINK_OPEN' === $key
                ? 'Open MiniMarket dashboard' : $key;
        }
    }
}
namespace MiniMarket\QuicklinkTest {
    use Joomla\CMS\Factory;
    use Joomla\CMS\Helper\ModuleHelper;
    $root=__DIR__.'/../src/com_minimarket/';
    $dir=$root.'admin/modules/mod_minimarket_quicklink';
    \define('_JEXEC',1);
    ModuleHelper::$source=$dir;
    $checks=0;
    function testIt(bool $ok,string $description): void {
        global $checks;
        ++$checks;
        if (!$ok) throw new \RuntimeException('QUICKLINK_TEST_FAIL: '.$description);
    }
    function render(array $settings=[],bool $authorized=true): string {
        $source=ModuleHelper::$source.'/mod_minimarket_quicklink.php';
        Factory::$app=new class($authorized) {
            public function __construct(private bool $authorized) {}
            public function getIdentity(): object {
                return new class($this->authorized) {
                    public function __construct(private bool $yes) {}
                    public function authorise(string $action,string $extension): bool {
                        return $this->yes && $action==='core.manage' && $extension==='com_minimarket';
                    }
                };
            }
        };
        $params=new class($settings) {
            public function __construct(private array $settings) {}
            public function get(string $key,mixed $default=null): mixed {
                return $this->settings[$key]??$default;
            }
        };
        ob_start();
        try { require $source; return (string)ob_get_clean(); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
    }
    $component=simplexml_load_file($root.'minimarket.xml');
    $module=simplexml_load_file($dir.'/mod_minimarket_quicklink.xml');
    testIt($component!==false && $module!==false,'component and module manifests parse');
    testIt((string)$module['type']==='module' &&
       (string)$module['client']==='administrator' &&
       (string)$module['method']==='upgrade',
       'separate module has Joomla administrator client and upgrade semantics');
    testIt((string)$component->version===(string)$module->version &&
       (string)$component->version==='0.5.62','component and module versions agree');
    testIt(count($component->xpath('/extension/administration/files/folder[text()="modules"]'))===1,
       'component bundles the child administrator module source');
    testIt(count($module->xpath('/extension/files/filename[@module="mod_minimarket_quicklink"]'))===1,
       'Joomla module entrypoint declared in module manifest');
    foreach(['enabled','label','show_icon','show_label'] as $field) {
        testIt(count($module->xpath('/extension/config/fields/fieldset/field[@name="'.$field.'"]'))===1,
            'native module setting '.$field);
    }
    foreach(['ru-RU','en-GB'] as $locale) {
        $strings=parse_ini_file($dir.'/language/'.$locale.'/mod_minimarket_quicklink.ini');
        testIt(is_array($strings) && isset($strings['MOD_MINIMARKET_QUICKLINK_OPEN'])
            && isset($strings['MOD_MINIMARKET_QUICKLINK_ENABLED']),
            'translated module options '.$locale);
        testIt(is_file($dir.'/language/'.$locale.'/mod_minimarket_quicklink.sys.ini'),
            'translated module description '.$locale);
    }
    $html=render();
    testIt(str_contains($html,'class="icon-cart"') &&
        str_contains($html,'>MiniMarket</span>'),
        'published module renders icon and label by default');
    testIt(str_contains($html,'index.php?option=com_minimarket&amp;view=dashboard'),
        'link opens component dashboard, with escaping exactly once');
    testIt(str_contains($html,'aria-label=') &&
        str_contains($html,'title='),
        'accessible name is always included');
    testIt(render(['enabled'=>0])==='',
        'disabled in module params is fully hidden');
    testIt(render([],false)==='',
        'user without com_minimarket core.manage sees no link');
    $html=render(['show_icon'=>0,'show_label'=>1,'label'=>'Dashboard']);
    testIt(!str_contains($html,'icon-cart') && str_contains($html,'>Dashboard<'),
        'label-only variation');
    $html=render(['show_icon'=>1,'show_label'=>0]);
    testIt(str_contains($html,'icon-cart') && !str_contains($html,'mm-quicklink-label'),
        'icon-only variation');
    testIt(render(['show_icon'=>0,'show_label'=>0])==='',
        'no empty clickable element when both icon and label disabled');
    $html=render(['label'=>'<script>alert(1)</script>']);
    testIt(!str_contains($html,'<script>') &&
        str_contains($html,'&lt;script&gt;'),
        'admin-set button label is correctly HTML-escaped');
    $script=(string)file_get_contents($root.'script.php');
    testIt(str_contains($script,'new Installer()') &&
        str_contains($script,'mod_minimarket_quicklink') &&
        str_contains($script,"$"."alreadyInstalled") &&
        str_contains($script,"$"."db->execute()"),
        'component installer installs and configures the module');
    echo "QUICKLINK_RENDER_OK: $checks checks\n";
}
