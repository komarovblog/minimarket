<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Builds a real preorder category tree using one category read and one grouped
 * product-count read. Malformed parents and cycles are shown safely at root
 * instead of silently disappearing from the administrator.
 */
final class CategoryAdminTree
{
    /**
     * @param object[] $categories
     * @param array<int, int> $directCounts All products, including unpublished.
     * @return array<int, array{item: object, depth: int, parent_index: ?int, direct_count: int, branch_count: int, has_children: bool, broken_parent: bool}>
     */
    public static function flatten(array $categories, array $directCounts = [], string $search = '', ?int $state = null): array
    {
        $byId = [];
        $children = [];

        foreach ($categories as $category) {
            $id = (int) ($category->id ?? 0);

            if ($id > 0) {
                $byId[$id] = $category;
            }
        }

        foreach ($byId as $id => $category) {
            $children[(int) ($category->parent_id ?? 0)][] = $id;
        }

        $compare = static function (int $a, int $b) use ($byId): int {
            return ((int) ($byId[$a]->sort_order ?? 0) <=> (int) ($byId[$b]->sort_order ?? 0))
                ?: strcasecmp((string) $byId[$a]->title, (string) $byId[$b]->title)
                ?: ($a <=> $b);
        };

        foreach ($children as &$ids) {
            usort($ids, $compare);
        }
        unset($ids);

        $rows = [];
        $visited = [];
        $append = static function (int $id, int $depth, ?int $parentIndex, bool $broken = false) use (
            &$append, &$rows, &$visited, $children, $byId, $directCounts
        ): bool {
            if (isset($visited[$id])) {
                return false;
            }

            $visited[$id] = true;
            $index = count($rows);
            $rows[] = [
                'item' => $byId[$id],
                'depth' => $depth,
                'parent_index' => $parentIndex,
                'direct_count' => max(0, (int) ($directCounts[$id] ?? 0)),
                'branch_count' => 0,
                'has_children' => false,
                'broken_parent' => $broken,
            ];

            foreach ($children[$id] ?? [] as $childId) {
                if ($append($childId, $depth + 1, $index)) {
                    $rows[$index]['has_children'] = true;
                }
            }

            return true;
        };

        $roots = [];
        foreach ($byId as $id => $category) {
            $parentId = (int) ($category->parent_id ?? 0);
            if ($parentId === 0 || !isset($byId[$parentId])) {
                $roots[] = $id;
            }
        }
        usort($roots, $compare);
        foreach ($roots as $id) {
            $parent = (int) ($byId[$id]->parent_id ?? 0);
            $append($id, 0, null, $parent !== 0);
        }

        // Roots of cyclic components: deliberately show them with a warning.
        $remaining = array_keys($byId);
        usort($remaining, $compare);
        foreach ($remaining as $id) {
            if (!isset($visited[$id])) {
                $append($id, 0, null, true);
            }
        }

        // Postorder aggregation is O(n), not one query per category/ancestor.
        for ($i = count($rows) - 1; $i >= 0; --$i) {
            $rows[$i]['branch_count'] += $rows[$i]['direct_count'];
            $parentIndex = $rows[$i]['parent_index'];
            if ($parentIndex !== null) {
                $rows[$parentIndex]['branch_count'] += $rows[$i]['branch_count'];
            }
        }

        $search = trim($search);
        if ($search === '' && $state === null) {
            return $rows;
        }

        // Retain ancestors of every matching result so a nested hit never
        // appears misleadingly as an independent root.
        $keep = [];
        foreach ($rows as $i => $row) {
            $item = $row['item'];
            $matchesState = $state === null || (int) ($item->state ?? 0) === $state;
            $matchesSearch = $search === ''
                || mb_stripos((string) ($item->title ?? ''), $search) !== false
                || mb_stripos((string) ($item->alias ?? ''), $search) !== false
                || (string) ($item->id ?? '') === $search;
            if (!$matchesState || !$matchesSearch) {
                continue;
            }
            for ($j = $i; $j !== null; $j = $rows[$j]['parent_index']) {
                $keep[$j] = true;
            }
        }

        $result = [];
        foreach ($rows as $i => $row) {
            if (isset($keep[$i])) {
                $result[] = $row;
            }
        }

        return $result;
    }
}
