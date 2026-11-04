<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Global option IDs are the source of truth; local dimension codes are merely
 * the stable SKU selection keys used by existing storefront/orders.
 *
 * Reads all category/option/value data in bounded queries; never writes.
 */
final class ProductCatalogAxes
{
    /**
     * @return array<int,array{axes:array,too_many:bool}>
     */
    public static function forAllCategories(DatabaseInterface $db): array
    {
        $hierarchy = CategoryOrderOptions::hierarchy($db);
        $q = $db->getQuery(true)->select(['id','code','title','state'])
            ->from($db->quoteName('#__minimarket_order_options'))
            ->order('sort_order ASC,id ASC');
        $db->setQuery($q);
        $options = (array) $db->loadAssocList();

        $q = $db->getQuery(true)->select(['id','option_id','code','title','sample_image','state'])
            ->from($db->quoteName('#__minimarket_order_option_values'))
            ->order('sort_order ASC,id ASC');
        $db->setQuery($q);
        $byOption = [];
        foreach ((array) $db->loadAssocList() as $value) {
            if ((int) $value['state'] !== 1) continue;
            $byOption[(int) $value['option_id']][] = [
                'source_value_id' => (int) $value['id'],
                'code' => (string) $value['code'],
                'title' => (string) $value['title'],
                'sample_image' => (string)($value['sample_image'] ?? ''),
            ];
        }

        $result = [];
        foreach ($hierarchy['parents'] as $categoryId => $unused) {
            $modes = CategoryOrderOptions::resolveModes(
                (int) $categoryId, $hierarchy['parents'], $hierarchy['rules']
            );
            $axes = [];
            $count = 1;
            $tooMany = false;
            foreach ($options as $option) {
                $id = (int) $option['id'];
                if (!isset($modes[$id]) || (int) $option['state'] !== 1) continue;
                $values = $byOption[$id] ?? [];
                if (!$values) continue;
                $count *= count($values);
                if (count($axes) >= 4 || $count > 200) {
                    $tooMany = true;
                    break;
                }
                $axes[] = [
                    'source_option_id' => $id,
                    'code' => (string) $option['code'],
                    'title' => (string) $option['title'],
                    'required' => (int) $modes[$id] === 2,
                    'values' => $values,
                ];
            }
            $result[(int) $categoryId] = ['axes' => $axes, 'too_many' => $tooMany];
        }
        return $result;
    }

    /**
     * Validate source IDs against the real dictionary and category inheritance.
     * Historical mapped axes/values remain legal when an admin disables a
     * catalog option later: ordinary editing must never destroy old SKU data.
     */
    public static function validate(
        DatabaseInterface $db, int $categoryId, int $productId, array $axes
    ): void {
        $catalog = self::forAllCategories($db);
        $allowed = [];
        foreach ($catalog[$categoryId]['axes'] ?? [] as $axis) {
            foreach ($axis['values'] as $value) {
                $allowed[$axis['source_option_id']][$value['source_value_id']] = true;
            }
        }
        // Include historical assignments of this exact product, but no other
        // product and no arbitrarily supplied IDs from request parameters.
        $historicalAxes = [];
        $historicalValues = [];
        if ($productId > 0) {
            $q = $db->getQuery(true)->select(['id','source_option_id'])
                ->from($db->quoteName('#__minimarket_variant_dimensions'))
                ->where('product_id = ' . $productId);
            $db->setQuery($q);
            $ids = [];
            foreach ((array) $db->loadAssocList() as $row) {
                $id = (int) $row['id'];
                $ids[] = $id;
                if ((int) $row['source_option_id'] > 0) {
                    $historicalAxes[(int) $row['source_option_id']] = true;
                }
            }
            if ($ids) {
                $q = $db->getQuery(true)->select(['source_value_id','dimension_id'])
                    ->from($db->quoteName('#__minimarket_variant_dimension_values'))
                    ->where('dimension_id IN (' . implode(',', $ids) . ')');
                $db->setQuery($q);
                foreach ((array) $db->loadAssocList() as $row) {
                    if ((int) $row['source_value_id'] > 0) {
                        $historicalValues[(int) $row['dimension_id']][(int) $row['source_value_id']] = true;
                    }
                }
            }
        }
        $usedOptions = [];
        foreach ($axes as $axis) {
            $optionId = (int) ($axis['source_option_id'] ?? 0);
            if ($optionId === 0) {
                foreach ($axis['values'] as $value) {
                    if ((int) ($value['source_value_id'] ?? 0) !== 0) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
                    }
                }
                continue; // Historical or manually defined local axes.
            }
            if (isset($usedOptions[$optionId])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
            }
            $usedOptions[$optionId] = true;
            // Check published catalog or historical mapping; never silently
            // replace a product's saved SKU axes on a category rule change.
            if (!isset($allowed[$optionId]) && !isset($historicalAxes[$optionId])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
            }
            foreach ($axis['values'] as $value) {
                $valueId = (int) ($value['source_value_id'] ?? 0);
                if ($valueId === 0) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
                }
                if (!isset($allowed[$optionId][$valueId]) &&
                    !isset($historicalValues[
                        self::existingDimensionId($db, $productId, (string) $axis['code'])
                    ][$valueId])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
                }
            }
        }
        // Verify the actual parent-child relationships, even if a user tampers
        // with hidden inputs or the option was unpublished since the last save.
        $sourceOptionIds = array_keys($usedOptions);
        if (!$sourceOptionIds) return;
        $q = $db->getQuery(true)->select(['id','option_id'])
            ->from($db->quoteName('#__minimarket_order_option_values'))
            ->where('option_id IN (' . implode(',', array_map('intval', $sourceOptionIds)) . ')');
        $db->setQuery($q);
        $owners = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $owners[(int) $row['id']] = (int) $row['option_id'];
        }
        foreach ($axes as $axis) {
            $id = (int) ($axis['source_option_id'] ?? 0);
            if (!$id) continue;
            foreach ($axis['values'] as $value) {
                if (($owners[(int) $value['source_value_id']] ?? 0) !== $id) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID');
                }
            }
        }
    }

    private static function existingDimensionId(
        DatabaseInterface $db, int $productId, string $code
    ): int {
        if ($productId < 1) return 0;
        $q = $db->getQuery(true)->select('id')
            ->from($db->quoteName('#__minimarket_variant_dimensions'))
            ->where('product_id = ' . $productId)
            ->where('code = ' . $db->quote($code));
        $db->setQuery($q);
        return (int) ($db->loadResult() ?? 0);
    }
}
