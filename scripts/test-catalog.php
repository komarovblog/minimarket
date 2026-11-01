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
    check($total === 74, 'total uses database COUNT result');
    check($db->lastQuery !== null, 'total builds SQL');
    check($db->lastQuery?->select === ['COUNT(*)'], 'total uses COUNT not full list');
    check(str_contains($db->lastQuery?->from ?? '', '#__minimarket_products'), 'total uses products table');
    check(count($db->lastQuery?->joins ?? []) === 1, 'total joins category once');
    check(in_array('p.state = 1', $db->lastQuery?->conditions ?? [], true), 'total excludes unpublished products');
    check(in_array('c.state = 1', $db->lastQuery?->conditions ?? [], true), 'total excludes unpublished categories');
    check(in_array('p.category_id IN (1)', $db->lastQuery?->conditions ?? [], true), 'total restricts to visible parent chains');

    $items = $repository->getAllProductsPage(24, 12, 'price_asc');
    check(count($items) === 1, 'page returns database result list');
    check($db->offset === 24, 'page applies server-side offset');
    check($db->limit === 12, 'page applies server-side page size');
    check(count($db->lastQuery?->joins ?? []) === 1, 'page joins category once');
    check(in_array('p.state = 1', $db->lastQuery?->conditions ?? [], true), 'page excludes unpublished products');
    check(in_array('c.state = 1', $db->lastQuery?->conditions ?? [], true), 'page excludes unpublished categories');
    check(($db->lastQuery?->orders[0] ?? '') === 'p.price ASC, p.id ASC', 'price ASC has stable ID tie-break');

    $repository->getAllProductsPage(-100, 999, 'price_desc');
    check($db->offset === 0, 'negative offsets clamp to zero');
    check($db->limit === 48, 'unbounded page sizes clamp to 48');
    check(($db->lastQuery?->orders[0] ?? '') === 'p.price DESC, p.id ASC', 'price DESC has stable ID tie-break');

    $repository->getAllProductsPage(0, 12, 'name');
    check(($db->lastQuery?->orders[0] ?? '') === 'p.title ASC, p.id ASC', 'name order is deterministic');

    $repository->getAllProductsPage(0, 12, 'untrusted SQL; DROP TABLE products');
    check(($db->lastQuery?->orders[0] ?? '') === 'p.id DESC', 'unexpected order falls back to safe allowlist');
    check($db->queryCount === 6, 'one cached category read, one count and four page queries');

    if ($failures !== []) {
        fwrite(STDERR, 'CATALOG_TEST_FAIL: ' . implode('; ', $failures) . PHP_EOL);
        exit(1);
    }

    echo 'CATALOG_TEST_OK: ' . $cases . ' cases' . PHP_EOL;
}
