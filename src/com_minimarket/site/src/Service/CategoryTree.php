<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

/**
 * Published category visibility follows every ancestor, not just the direct
 * category. Existing malformed/orphaned/cyclic chains are hidden safely.
 * IDs and URLs remain stable regardless of category nesting.
 */
final class CategoryTree
{
    /** @var array<int, object> */
    private array $items = [];

    public function __construct(array $categories)
    {
        foreach ($categories as $category) {
            $id = (int) ($category->id ?? 0);

            if ($id > 0) {
                $this->items[$id] = $category;
            }
        }
    }

    public function isVisible(int $id): bool
    {
        $visited = [];

        while ($id > 0) {
            if (isset($visited[$id]) || !isset($this->items[$id])) {
                return false;
            }

            $visited[$id] = true;
            $category = $this->items[$id];

            if ((int) ($category->state ?? 0) !== 1) {
                return false;
            }

            $id = (int) ($category->parent_id ?? 0);

            if ($id < 0) {
                return false;
            }
        }

        return $visited !== [];
    }

    /** @return int[] Published root-to-leaf IDs for attribute inheritance. */
    public function ancestorIds(int $id): array
    {
        if (!$this->isVisible($id)) return [];
        $ids = [];
        while ($id > 0) {
            $ids[] = $id;
            $id = (int) $this->items[$id]->parent_id;
        }
        return array_reverse($ids);
    }

    /** @return int[] */
    public function visibleIds(): array
    {
        $ids = [];

        foreach ($this->items as $id => $item) {
            if ($this->isVisible($id)) {
                $ids[] = $id;
            }
        }

        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    /** All published descendants, excluding this category and hidden branches. */
    public function descendantIds(int $parentId): array
    {
        if (!$this->isVisible($parentId)) return [];
        $children = [];
        foreach ($this->items as $id => $item) {
            if ($this->isVisible($id)) {
                $children[(int)($item->parent_id ?? 0)][] = $id;
            }
        }
        $result = [];
        $seen = [$parentId => true];
        $queue = [$parentId];
        for ($offset = 0; $offset < count($queue); $offset++) {
            $parent = $queue[$offset];
            foreach ($children[$parent] ?? [] as $id) {
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    $result[] = $id;
                    $queue[] = $id;
                }
            }
        }
        sort($result, SORT_NUMERIC);
        return $result;
    }

    /** @return object[] */
    public function children(int $parentId): array
    {
        if ($parentId !== 0 && !$this->isVisible($parentId)) {
            return [];
        }

        $result = [];

        foreach ($this->items as $item) {
            if ((int) ($item->parent_id ?? 0) === $parentId
                && $this->isVisible((int) $item->id)) {
                $result[] = $item;
            }
        }

        usort($result, static function (object $a, object $b): int {
            return ((int) ($a->sort_order ?? 0) <=> (int) ($b->sort_order ?? 0))
                ?: strcasecmp((string) $a->title, (string) $b->title)
                ?: (int) $a->id <=> (int) $b->id;
        });

        return $result;
    }
}
