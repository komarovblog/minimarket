<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class BulkDeleteGuard
{
    /** @return int[] Unique positive record IDs. */
    public static function normalizeIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }
        $valid = [];
        foreach ($ids as $id) {
            if (is_scalar($id) && ctype_digit((string) $id) && (int) $id > 0) {
                $valid[(int) $id] = (int) $id;
            }
        }
        return array_values($valid);
    }

    /**
     * One count query for each dependency, never one per selected item.
     * Even selected parent + child are blocked: delete independently after
     * moving children; never silently cascade entire category subtrees.
     */
    public static function assertNoDependencies(DatabaseInterface $db, string $type, array $ids): void
    {
        if (!in_array($type, ['category', 'attribute'], true) || !$ids) {
            throw new \RuntimeException('COM_MINIMARKET_BULK_INVALID');
        }
        $in = implode(',', array_map('intval', $ids));
        $refs = $type === 'category'
            ? [
                ['#__minimarket_categories', 'parent_id'],
                ['#__minimarket_products', 'category_id'],
            ]
            : [
                ['#__minimarket_attribute_values', 'attribute_id'],
                ['#__minimarket_category_attributes', 'attribute_id'],
                ['#__minimarket_product_attribute_scalars', 'attribute_id'],
                ['#__minimarket_product_attribute_options', 'attribute_id'],
                ['#__minimarket_product_attribute_free_values', 'attribute_id'],
            ];
        foreach ($refs as [$table, $column]) {
            $q = $db->getQuery(true)->select('COUNT(*)')
                ->from($db->quoteName($table))
                ->where($db->quoteName($column) . ' IN (' . $in . ')');
            $db->setQuery($q);
            if ((int) $db->loadResult() > 0) {
                throw new \RuntimeException('COM_MINIMARKET_BULK_DEPENDENCY');
            }
        }
    }
}
