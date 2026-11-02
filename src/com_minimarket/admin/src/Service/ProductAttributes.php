<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Typed values for category-eligible product attributes.
 * These are product selections, NOT real variant/SKU combinations.
 */
final class ProductAttributes
{
    /** @return array<int,array{id:int,title:string,type:string,is_variant:bool,state:int,values:array<int,array{title:string,state:int}>}> */
    public static function definitions(DatabaseInterface $db): array
    {
        $q = $db->getQuery(true)
            ->select(['id','title','input_type','is_variant','state'])
            ->from($db->quoteName('#__minimarket_attributes'))
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $attributes = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $id = (int) $row['id'];
            $attributes[$id] = [
                'id' => $id,
                'title' => (string) $row['title'],
                'type' => (string) $row['input_type'],
                'is_variant' => (int) $row['is_variant'] === 1,
                'state' => (int) $row['state'],
                'values' => [],
            ];
        }
        $q = $db->getQuery(true)
            ->select(['id','attribute_id','title','state'])
            ->from($db->quoteName('#__minimarket_attribute_values'))
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        foreach ((array) $db->loadAssocList() as $row) {
            $attributeId = (int) $row['attribute_id'];
            if (isset($attributes[$attributeId])) {
                $attributes[$attributeId]['values'][(int) $row['id']] = [
                    'title' => (string) $row['title'],
                    'state' => (int) $row['state'],
                ];
            }
        }
        return $attributes;
    }

    /**
     * Compute eligibility for every category in one read of the hierarchy.
     * All assigned attributes, including drafts, remain editable in admin.
     * Publication state only affects public storefront/filter visibility.
     * @return array<int,int[]> category => allowed attribute IDs
     */
    public static function categoryMap(array $hierarchy, array $definitions): array
    {
        $map = [];
        foreach (array_keys($hierarchy['parents']) as $categoryId) {
            $effective = CategoryAttributes::resolve(
                (int) $categoryId, $hierarchy['parents'], $hierarchy['rules']
            );
            $map[(int) $categoryId] = [];
            foreach ($effective as $attrId => $source) {
                if (isset($definitions[$attrId])) {
                    $map[(int) $categoryId][] = (int) $attrId;
                }
            }
        }
        return $map;
    }

    /** @return array<int,mixed> attr ID => value IDs[] / string / number-string / boolean-string */
    public static function load(DatabaseInterface $db, int $productId): array
    {
        if ($productId < 1) {
            return [];
        }
        $result = [];
        $q = $db->getQuery(true)
            ->select(['attribute_id','value_text','value_number','value_boolean'])
            ->from($db->quoteName('#__minimarket_product_attribute_scalars'))
            ->where($db->quoteName('product_id') . ' = ' . $productId);
        $db->setQuery($q);
        foreach ((array) $db->loadAssocList() as $row) {
            $v = $row['value_text'] ?? $row['value_number'] ?? $row['value_boolean'] ?? null;
            if ($v !== null) {
                $result[(int) $row['attribute_id']] = (string) $v;
            }
        }
        $q = $db->getQuery(true)
            ->select(['attribute_id','value_id'])
            ->from($db->quoteName('#__minimarket_product_attribute_options'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->order($db->quoteName('attribute_id') . ' ASC, ' . $db->quoteName('value_id') . ' ASC');
        $db->setQuery($q);
        foreach ((array) $db->loadAssocList() as $row) {
            $result[(int) $row['attribute_id']][] = (int) $row['value_id'];
        }
        return $result;
    }

    /**
     * Validate every supplied nonempty field against the selected category,
     * published attribute and matching published dictionary values.
     * @return array{scalars:array<int,array{type:string,value:string}>,options:array<int,int[]>}
     */
    public static function normalize(
        mixed $posted, int $categoryId, array $definitions, array $categoryMap
    ): array {
        if (!is_array($posted) && $posted !== null) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
        }
        $allowed = array_fill_keys($categoryMap[$categoryId] ?? [], true);
        $out = ['scalars' => [], 'options' => []];
        foreach ((array) $posted as $id => $value) {
            if (!ctype_digit((string) $id) || (int) $id < 1) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
            }
            $id = (int) $id;
            if ($value === '' || $value === null || $value === []) {
                continue;
            }
            if (!isset($allowed[$id]) || !isset($definitions[$id])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_NOT_ALLOWED');
            }
            $def = $definitions[$id];
            $type = $def['type'];
            if ($type === 'list') {
                $items = is_array($value) ? $value : [$value];
                if (!$def['is_variant'] && count($items) > 1) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                }
                $selected = [];
                foreach ($items as $candidate) {
                    if (!is_scalar($candidate) || !ctype_digit((string) $candidate)
                        || (int) $candidate < 1 || isset($selected[(int) $candidate])
                        || !isset($def['values'][(int) $candidate])
                        ) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                    }
                    $selected[(int) $candidate] = (int) $candidate;
                }
                if ($selected) {
                    $out['options'][$id] = array_values($selected);
                }
                continue;
            }
            if (!is_scalar($value)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
            }
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            if ($type === 'text') {
                if (mb_strlen($value) > 1024 || preg_match('/[\x00-\x1f<>]/', $value)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                }
            } elseif ($type === 'number') {
                if (!preg_match('/^-?(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,4})?$/D', $value)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                }
            } elseif ($type === 'boolean') {
                if (!in_array($value, ['0','1'], true)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                }
            } else {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
            }
            $out['scalars'][$id] = ['type'=>$type,'value'=>$value];
        }
        return $out;
    }

    /** Called inside ProductModel's product+gallery transaction. */
    public static function replace(DatabaseInterface $db, int $productId, array $normalized): void
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
        }
        foreach (['#__minimarket_product_attribute_scalars', '#__minimarket_product_attribute_options'] as $table) {
            $q = $db->getQuery(true)->delete($db->quoteName($table))
                ->where($db->quoteName('product_id') . ' = ' . $productId);
            $db->setQuery($q);
            $db->execute();
        }
        if ($normalized['scalars']) {
            $q = $db->getQuery(true)
                ->insert($db->quoteName('#__minimarket_product_attribute_scalars'))
                ->columns($db->quoteName(['product_id','attribute_id','value_text','value_number','value_boolean']));
            foreach ($normalized['scalars'] as $id => $row) {
                $type = $row['type'];
                $value = $row['value'];
                $q->values(implode(',',[
                    (string) $productId, (string) (int) $id,
                    $type === 'text' ? $db->quote($value) : 'NULL',
                    $type === 'number' ? $db->quote($value) : 'NULL',
                    $type === 'boolean' ? (string) (int) $value : 'NULL',
                ]));
            }
            $db->setQuery($q);
            $db->execute();
        }
        if ($normalized['options']) {
            $q = $db->getQuery(true)
                ->insert($db->quoteName('#__minimarket_product_attribute_options'))
                ->columns($db->quoteName(['product_id','attribute_id','value_id']));
            foreach ($normalized['options'] as $id => $values) {
                foreach ($values as $valueId) {
                    $q->values(implode(',', [(string) $productId,(string) (int) $id,(string) (int) $valueId]));
                }
            }
            $db->setQuery($q);
            $db->execute();
        }
    }
}
