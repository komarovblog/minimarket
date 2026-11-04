<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Per-category order-option inheritance:
 * absent/-1 = inherit; 0 = disabled; 1 = optional; 2 = required.
 */
final class CategoryOrderOptions
{
    public static function normalize(mixed $input, array $allowedIds): array
    {
        if ($input === null || $input === '') return [];
        if (!is_array($input)) throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
        $allow = array_fill_keys(array_map('intval', $allowedIds), true);
        $result = [];
        foreach ($input as $key => $value) {
            if (!ctype_digit((string) $key) || (int) $key < 1
                || !isset($allow[(int) $key])
                || !in_array((string) $value, ['-1','0','1','2'], true)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
            }
            if ((string) $value !== '-1') $result[(int) $key] = (int) $value;
        }
        ksort($result, SORT_NUMERIC);
        return $result;
    }

    public static function allowedIds(DatabaseInterface $db): array
    {
        $q = $db->getQuery(true)->select('id')
            ->from($db->quoteName('#__minimarket_order_options'));
        $db->setQuery($q);
        return array_map('intval', (array) $db->loadColumn());
    }

    public static function direct(DatabaseInterface $db, int $categoryId): array
    {
        if ($categoryId < 1) return [];
        $q = $db->getQuery(true)->select(['option_id','mode'])
            ->from($db->quoteName('#__minimarket_category_order_options'))
            ->where('category_id = ' . $categoryId);
        $db->setQuery($q);
        $result = [];
        foreach ((array) $db->loadAssocList() as $row) $result[(int) $row['option_id']] = (int) $row['mode'];
        return $result;
    }

    /** Direct mode per category for one reusable order option. */
    public static function directForOption(DatabaseInterface $db, int $optionId): array
    {
        if ($optionId < 1) return [];
        $q = $db->getQuery(true)->select(['category_id', 'mode'])
            ->from($db->quoteName('#__minimarket_category_order_options'))
            ->where('option_id = ' . $optionId);
        $db->setQuery($q);
        $result = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $result[(int) $row['category_id']] = (int) $row['mode'];
        }
        return $result;
    }

    public static function categoryIds(DatabaseInterface $db): array
    {
        $q = $db->getQuery(true)->select('id')
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q);
        return array_map('intval', (array) $db->loadColumn());
    }

    /** Replace only the selected option's assignments, never other options' assignments. */
    public static function replaceForOption(DatabaseInterface $db, int $optionId, array $rules): void
    {
        if ($optionId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
        }
        $q = $db->getQuery(true)->delete($db->quoteName('#__minimarket_category_order_options'))
            ->where('option_id = ' . $optionId);
        $db->setQuery($q);
        $db->execute();
        if (!$rules) return;
        $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_category_order_options'))
            ->columns($db->quoteName(['category_id', 'option_id', 'mode']));
        foreach ($rules as $categoryId => $mode) {
            $q->values((int) $categoryId . ',' . $optionId . ',' . (int) $mode);
        }
        $db->setQuery($q);
        $db->execute();
    }

    /** Read the tree and all direct category rules in two bounded queries. */
    public static function hierarchy(DatabaseInterface $db): array
    {
        $q = $db->getQuery(true)->select(['id','parent_id'])
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q);
        $parents = [];
        foreach ((array) $db->loadAssocList() as $row) $parents[(int) $row['id']] = (int) $row['parent_id'];
        $q = $db->getQuery(true)->select(['category_id','option_id','mode'])
            ->from($db->quoteName('#__minimarket_category_order_options'));
        $db->setQuery($q);
        $rules = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $rules[(int) $row['category_id']][(int) $row['option_id']] = (int) $row['mode'];
        }
        return ['parents' => $parents, 'rules' => $rules];
    }

    /** Return effective enabled option => required/optional, guard broken tree. */
    public static function resolveModes(int $categoryId, array $parents, array $rules): array
    {
        if ($categoryId < 1) return [];
        $seen = [];
        $chain = [];
        for ($id = $categoryId; $id > 0; $id = (int) ($parents[$id] ?? -1)) {
            if (!array_key_exists($id, $parents) || isset($seen[$id])) return [];
            $seen[$id] = true;
            $chain[] = $id;
        }
        $effective = [];
        foreach (array_reverse($chain) as $node) {
            foreach ($rules[$node] ?? [] as $optionId => $mode) {
                if ((int) $mode === 0) unset($effective[(int) $optionId]);
                elseif (in_array((int) $mode, [1,2], true)) $effective[(int) $optionId] = (int) $mode;
            }
        }
        return $effective;
    }

    /** Called from CategoryModel's transaction. Removes only overrides, not the global option. */
    public static function replace(DatabaseInterface $db, int $categoryId, array $rules): void
    {
        if ($categoryId < 1) throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
        $q = $db->getQuery(true)->delete($db->quoteName('#__minimarket_category_order_options'))
            ->where('category_id = ' . $categoryId);
        $db->setQuery($q);
        $db->execute();
        if (!$rules) return;
        $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_category_order_options'))
            ->columns($db->quoteName(['category_id','option_id','mode']));
        foreach ($rules as $optionId => $mode) $q->values($categoryId . ',' . (int) $optionId . ',' . (int) $mode);
        $db->setQuery($q);
        $db->execute();
    }
}
