<?php
/**
 * Self-contained regression gate for published full-assortment SQL.
 *
 * Command: php scripts/test-catalog.php
 * No Joomla installation / database / network access required.
 * This checks Repository's query decisions, not live SQL integration.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($name, $as = null);
        public function setQuery($query, $offset = 0, $limit = 0);
        public function loadObjectList();
        public function loadResult();
    }
}

namespace MiniMarket\CatalogSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;

    \define('_JEXEC', 1);
    require_once __DIR__ . '/../src/com_minimarket/site/src/Service/CategoryTree.php';
    require_once __DIR__ . '/../src/com_minimarket/site/src/Service/CatalogRepository.php';

    final class FakeQuery
    {
        public array $select = [];
        public array $joins = [];
        public array $conditions = [];
        public array $orders = [];
        public string $from = '';

        public function select($columns): self
        {
            foreach ((array) $columns as $column) {
                $this->select[] = (string) $column;
            }

            return $this;
        }

        public function from($table): self
        {
            $this->from = (string) $table;

            return $this;
        }

        public function join($kind, $on): self
        {
            $this->joins[] = (string) $kind . ' ' . (string) $on;

            return $this;
        }

        public function where($condition): self
        {
            $this->conditions[] = (string) $condition;

            return $this;
        }

        public function order($order): self
        {
            $this->orders[] = (string) $order;

            return $this;
        }
    }

    final class FakeDb implements DatabaseInterface
    {
        public ?FakeQuery $lastQuery = null;
        public int $offset = -1;
        public int $limit = -1;
        public int $queryCount = 0;

        public function getQuery($new = true): FakeQuery
        {
            return new FakeQuery();
        }

        public function quoteName($name, $as = null): string
        {
            return (string) $name . ($as === null ? '' : ' AS ' . (string) $as);
        }

        public function setQuery($query, $offset = 0, $limit = 0): void
        {
            $this->lastQuery = $query;
            $this->offset = (int) $offset;
            $this->limit = (int) $limit;
            ++$this->queryCount;
        }

        public function loadObjectList(): array
        {
            if ($this->lastQuery?->from === '#__minimarket_categories') {
                return [(object) [
                    'id' => 1, 'title' => 'Published root', 'alias' => 'published-root',
                    'parent_id' => 0, 'state' => 1,
                ]];
            }

            return [(object) ['id' => 1, 'title' => 'Example product']];
        }

        public function loadResult(): int
        {
            return 74;
        }
    }

    $cases = 0;
    $failures = [];

    function check(bool $ok, string $description): void
    {
        global $cases, $failures;
        ++$cases;

        if (!$ok) {
            $failures[] = $description;
        }
    }

    $db = new FakeDb();
    $repository = new CatalogRepository($db);
    $total = $repository->countAllProducts();
    check($total === 1, 'published listing is counted AFTER effective stock filtering');
    check($db->lastQuery !== null, 'visible listing builds SQL');
    check(str_contains($db->lastQuery?->from ?? '', '#__minimarket_products'), 'listing uses products table');
    check(count($db->lastQuery?->joins ?? []) === 1, 'listing joins categories once');
    check(in_array('p.state = 1', $db->lastQuery?->conditions ?? [], true), 'unpublished products excluded');
    check(in_array('c.state = 1', $db->lastQuery?->conditions ?? [], true), 'unpublished categories excluded');
    check(in_array('p.category_id IN (1)', $db->lastQuery?->conditions ?? [], true),
        'only published ancestor chains listed');
    check(str_contains(implode(' ', $db->lastQuery?->conditions ?? []),
        'mmv.stock>0'), 'variant SKU availability enforced in SQL');
    check(str_contains(implode(' ', $db->lastQuery?->conditions ?? []),
        'p.product_type'), 'bundles participate with derived availability');
    $items = $repository->getAllProductsPage(0, 12, 'price_asc');
    check(count($items) === 1, 'returns currently available published product');
    check($repository->getAllProductsPage(24, 12, 'price_asc')===[], 'page offset applies after filtering');
    check(count($repository->getAllProductsPage(-1, 999, 'newest'))===1,
        'page bounds do not remove eligible rows');
    $repository->getAllProductsPage(0,12,'name');
    $repository->getAllProductsPage(0,12,'price_desc');
    $repository->getAllProductsPage(0,12,'unsafe SQL');
    check($db->queryCount===2,'one cached category read and one complete available listing');
    check($db->offset===0 && $db->limit===0,
        'full in-stock listing loaded before paging, so no gaps from OOS bundles');

    if ($failures !== []) {
        fwrite(STDERR, 'CATALOG_TEST_FAIL: ' . implode('; ', $failures) . PHP_EOL);
        exit(1);
    }

    echo 'CATALOG_TEST_OK: ' . $cases . ' cases' . PHP_EOL;
}
