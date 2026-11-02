<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Real product variants (SKU, price, stock, cover and ordered gallery).
 * Variants never create product URLs; all work inside ProductModel transaction.
 */
final class ProductVariants
{
    public const MAX_VARIANTS = 200;
    public const MAX_AXES = 4;

    /** @return array<int,array{title:string,options:array<int,array{value:string,title:string,image:string,hex:string}>}> */
    public static function axes(int $categoryId, array $definitions, array $categoryMap): array
    {
        $allowed = array_fill_keys($categoryMap[$categoryId] ?? [], true);
        $axes = [];
        foreach ($definitions as $id => $definition) {
            if (!isset($allowed[$id]) || !$definition['is_variant'] || (int) $definition['state'] !== 1) continue;
            $type = $definition['type'];
            $options = [];
            if (in_array($type, ['list', 'color'], true)) {
                foreach ($definition['values'] as $valueId => $value) {
                    if ((int) $value['state'] !== 1) continue;
                    $options[] = [
                        'value' => 'd:' . (int) $valueId,
                        'title' => (string) $value['title'],
                        'image' => (string) ($value['sample_image'] ?? ''),
                        'hex' => (string) ($value['color_hex'] ?? ''),
                    ];
                }
            } elseif ($type === 'text' && ($definition['input_mode'] ?? '') === 'presets') {
                foreach (TextPresetRows::rows((string) ($definition['text_options'] ?? '')) as $preset) {
                    $options[] = [
                        'value' => 't:' . $preset['value'],
                        'title' => $preset['value'],
                        'image' => $preset['image'],
                        'hex' => '',
                    ];
                }
            }
            if ($options) {
                $axes[(int) $id] = ['title' => (string) $definition['title'], 'options' => $options];
            }
        }
        return $axes;
    }

    /** @return array<int,array<string,mixed>> */
    public static function normalize(mixed $input, array $axes): array
    {
        if ($input === null || $input === '' || $input === '{}' || $input === []) return [];
        if (is_string($input)) {
            try { $input = json_decode($input, true, 512, JSON_THROW_ON_ERROR); }
            catch (\JsonException $e) { throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID'); }
        }
        if (!is_array($input) || count($input) > self::MAX_VARIANTS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
        }
        if (!$axes) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_NO_AXES');
        }
        if (count($axes) > self::MAX_AXES) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_TOO_MANY_AXES');
        }
        $result = [];
        $seenCombos = [];
        $seenSkus = [];
        $seenIds = [];
        $allowed = [];
        foreach ($axes as $id => $axis) {
            foreach ($axis['options'] as $option) {
                $allowed[$id][$option['value']] = true;
            }
        }
        foreach ($input as $item) {
            if (is_object($item)) $item = (array) $item;
            if (!is_array($item)) throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            $idText = (string) ($item['id'] ?? '0');
            $sku = trim((string) ($item['sku'] ?? ''));
            $price = trim((string) ($item['price'] ?? ''));
            $stock = trim((string) ($item['stock'] ?? ''));
            $state = (string) ($item['state'] ?? '1');
            $alt = trim((string) ($item['image_alt'] ?? ''));
            $image = MediaPath::normalize((string) ($item['image'] ?? ''));
            $rawSelection = $item['selection'] ?? null;
            if (is_string($rawSelection)) {
                if (strlen($rawSelection) > 4096) throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
                try { $rawSelection = json_decode($rawSelection, true, 512, JSON_THROW_ON_ERROR); }
                catch (\JsonException $e) { throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID'); }
            }
            if (!ctype_digit($idText) || (int) $idText < 0 || !is_array($rawSelection)
                || count($rawSelection) !== count($axes)
                || $sku === '' || mb_strlen($sku) > 100
                || preg_match('/[\x00-\x1f<>]/u', $sku)
                || str_starts_with(mb_strtolower($sku), 'mm-temporary-variant-')
                || !preg_match('/^(?:0|[1-9]\d{0,12})(?:\.\d{1,2})?$/D', $price)
                || !preg_match('/^(?:0|[1-9]\d{0,9})$/D', $stock)
                || (float) $stock > 4294967295
                || !in_array($state, ['0', '1'], true)
                || $image === null
                || mb_strlen($alt) > 255 || preg_match('/[\x00-\x1f<>]/u', $alt)
            ) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            }
            $id = (int) $idText;
            if ($id > 0 && isset($seenIds[$id])) throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            $seenIds[$id] = true;
            $selection = [];
            foreach ($axes as $attrId => $axis) {
                $choice = $rawSelection[$attrId] ?? $rawSelection[(string) $attrId] ?? null;
                if (!is_string($choice) || !isset($allowed[$attrId][$choice])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_SELECTION_INVALID');
                }
                $selection[(string) $attrId] = $choice;
            }
            if (count($rawSelection) !== count($selection)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_SELECTION_INVALID');
            }
            $signature = hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $skuKey = mb_strtolower($sku);
            if (isset($seenCombos[$signature]) || isset($seenSkus[$skuKey])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_DUPLICATE');
            }
            $seenCombos[$signature] = true;
            $seenSkus[$skuKey] = true;
            $photos = ProductGallery::normalizeRows($item['photos'] ?? [], $image);
            if ($photos && $image === '') throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_COVER_REQUIRED');
            $result[] = [
                'id' => $id,
                'sku' => $sku,
                'selection_json' => json_encode($selection, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'signature' => $signature,
                'price' => (static function (string $money): string {
                    $parts = explode('.', $money, 2);
                    return $parts[0] . '.' . str_pad($parts[1] ?? '', 2, '0');
                })($price),
                'stock' => (int) $stock,
                'image' => $image, 'image_alt' => $alt,
                'state' => (int) $state,
                'photos' => $photos,
                'sort_order' => count($result),
            ];
        }
        return $result;
    }

