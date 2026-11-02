<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * A reusable global attribute is assigned to categories, not copied to products.
 * A category may explicitly include (1), exclude (0), or inherit (-1/no row).
 */
final class CategoryAttributes
{
    /**
     * Validates and normalizes the Joomla form's array values.
     *
     * @param mixed $input Map of attribute IDs to -1 (inherit), 0, 1.
     * @param int[] $allowedIds IDs of existing attributes, published or not.
     * @return array<int,int> Explicit rules, absent entries inherit.
     */
    public static function normalizeRules(mixed $input, array $allowedIds): array
    {
        if ($input === null || $input === '') {
            return [];
        }
        if (!is_array($input)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_CATATTR_INVALID');
        }
        $allowed = array_fill_keys(array_map('intval', $allowedIds), true);
        $rules = [];
        foreach ($input as $key => $value) {
            if (!ctype_digit((string) $key) || (int) $key < 1
                || !isset($allowed[(int) $key])
                || !in_array((string) $value, ['-1','0','1'], true)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_CATATTR_INVALID');
            }
            if ((string) $value !== '-1') {
                $rules[(int) $key] = (int) $value;
            }
        }
        ksort($rules, SORT_NUMERIC);
        return $rules;
    }

    /**
     * Resolve one category's effective attributes. Include inherited rules,
     * apply child override, and guard against cycles/broken ancestor chains.
     *
     * @param array<int,int> $parents category_id=>parent_id
     * @param array<int,array<int,int>> $rules category_id=>[attribute_id=>0|1]
     * @return array<int,int> attribute_id=>source category_id (enabled only)
     */
    public static function resolve(int $categoryId, array $parents, array $rules): array
    {
        if ($categoryId < 1) {
            return [];
        }
        $chain = [];
        $seen = [];
        $id = $categoryId;
        while ($id > 0) {
            if (isset($seen[$id]) || !array_key_exists($id, $parents)) {
                return []; // never leak attributes through corrupt hierarchy
            }
            $seen[$id] = true;
            $chain[] = $id;
            $id = (int) $parents[$id];
            if ($id < 0) {
                return [];
            }
        }
        $result = [];
        foreach (array_reverse($chain) as $nodeId) {
            foreach ($rules[$nodeId] ?? [] as $attributeId => $mode) {
                if ((int) $mode === 1) {
                    $result[(int) $attributeId] = $nodeId;
                } elseif ((int) $mode === 0) {
                    unset($result[(int) $attributeId]);
                }
            }
        }
        return $result;
    }

    /** @return array<int,int> Direct category rules, 1=enabled, 0=blocked. */
    public static function loadDirect(DatabaseInterface $db, int $categoryId): array
    {
        if ($categoryId < 1) {
            return [];
        }
        $q = $db->getQuery(true)
            ->select([$db->quoteName('attribute_id'), $db->quoteName('enabled')])
            ->from($db->quoteName('#__minimarket_category_attributes'))
            ->where($db->quoteName('category_id') . ' = ' . $categoryId);
        $db->setQuery($q);
        $rules = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $rules[(int) $row['attribute_id']] = (int) $row['enabled'];
        }
        return $rules;
    }

    /** @return int[] IDs of all known attributes, including unpublished records. */
    public static function allowedIds(DatabaseInterface $db): array
    {
        $q = $db->getQuery(true)->select($db->quoteName('id'))
            ->from($db->quoteName('#__minimarket_attributes'));
        $db->setQuery($q);
        return array_map('intval', (array) $db->loadColumn());
    }

    /** @return int[] Real category IDs for validating reverse form submissions. */
    public static function categoryIds(DatabaseInterface $db): array
    {
        $q = $db->getQuery(true)->select($db->quoteName('id'))
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q);
        return array_map('intval', (array) $db->loadColumn());
    }

    /**
     * Read all hierarchical parents and direct assignments in two queries;
     * no query per property, category or level. Admin-only.
     *
     * @return array{parents:array<int,int>,rules:array<int,array<int,int>>}
     */
    public static function loadHierarchy(DatabaseInterface $db): array
    {
        $q = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('parent_id')])
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q);
        $parents = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $parents[(int) $row['id']] = (int) $row['parent_id'];
        }

        $q = $db->getQuery(true)
            ->select([$db->quoteName('category_id'), $db->quoteName('attribute_id'), $db->quoteName('enabled')])
            ->from($db->quoteName('#__minimarket_category_attributes'));
        $db->setQuery($q);
        $rules = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $rules[(int) $row['category_id']][(int) $row['attribute_id']] = (int) $row['enabled'];
        }
        return ['parents' => $parents, 'rules' => $rules];
    }

    /** @return array<int,int> Direct category ID=>mode for one attribute. */
    public static function loadForAttribute(DatabaseInterface $db, int $attributeId): array
    {
        if ($attributeId < 1) {
            return [];
        }
        $q = $db->getQuery(true)
            ->select([$db->quoteName('category_id'), $db->quoteName('enabled')])
            ->from($db->quoteName('#__minimarket_category_attributes'))
            ->where($db->quoteName('attribute_id') . ' = ' . $attributeId);
        $db->setQuery($q);
        $rows = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $rows[(int) $row['category_id']] = (int) $row['enabled'];
        }
        return $rows;
    }

    /**
     * Same relationship table as category editor; never erase rules of other
     * attributes in those categories. Caller starts category/attribute txn.
     */
    public static function replaceForAttribute(DatabaseInterface $db, int $attributeId, array $rules): void
    {
        if ($attributeId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_CATATTR_INVALID');
        }
        $q = $db->getQuery(true)
            ->delete($db->quoteName('#__minimarket_category_attributes'))
            ->where($db->quoteName('attribute_id') . ' = ' . $attributeId);
        $db->setQuery($q);
        $db->execute();
        if (!$rules) {
            return;
        }
        $q = $db->getQuery(true)
            ->insert($db->quoteName('#__minimarket_category_attributes'))
            ->columns($db->quoteName(['category_id','attribute_id','enabled']));
        foreach ($rules as $categoryId => $mode) {
            $q->values(implode(',', [(int) $categoryId, $attributeId, (int) $mode]));
        }
        $db->setQuery($q);
        $db->execute();
    }

    /**
     * Replace only direct rules; caller holds a transaction that includes
     * category save. Deleting a rule reverts to inheritance, never deletes
     * the shared attribute or a saved product value.
     */
    public static function replaceDirect(DatabaseInterface $db, int $categoryId, array $rules): void
    {
        if ($categoryId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_CATATTR_INVALID');
        }
        $q = $db->getQuery(true)
            ->delete($db->quoteName('#__minimarket_category_attributes'))
            ->where($db->quoteName('category_id') . ' = ' . $categoryId);
        $db->setQuery($q);
        $db->execute();
        if (!$rules) {
            return;
        }
        $q = $db->getQuery(true)
            ->insert($db->quoteName('#__minimarket_category_attributes'))
            ->columns($db->quoteName(['category_id', 'attribute_id', 'enabled']));
        foreach ($rules as $attributeId => $enabled) {
            $q->values(implode(',', [$categoryId, (int) $attributeId, (int) $enabled]));
        }
        $db->setQuery($q);
        $db->execute();
    }
}
