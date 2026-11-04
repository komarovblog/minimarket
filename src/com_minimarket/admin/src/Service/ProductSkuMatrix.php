<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * One SKU matrix input and one set of physical SKU rows. The browser never
 * dictates product option definitions: they come from current category rules
 * or an existing product's stable dimensions.
 */
final class ProductSkuMatrix
{
    public static function dimensions(DatabaseInterface $db, int $categoryId, int $productId): array
    {
        if ($productId > 0) {
            $saved = ProductLocalDimensions::load($db, $productId);
            if ($saved) return VariantMatrix::normalize($saved);
        }
        $catalog = ProductCatalogAxes::forAllCategories($db);
        $rule = $catalog[$categoryId] ?? ['axes' => [], 'too_many' => false];
        if (!empty($rule['too_many'])) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_OPTIONS_TOO_MANY');
        }
        return VariantMatrix::normalize((array) ($rule['axes'] ?? []));
    }

    /**
     * Payload rows contain only id/selection/sku/stock/state. Media/text/video
     * content is reloaded from the existing variant, never from editable
     * client JSON, so a matrix save cannot inadvertently erase SKU media.
     *
     * @return array{dimensions:array,variants:array}
     */
    public static function normalize(
        DatabaseInterface $db, int $productId, int $categoryId,
        mixed $raw, string $productPrice
    ): array {
        if (!is_string($raw) || strlen($raw) > 250000) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
        }
        try { $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
        }
        if (!is_array($payload) || !array_is_list($payload)
            || count($payload) > ProductVariants::MAX_VARIANTS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
        }
        $dimensions = self::dimensions($db, $categoryId, $productId);
        $existing = ProductVariants::formRows($db, $productId);
        $byId = [];
        foreach ($existing as $saved) $byId[(int) $saved['id']] = $saved;
        $used = [];
        $merged = [];
        foreach ($payload as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            }
            $id = $row['id'] ?? 0;
            if ((!is_int($id) && (!is_string($id) || !ctype_digit($id)))
                || (int) $id < 0) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            }
            $id = (int) $id;
            if (($id && !isset($byId[$id])) || isset($used[$id]) && $id > 0) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            }
            if ($id > 0) $used[$id] = true;
            $state = $row['state'] ?? 0;
            if ($state !== 0 && $state !== 1 && $state !== '0' && $state !== '1') {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            }
            $sku = trim((string) ($row['sku'] ?? ''));
            $stock = (string) ($row['stock'] ?? '0');
            $selection = $row['selection'] ?? null;
            if (!is_array($selection) || !is_array($dimensions)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            }
            if ($id === 0 && (int) $state === 0 && $sku === ''
                && ($stock === '' || $stock === '0')) {
                continue; // Unavailable, never-created combination
            }
            $old = $id ? $byId[$id] : [];
            $merged[] = array_merge($old, [
                'id' => $id, 'sku' => $sku, 'stock' => $stock,
                'state' => (int) $state,
                'selection' => json_encode($selection,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'price' => $productPrice, // The product owns all prices.
            ]);
        }
        // Do not silently delete a saved SKU simply because an admin changes
        // category or a JS form drops a row. Deletions require a future explicit
        // action with confirmation.
        foreach ($byId as $id => $_) {
            if (!isset($used[$id])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            }
        }
        ProductCatalogAxes::validate($db, $categoryId, $productId, $dimensions);
        $axes = ProductLocalDimensions::axesForVariants($dimensions);
        return [
            'dimensions' => $dimensions,
            'variants' => ProductVariants::normalize($merged, $axes),
        ];
    }

    /** Deterministic JSON key for photo assignments, independent of SKU text. */
    public static function key(array $selection): string
    {
        ksort($selection, SORT_STRING);
        return json_encode($selection, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
