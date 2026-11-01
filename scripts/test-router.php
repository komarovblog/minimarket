<?php
/**
 * Isolated MiniMarket Router regression tests.
 *
 * Run from repository root: php scripts/test-router.php
 * No Joomla installation or database needed. Minimal test doubles below
 * intentionally mirror Joomla 6 SiteMenu: published entries do NOT expose
 * a "published" property on their MenuItem objects.
 */

declare(strict_types=1);

namespace Joomla\CMS\Categories {
    interface CategoryFactoryInterface
    {
    }
}

namespace Joomla\CMS\Component\Router {
    interface RouterInterface
    {
        public function preprocess($query);
        public function build(&$query);
        public function parse(&$segments);
    }
}

namespace Joomla\CMS\Menu {
    abstract class AbstractMenu
    {
        /** @var object[] */
        protected array $items;

        public function __construct(array $items)
        {
            $this->items = $items;
        }

        public function getItems($attribute, $value, $firstonly = false)
        {
            $matching = array_values(array_filter(
                $this->items,
                static fn (object $item): bool => ($item->{$attribute} ?? null) === $value
            ));

            return $firstonly ? ($matching[0] ?? null) : $matching;
        }
    }
}

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($name, $as = null);
        public function setQuery($query);
        public function loadObject();
    }
}

namespace MiniMarket\RouterSmoke {
    use Joomla\CMS\Menu\AbstractMenu;
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Site\Service\Router;

    \define('_JEXEC', 1);
    require_once __DIR__ . '/../src/com_minimarket/site/src/Service/Router.php';

    class FakeMenu extends AbstractMenu
    {
    }

    final class FakeQuery
    {
        public string $table = '';
        public array $conditions = [];

        public function select($columns): self
        {
            return $this;
        }

        public function from($table): self
        {
            $this->table = (string) $table;

            return $this;
        }

        public function join($type, $on): self
        {
            return $this;
        }

        public function where($condition): self
        {
            $this->conditions[] = (string) $condition;

            return $this;
        }
    }

    final class FakeDb implements DatabaseInterface
    {
        private ?FakeQuery $query = null;

        public function __construct(public array $records = [])
        {
        }

        public function getQuery($new = true): FakeQuery
        {
            return new FakeQuery();
        }

        public function quoteName($name, $as = null): string
        {
            return (string) $name . ($as === null ? '' : ' AS ' . (string) $as);
        }

        public function setQuery($query): void
        {
            $this->query = $query;
        }

        public function loadObject(): ?object
        {
            if ($this->query === null) {
                return null;
            }

            $view = str_contains($this->query->table, '#__minimarket_products')
                ? 'product'
                : 'category';
            $id = 0;

            foreach ($this->query->conditions as $condition) {
                if (preg_match('/(?:^|[^a-z_])(?:p\.)?id = ([0-9]+)/', $condition, $matches)) {
                    $id = (int) $matches[1];
                    break;
                }
            }

            return isset($this->records[$view][$id])
                ? (object) $this->records[$view][$id]
                : null;
        }
    }

    function menuItem(int $id = 31, string $alias = 'catalog', bool $home = false): object
    {
        // No "published" field! Joomla SiteMenu filtered it before getItems().
        return (object) [
            'id' => $id,
            'component' => 'com_minimarket',
            'query' => ['view' => 'catalog'],
            'alias' => $alias,
            'route' => $home ? '' : $alias,
            'home' => $home,
        ];
    }

    function router(array $items, ?FakeDb $db = null): Router
    {
        return new Router(null, new FakeMenu($items), null, $db ?? new FakeDb([
            'category' => [1 => ['id' => 1, 'alias' => 'testovaa-kategoria']],
            'product' => [1 => ['id' => 1, 'alias' => 'testovyj-tovar']],
        ]));
    }

    $tests = 0;
    $fails = [];

    function expect(bool $condition, string $name): void
    {
        global $tests, $fails;
        ++$tests;

        if (!$condition) {
            $fails[] = $name;
        }
    }

