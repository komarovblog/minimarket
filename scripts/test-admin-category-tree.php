<?php
/** Pure-PHP administrator tree regression; no Joomla or DB required. */
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__ . '/../src/com_minimarket/admin/src/Service/CategoryAdminTree.php';

use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;

$nodes = [];
foreach ([
    [1, 0, 'Root', 1, 0],
    [2, 1, 'Child', 1, 2],
    [3, 2, 'Grandchild', 0, 1],
    [4, 0, 'Other', 1, 0],
    [5, 404, 'Orphan', 1, 0],
    [6, 7, 'Cycle A', 1, 0],
    [7, 6, 'Cycle B', 1, 0],
] as [$id, $parent, $title, $state, $order]) {
    $nodes[] = (object) ['id' => $id, 'parent_id' => $parent, 'title' => $title,
        'alias' => strtolower($title), 'state' => $state, 'sort_order' => $order];
}
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void {
    ++$checks;
    if (!$ok) {
        throw new RuntimeException('ADMIN_CATEGORY_TREE_FAIL: ' . $message);
    }
};
$tree = CategoryAdminTree::flatten($nodes, [1 => 2, 2 => 3, 3 => 5, 4 => 1]);
$ids = array_map(static fn ($r) => (int) $r['item']->id, $tree);
$assert(count($tree) === count($nodes), 'no missing/duplicated node');
$assert(array_slice($ids, 0, 4) === [1, 2, 3, 4], 'preorder siblings and grandchildren');
$assert($tree[2]['depth'] === 2, 'real nesting depth');
$assert($tree[0]['direct_count'] === 2 && $tree[0]['branch_count'] === 10, 'grouped direct/subtree counts');
$assert($tree[1]['branch_count'] === 8, 'subtree rollup');
$assert($tree[0]['has_children'] && !$tree[2]['has_children'], 'collapse controls only for parents');
$assert($tree[4]['broken_parent'], 'orphan visible with warning');
$assert(count(array_filter($tree, static fn ($r) => $r['broken_parent'])) >= 2, 'cycle rescued');
$hits = CategoryAdminTree::flatten($nodes, [], 'Grandchild');
$assert(array_map(static fn ($r) => (int) $r['item']->id, $hits) === [1, 2, 3], 'search preserves all ancestors');
$hits = CategoryAdminTree::flatten($nodes, [], '', 0);
$assert(array_map(static fn ($r) => (int) $r['item']->id, $hits) === [1, 2, 3], 'publication filter preserves ancestors');
echo 'ADMIN_CATEGORY_TREE_OK: ' . $checks . " cases\n";
