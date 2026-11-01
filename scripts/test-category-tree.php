<?php
/**
 * MiniMarket 1.2 category trees and cycle regression.
 * No Joomla or database installation required.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function getQuery($new = true);
        public function quoteName($name, $as = null);
        public function quote($value);
        public function setQuery($query);
        public function loadResult();
    }
}

namespace Joomla\CMS\Filter {
    final class OutputFilter
    {
        public static function stringURLSafe(string $value): string
        {
            return strtolower(trim($value));
        }
    }
}

namespace Joomla\CMS\Table {
    use Joomla\Database\DatabaseInterface;

    class Table
    {
        public $id = 0;
        public $parent_id = 0;
        public $title = '';
        public $alias = '';
        public string $error = '';

        public function __construct($table, $primary, protected DatabaseInterface $db)
        {
        }

        public function setError(string $error): void { $this->error = $error; }
        public function getDbo(): DatabaseInterface { return $this->db; }
        public function check() { return true; }
    }
}

namespace MiniMarket\CategoryTreeSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Table\CategoryTable;
    use MiniMarket\Component\Minimarket\Site\Service\CategoryTree;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Table/CategoryTable.php';
    require __DIR__ . '/../src/com_minimarket/site/src/Service/CategoryTree.php';

    final class FakeQuery
    {
        public string $select = '';
        public string $from = '';
        public array $where = [];
        public function select($part): self { $this->select = (string) $part; return $this; }
        public function from($part): self { $this->from = (string) $part; return $this; }
        public function where($part): self { $this->where[] = (string) $part; return $this; }
    }

    final class FakeDb implements DatabaseInterface
    {
        public ?FakeQuery $q = null;

        /** @var array<int, int> */
        public array $parents = [1 => 0, 2 => 1, 3 => 2, 4 => 0, 6 => 7, 7 => 6];

        public function getQuery($new = true): FakeQuery { return new FakeQuery(); }
        public function quoteName($name, $as = null) { return (string) $name; }
        public function quote($value) { return "'" . (string) $value . "'"; }
        public function setQuery($query) { $this->q = $query; }
        public function loadResult()
        {
            if ($this->q?->select === 'parent_id') {
                preg_match('/id = (\d+)/', implode(' ', $this->q->where), $matches);
                $id = (int) ($matches[1] ?? 0);
                return $this->parents[$id] ?? null;
            }

            return 0; // no duplicate alias for these isolated tests
        }
    }

    $checks = 0;
    $failed = [];
    function expect(bool $condition, string $description): void
    {
        global $checks, $failed;
        ++$checks;
        if (!$condition) { $failed[] = $description; }
    }

    $db = new FakeDb();
    $table = new CategoryTable($db);
    $table->id = 5;
    $table->title = 'Child';
    $table->alias = 'child';
    $table->parent_id = 3;
    expect($table->check() === true, 'existing ancestor chain accepted');
    expect($table->parent_id === 3, 'parent not silently reset to root');

    $table->parent_id = 5;
    expect($table->check() === false, 'cannot be its own parent');

    $table->id = 1;
    $table->parent_id = 3;
    expect($table->check() === false, 'cannot select own descendant (cycle)');
    $table->id = 5;
    $table->parent_id = 50;
    expect($table->check() === false, 'nonexistent parent rejected');
    $table->parent_id = 6;
    expect($table->check() === false, 'pre-existing cyclic ancestors rejected');
    $table->parent_id = 0;
    expect($table->check() === true, 'root category accepted');
    $table->parent_id = -1;
    expect($table->check() === false, 'negative parent rejected');

    $tree = new CategoryTree([
        (object) ['id' => 1, 'title' => 'Root', 'parent_id' => 0, 'state' => 1],
        (object) ['id' => 2, 'title' => 'Nested', 'parent_id' => 1, 'state' => 1],
        (object) ['id' => 3, 'title' => 'Leaf', 'parent_id' => 2, 'state' => 1],
        (object) ['id' => 4, 'title' => 'Hidden root', 'parent_id' => 0, 'state' => 0],
        (object) ['id' => 5, 'title' => 'Published child of hidden', 'parent_id' => 4, 'state' => 1],
        (object) ['id' => 6, 'title' => 'Orphan', 'parent_id' => 999, 'state' => 1],
        (object) ['id' => 7, 'title' => 'Loop 1', 'parent_id' => 8, 'state' => 1],
        (object) ['id' => 8, 'title' => 'Loop 2', 'parent_id' => 7, 'state' => 1],
        (object) ['id' => 9, 'title' => 'Second root', 'parent_id' => 0, 'state' => 1],
    ]);

    expect($tree->isVisible(3), 'published descendant of two visible ancestors accessible');
    expect(!$tree->isVisible(5), 'published child of unpublished parent hidden');
    expect(!$tree->isVisible(6), 'orphan parent not public');
    expect(!$tree->isVisible(7), 'cycle cannot appear publicly');
    expect(!$tree->isVisible(0), 'zero is not a category page');
    expect($tree->visibleIds() === [1, 2, 3, 9], 'only visible descendants included in catalog');
    expect(array_map(static fn ($r) => (int) $r->id, $tree->children(0)) === [1, 9],
        'catalog root lists only top-level visible categories');
    expect(array_map(static fn ($r) => (int) $r->id, $tree->children(1)) === [2],
        'root displays direct children only');
    expect(array_map(static fn ($r) => (int) $r->id, $tree->children(2)) === [3],
        'nested category displays direct grandchildren');
    expect($tree->children(4) === [], 'unpublished parent has no visible children');

    if ($failed !== []) {
        fwrite(STDERR, 'CATEGORY_TREE_TEST_FAIL: ' . implode('; ', $failed) . PHP_EOL);
        exit(1);
    }

    echo 'CATEGORY_TREE_TEST_OK: ' . $checks . ' cases' . PHP_EOL;
}
