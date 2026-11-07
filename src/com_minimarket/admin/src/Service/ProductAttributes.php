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
    /** @return array<int,array<string,mixed>> */
    public static function definitions(DatabaseInterface $db): array
    {
        $q = $db->getQuery(true)
            ->select(['id','title','input_type','number_unit','number_min','number_max','number_step','is_multiple','value_source','color_display','state'])
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
                'is_multiple' => (int) $row['is_multiple'] === 1,
                'value_source' => (string) ($row['value_source'] ?? 'manual'),
                'color_display' => (string) ($row['color_display'] ?? 'both'),
                'number_unit' => (string) ($row['number_unit'] ?? ''),
                'number_min' => isset($row['number_min']) ? (string) $row['number_min'] : null,
                'number_max' => isset($row['number_max']) ? (string) $row['number_max'] : null,
                'number_step' => isset($row['number_step']) ? (string) $row['number_step'] : null,
                'state' => (int) $row['state'],
                'values' => [],
            ];
        }
        $q = $db->getQuery(true)
            ->select(['id','attribute_id','title','state','color_hex','sample_image','value_payload'])
            ->from($db->quoteName('#__minimarket_attribute_values'))
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        foreach ((array) $db->loadAssocList() as $row) {
            $attributeId = (int) $row['attribute_id'];
            if (isset($attributes[$attributeId])) {
                $attributes[$attributeId]['values'][(int) $row['id']] = [
                    'title' => (string) $row['title'],
                    'color_hex' => (string) ($row['color_hex'] ?? ''),
                    'sample_image' => (string) ($row['sample_image'] ?? ''),
                    'value_payload' => (string) ($row['value_payload'] ?? ''),
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
        // Ordered free values are the only manual multiple-value representation.
        $q = $db->getQuery(true)
            ->select(['attribute_id','value_text'])
            ->from($db->quoteName('#__minimarket_product_attribute_free_values'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->order('attribute_id ASC, sort_order ASC, id ASC');
        $db->setQuery($q);
        foreach ((array) $db->loadAssocList() as $row) {
            $id = (int) $row['attribute_id'];
            if (!is_array($result[$id] ?? null)) {
                $result[$id] = isset($result[$id]) ? [(string) $result[$id]] : [];
            }
            $result[$id][] = (string) $row['value_text'];
        }
        return $result;
    }

    /**
     * Validate every supplied nonempty field against the selected category,
     * published attribute and matching published dictionary values.
     * @return array{scalars:array<int,array{type:string,value:string}>,options:array<int,int[]>}
     */
    public static function normalize(
        mixed $posted, int $categoryId, array $definitions, array $categoryMap,
        array $requiredIds = []
    ): array {
        if (!is_array($posted) && $posted !== null) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
        }
        $allowed = array_fill_keys($categoryMap[$categoryId] ?? [], true);
        $out = ['scalars' => [], 'options' => [], 'free_values' => []];
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
            $source = (string) ($def['value_source'] ?? 'manual');
            if ($source === 'preset') {
                $items = is_array($value) ? $value : [$value];
                if (!$def['is_multiple'] && count($items) > 1) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                }
                $chosen = [];
                foreach ($items as $candidate) {
                    if (!is_scalar($candidate) || !ctype_digit((string) $candidate)
                        || (int) $candidate < 1 || isset($chosen[(int) $candidate])
                        || !isset($def['values'][(int) $candidate])) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                    }
                    $chosen[(int) $candidate] = (int) $candidate;
                }
                if ($chosen) $out['options'][$id] = array_values($chosen);
                continue;
            }
            if ($source === 'manual' && !empty($def['is_multiple'])) {
                $items = is_array($value) ? $value : [$value];
                if (count($items) > 100) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                }
                $unique = [];
                $singleDefinition = $def;
                $singleDefinition['is_multiple'] = false;
                foreach ($items as $item) {
                    if ($item === '' || $item === null) continue;
                    if (!is_scalar($item)) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                    }
                    $validated = self::normalize(
                        [$id => (string) $item], $categoryId,
                        [$id => $singleDefinition], [$categoryId => [$id]]
                    );
                    $validatedValue = $validated['scalars'][$id]['value'] ?? null;
                    if ($validatedValue !== null) {
                        if (isset($unique[$validatedValue])) {
                            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                        }
                        $unique[$validatedValue] = $validatedValue;
                    }
                }
                if ($unique) $out['free_values'][$id] = array_values($unique);
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
                if (mb_strlen($value)>1024 || preg_match('/[\x00-\x1f<>]/u',$value)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                }
            } elseif ($type === 'editor') {
                if (mb_strlen($value)>60000 ||
                    preg_match('/<\s*(script|iframe|object|embed|form|svg|math)\b|\bon\w+\s*=|javascript\s*:/iu',$value)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_EDITOR_HTML_INVALID');
                }
                $value=\Joomla\CMS\Filter\InputFilter::getInstance()->clean($value,'HTML');
            } elseif ($type === 'document') {
                if (strlen($value)>1024 || str_contains($value,'..')
                    || str_contains($value,'//')
                    || !preg_match('~^(?:images|files|media)/[a-zA-Z0-9_./ -]+\.(?:pdf|doc|docx|xls|xlsx|txt|md|csv|zip|jpg|jpeg|png|webp)$~iD',$value)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_DOCUMENT_INVALID');
                }
            } elseif ($type === 'color') {
                if (!preg_match('/^#[a-fA-F0-9]{6}$/D',$value)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_COLOR_INVALID');
                }
                $value=strtoupper($value);
            } elseif ($type === 'number') {
                if (!AttributeSettings::decimal($value)
                    || !AttributeSettings::inRange(
                        $value,$def['number_min']??null,$def['number_max']??null)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_NUMERIC_INVALID');
                }
                $step=$def['number_step']??null;
                if ($step!==null && (float)$step>0) {
                    $origin=(float)($def['number_min']??0);
                    $remainder=fmod(abs((float)$value-$origin),(float)$step);
                    if ($remainder>0.00001 &&
                        abs($remainder-(float)$step)>0.00001) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_NUMERIC_INVALID');
                    }
                }
            } elseif ($type === 'boolean') {
                if (!in_array($value,['0','1'],true)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
                }
            } else {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
            }
            $out['scalars'][$id] = ['type'=>$type,'value'=>$value];
        }
        foreach ($requiredIds as $id) {
            $id = (int) $id;
            if (!isset($allowed[$id]) || !isset($definitions[$id])) continue;
            // A boolean value of 0 is valid and already present as a scalar.
            if (!isset($out['scalars'][$id]) && empty($out['options'][$id])
                && empty($out['free_values'][$id])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_REQUIRED:' . $id);
            }
        }
        return $out;
    }

    /** Called inside ProductModel's product+gallery transaction. */
    public static function replace(DatabaseInterface $db, int $productId, array $normalized): void
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INVALID');
        }
        foreach (['#__minimarket_product_attribute_scalars', '#__minimarket_product_attribute_options', '#__minimarket_product_attribute_free_values'] as $table) {
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
                    in_array($type, ['text','editor','document','color'], true) ? $db->quote($value) : 'NULL',
                    $type === 'number' ? $db->quote($value) : 'NULL',
                    $type === 'boolean' ? (string) (int) $value : 'NULL',
                ]));
            }
            $db->setQuery($q);
            $db->execute();
        }
        if (!empty($normalized['free_values'])) {
            $q = $db->getQuery(true)
                ->insert($db->quoteName('#__minimarket_product_attribute_free_values'))
                ->columns($db->quoteName(['product_id','attribute_id','value_text','sort_order']));
            foreach ($normalized['free_values'] as $id => $values) {
                foreach ($values as $position => $value) {
                    $q->values(implode(',', [
                        (string) $productId, (string) (int) $id,
                        $db->quote((string) $value), (string) (int) $position,
                    ]));
                }
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
