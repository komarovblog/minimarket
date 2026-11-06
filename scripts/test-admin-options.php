<?php
/**
 * Isolated Joomla 6 admin Options visibility regression.
 * No Joomla installation, network, database or user records needed.
 *
 * Run: php scripts/test-admin-options.php
 */
declare(strict_types=1);

namespace Joomla\CMS {
    final class Factory
    {
        public static object $application;

        public static function getApplication(): object
        {
            return self::$application;
        }

        public static function getContainer(): object
        {
            return new class {
                public function get(string $service): object
                {
                    return new class {
                        private string $sql = '';
                        public function quoteName(string $name): string { return '`'.$name.'`'; }
                        public function setQuery(string $sql): void { $this->sql = $sql; }
                        public function loadResult(): int { return 0; }
                    };
                }
            };
        }
    }
}

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}

namespace Joomla\CMS\MVC\View {
    class HtmlView
    {
        public function get(string $name): mixed
        {
            // Joomla ListModel returns a Registry for the State property,
            // while Items / ActiveFilters / ProductCounts are collections.
            return $name === 'State'
                ? new class {
                    public function get(string $key, mixed $default = null): mixed {
                        return $default;
                    }
                }
                : [];
        }

        public function display($tpl = null)
        {
        }
    }
}

namespace Joomla\CMS\Toolbar {
    final class ToolbarHelper
    {
        public static array $preferences = [];
        public static array $titles = [];

        public static function title(string $value): void
        {
            self::$titles[] = $value;
        }

        public static function addNew(string $task): void
        {
        }

        public static function preferences(string $component): void
        {
            self::$preferences[] = $component;
        }
    }
}

namespace MiniMarket\AdminOptionsTest {
    use Joomla\CMS\Factory;
    use Joomla\CMS\Toolbar\ToolbarHelper;
    use MiniMarket\Component\Minimarket\Administrator\View\Dashboard\HtmlView as Dashboard;
    use MiniMarket\Component\Minimarket\Administrator\View\Categories\HtmlView as Categories;
    use MiniMarket\Component\Minimarket\Administrator\View\Products\HtmlView as Products;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/View/Dashboard/HtmlView.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/CategoryAdminTree.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/View/Categories/HtmlView.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/View/Products/HtmlView.php';

    $tests = 0;
    $failures = [];
    $check = static function (bool $pass, string $label) use (&$tests, &$failures): void {
        ++$tests;
        if (!$pass) {
            $failures[] = $label;
        }
    };

    foreach ([
        'superuser' => ['core.admin'],
        'config-editor' => ['core.options'],
        'content-editor' => ['core.create'],
        'none' => [],
    ] as $role => $allowed) {
        Factory::$application = new class($allowed) {
            public object $input;

            public function __construct(private array $allowed)
            {
                $this->input = new class {
                    public function getString(string $key, string $default = ''): string { return $default; }
                    public function getCmd(string $key, string $default = ''): string { return $default; }
                    public function getInt(string $key, int $default = 0): int { return $default; }
                };
            }

            public function getIdentity(): object
            {
                return new class($this->allowed) {
                    public function __construct(private array $allowed)
                    {
                    }

                    public function authorise(string $permission, string $component): bool
                    {
                        return $component === 'com_minimarket'
                            && in_array($permission, $this->allowed, true);
                    }
                };
            }
        };

        foreach ([
            'dashboard' => Dashboard::class,
            'categories' => Categories::class,
            'products' => Products::class,
        ] as $screen => $class) {
            ToolbarHelper::$preferences = [];
            (new $class())->display();
            $isAllowed = in_array('core.admin', $allowed, true)
                || in_array('core.options', $allowed, true);
            $expected = $isAllowed ? ['com_minimarket'] : [];
            $check(ToolbarHelper::$preferences === $expected, "$role / $screen preferences ACL");
        }
    }

    // Use Joomla's standard Options toolbar and avoid a duplicate custom link.
    $dashboard = file_get_contents(
        __DIR__ . '/../src/com_minimarket/admin/tmpl/dashboard/default.php'
    );
    $check($dashboard !== false && !str_contains($dashboard, 'COM_MINIMARKET_OPEN_SETTINGS'),
        'No duplicate custom dashboard Options button');

    foreach ([
        'ru-RU' => 'MiniMarket: Настройки',
        'en-GB' => 'MiniMarket: Options',
    ] as $language => $expected) {
        foreach (['ini', 'sys.ini'] as $ext) {
            $path = __DIR__ . '/../src/com_minimarket/admin/language/' . $language
                . '/com_minimarket.' . $ext;
            $strings = parse_ini_file($path);
            $check(is_array($strings)
                && ($strings['COM_MINIMARKET_CONFIGURATION'] ?? null) === $expected,
                "$language $ext translates Joomla's component Options title");
        }
    }

    $config = file_get_contents(__DIR__ . '/../src/com_minimarket/admin/config.xml');
    $check($config !== false && str_contains($config, 'name="primary_shop_itemid"'),
        'Joomla component Options contains primary menu field');

    if ($failures !== []) {
        fwrite(STDERR, 'ADMIN_OPTIONS_TEST_FAIL: ' . implode('; ', $failures) . PHP_EOL);
        exit(1);
    }

    echo 'ADMIN_OPTIONS_TEST_OK: ' . $tests . ' cases' . PHP_EOL;
}