    /** @return array<string,array<string,mixed>> */
    public static function formRows(DatabaseInterface $db, int $productId): array
    {
        if ($productId < 1) return [];
        $q = $db->getQuery(true)->select(['id','sku','selection_json','price','stock','image','image_alt','state'])
            ->from($db->quoteName('#__minimarket_product_variants'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $variants = (array) $db->loadAssocList();
        if (!$variants) return [];
        $q = $db->getQuery(true)->select(['variant_id','path','alt'])
            ->from($db->quoteName('#__minimarket_product_variant_images'))
            ->where($db->quoteName('variant_id') . ' IN (' .
                implode(',', array_map(static fn (array $r): int => (int) $r['id'], $variants)) . ')')
            ->order('variant_id ASC, sort_order ASC, id ASC');
        $db->setQuery($q);
        $photos = [];
        foreach ((array) $db->loadAssocList() as $r) {
            $photos[(int) $r['variant_id']][] = [
                'path' => (string) $r['path'], 'alt' => (string) $r['alt'],
            ];
        }
        $rows = [];
        foreach ($variants as $i => $r) {
            $id = (int) $r['id'];
            $rows['row' . $i] = [
                'id' => $id, 'sku' => (string) $r['sku'],
                'selection' => (string) $r['selection_json'],
                'price' => (string) $r['price'], 'stock' => (string) $r['stock'],
                'image' => (string) $r['image'], 'image_alt' => (string) $r['image_alt'],
                'state' => (int) $r['state'],
                'photos' => [],
            ];
            foreach ($photos[$id] ?? [] as $j => $photo) {
                $rows['row' . $i]['photos']['row' . $j] = $photo;
            }
        }
        return $rows;
    }

    /**
     * Atomically preserve existing variant IDs when edited/reordered.
     * Does not delete/copy Joomla Media files.
     */
    public static function replace(DatabaseInterface $db, int $productId, array $rows): void
    {
        if ($productId < 1) throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
        $q = $db->getQuery(true)->select('id')
            ->from($db->quoteName('#__minimarket_product_variants'))
            ->where($db->quoteName('product_id') . ' = ' . $productId);
        $db->setQuery($q);
        $oldIds = array_map('intval', (array) $db->loadColumn());
        $old = array_fill_keys($oldIds, true);
        $kept = [];
        foreach ($rows as $r) {
            if ($r['id'] > 0 && !isset($old[$r['id']])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANTS_INVALID');
            }
            if ($r['id'] > 0) $kept[$r['id']] = true;
        }
        $removed = array_values(array_diff($oldIds, array_keys($kept)));
        if ($removed) {
            $q = $db->getQuery(true)->delete($db->quoteName('#__minimarket_product_variants'))
                ->where($db->quoteName('product_id') . ' = ' . $productId)
                ->where($db->quoteName('id') . ' IN (' . implode(',', $removed) . ')');
            $db->setQuery($q);
            $db->execute();
        }
        // Temporarily move unique signatures and SKUs to allow swaps between
        // existing rows, without replacing their stable IDs or media relations.
        foreach (array_keys($kept) as $id) {
            $q = $db->getQuery(true)->update($db->quoteName('#__minimarket_product_variants'))
                ->set($db->quoteName('signature') . ' = ' . $db->quote(hash('sha256', 'variant-temporary-' . $id)))
                ->set($db->quoteName('sku') . ' = ' . $db->quote('mm-temporary-variant-' . $id))
                ->where($db->quoteName('id') . ' = ' . $id)
                ->where($db->quoteName('product_id') . ' = ' . $productId);
            $db->setQuery($q);
            $db->execute();
        }
        foreach ($rows as $r) {
            $data = [
                'sku' => $db->quote($r['sku']),
                'selection_json' => $db->quote($r['selection_json']),
                'signature' => $db->quote($r['signature']),
                'price' => $db->quote($r['price']),
                'stock' => (string) $r['stock'],
                'image' => $db->quote($r['image']),
                'image_alt' => $db->quote($r['image_alt']),
                'state' => (string) $r['state'],
                'sort_order' => (string) $r['sort_order'],
            ];
            if ($r['id'] > 0) {
                $q = $db->getQuery(true)->update($db->quoteName('#__minimarket_product_variants'));
                foreach ($data as $name => $value) $q->set($db->quoteName($name) . ' = ' . $value);
                $q->where($db->quoteName('product_id') . ' = ' . $productId)
                    ->where($db->quoteName('id') . ' = ' . $r['id']);
                $db->setQuery($q);
                $db->execute();
                $variantId = $r['id'];
            } else {
                $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_product_variants'))
                    ->columns($db->quoteName(array_merge(['product_id'], array_keys($data))))
                    ->values(implode(',', array_merge([(string) $productId], array_values($data))));
                $db->setQuery($q);
                $db->execute();
                $variantId = (int) $db->insertid();
                if ($variantId < 1) throw new \RuntimeException('COM_MINIMARKET_VARIANTS_SAVE_FAILED');
            }
            $q = $db->getQuery(true)->delete($db->quoteName('#__minimarket_product_variant_images'))
                ->where($db->quoteName('variant_id') . ' = ' . $variantId);
            $db->setQuery($q);
            $db->execute();
            if ($r['photos']) {
                $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_product_variant_images'))
                    ->columns($db->quoteName(['variant_id','path','alt','sort_order']));
                foreach ($r['photos'] as $i => $photo) {
                    $q->values(implode(',', [
                        (string) $variantId, $db->quote($photo['path']),
                        $db->quote($photo['alt']), (string) $i,
                    ]));
                }
                $db->setQuery($q);
                $db->execute();
            }
        }
    }
}