    function expect404(callable $action, string $name): void
    {
        try {
            $action();
            expect(false, $name);
        } catch (\RuntimeException $e) {
            expect($e->getCode() === 404, $name);
        }
    }

    $item = menuItem();
    expect(!property_exists($item, 'published'), 'Joomla 6 SiteMenu omits published');
    $r = router([$item]);
    $query = $r->preprocess(['option' => 'com_minimarket', 'view' => 'catalog']);
    expect(($query['Itemid'] ?? null) === 31, 'catalog link uses published Shop menu item');
    $parts = $r->build($query);
    expect($parts === [], 'catalog route uses exactly its Joomla menu base');
    expect(!isset($query['view']), 'catalog view removed after building');

    $query = $r->preprocess([
        'option' => 'com_minimarket',
        'view' => 'product',
        'id' => 1,
        'Itemid' => 999,
    ]);
    expect($query['Itemid'] === 31, 'foreign Itemid replaced by Shop menu');
    $parts = $r->build($query);
    expect($parts === ['product', 'testovyj-tovar-1'], 'typed product URL built');
    expect(!isset($query['view'], $query['id']), 'typed product query consumed');

    $query = $r->preprocess(['option' => 'com_minimarket', 'view' => 'category', 'id' => 1]);
    expect($r->build($query) === ['category', 'testovaa-kategoria-1'], 'typed category URL built');

    $path = ['product', 'testovyj-tovar-1'];
    expect($r->parse($path) === ['view' => 'product', 'id' => 1], 'typed product parsed');
    expect($path === [], 'parsed segments consumed');
    $path = ['category', 'testovaa-kategoria-1'];
    expect($r->parse($path) === ['view' => 'category', 'id' => 1], 'typed category parsed');
    $path = ['testovyj-tovar-1'];
    expect($r->parse($path) === ['view' => 'product', 'id' => 1], 'legacy product parsed');
    $path = ['testovaa-kategoria-1'];
    expect($r->parse($path) === ['view' => 'category', 'id' => 1], 'legacy category parsed');

    expect404(static function () use ($r): void {
        $path = ['product', 'testovaa-kategoria-1'];
        $r->parse($path);
    }, 'typed path rejects category alias in product route');
    expect404(static function () use ($r): void {
        $path = ['product', 'missing-999999'];
        $r->parse($path);
    }, 'missing product returns 404');
    expect404(static function () use ($r): void {
        $path = ['unknown', 'testovyj-tovar-1'];
        $r->parse($path);
    }, 'unknown type returns 404');
    expect404(static function () use ($r): void {
        $path = ['product', 'testovyj-tovar-1', 'extra'];
        $r->parse($path);
    }, 'extra segments return 404');

    $home = router([menuItem(45, 'home', true)]);
    expect($home->preprocess(['option' => 'com_minimarket'])['Itemid'] === 45, 'explicit Joomla homepage item accepted');

    foreach ([[], [menuItem(31), menuItem(32, 'shop')]] as $items) {
        try {
            router($items)->preprocess(['option' => 'com_minimarket']);
            expect(false, 'invalid menu count rejected');
        } catch (\RuntimeException $e) {
            expect(str_contains($e->getMessage(), 'exactly one'), 'invalid menu count rejected');
        }
    }

    $collision = router([menuItem()], new FakeDb([
        'category' => [1 => ['id' => 1, 'alias' => 'same']],
        'product' => [1 => ['id' => 1, 'alias' => 'same']],
    ]));
    $path = ['category', 'same-1'];
    expect($collision->parse($path)['view'] === 'category', 'typed collision resolves as category');
    $path = ['product', 'same-1'];
    expect($collision->parse($path)['view'] === 'product', 'typed collision resolves as product');
    expect404(static function () use ($collision): void {
        $path = ['same-1'];
        $collision->parse($path);
    }, 'ambiguous legacy URL rejected');

    if ($fails !== []) {
        fwrite(STDERR, 'ROUTER_TEST_FAIL: ' . implode('; ', $fails) . PHP_EOL);
        exit(1);
    }

    echo 'ROUTER_TEST_OK: ' . $tests . ' cases' . PHP_EOL;
}
