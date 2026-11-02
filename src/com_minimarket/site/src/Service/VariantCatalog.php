<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Read valid, published SKU combinations without introducing variant URLs. */
final class VariantCatalog
{
    /** @param int[] $ancestors published category chain */
    public static function load(DatabaseInterface $db, int $productId, array $ancestors): array
    {
        $empty = ['axes' => [], 'items' => []];
        if ($productId < 1 || !$ancestors) return $empty;
        $q = $db->getQuery(true)->select(['category_id','attribute_id','enabled'])
            ->from($db->quoteName('#__minimarket_category_attributes'))
            ->where($db->quoteName('category_id') . ' IN (' . implode(',', $ancestors) . ')');
        $db->setQuery($q);
        $byCategory = [];
        foreach ((array) $db->loadAssocList() as $r) {
            $byCategory[(int)$r['category_id']][(int)$r['attribute_id']] =
                (int) $r['enabled'] === 1;
        }
        $allowed = [];
        foreach ($ancestors as $catId) {
            foreach ($byCategory[$catId] ?? [] as $id => $active) $allowed[$id] = $active;
        }
        $allowed = array_keys(array_filter($allowed));
        if (!$allowed) return $empty;

        $q = $db->getQuery(true)->select(['id','title','input_type','input_mode','text_options'])
            ->from($db->quoteName('#__minimarket_attributes'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $allowed) . ')')
            ->where($db->quoteName('is_variant') . ' = 1')
            ->where($db->quoteName('state') . ' = 1')
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $axes = [];
        foreach ((array) $db->loadAssocList() as $r) {
            $id = (int) $r['id']; $type = (string)$r['input_type'];
            if (!in_array($type, ['list','color','text'], true)) continue;
            if ($type === 'text' && $r['input_mode'] !== 'presets') continue;
            $axes[$id] = ['title'=>(string)$r['title'], 'options'=>[]];
            if ($type === 'text') {
                $presets = json_decode((string)($r['text_options'] ?? ''), true);
                foreach (is_array($presets) ? $presets : [] as $preset) {
                    $title = is_array($preset) ? ($preset['value'] ?? null) : $preset;
                    if (!is_string($title) || $title==='') continue;
                    $axes[$id]['options'][] = ['value'=>'t:'.$title,'title'=>$title];
                }
            }
        }
        if (!$axes || count($axes) > 4) return $empty;
        $dictIds = [];
        foreach ($axes as $id => $axis) {
            if (!$axis['options']) $dictIds[] = $id;
        }
        if ($dictIds) {
            $q = $db->getQuery(true)->select(['attribute_id','id','title'])
                ->from($db->quoteName('#__minimarket_attribute_values'))
                ->where($db->quoteName('attribute_id') . ' IN (' . implode(',', $dictIds) . ')')
                ->where($db->quoteName('state') . ' = 1')
                ->order('sort_order ASC, id ASC');
            $db->setQuery($q);
            foreach ((array) $db->loadAssocList() as $r) {
                $id = (int) $r['attribute_id'];
                $axes[$id]['options'][] = ['value'=>'d:' . (int) $r['id'], 'title'=>(string)$r['title']];
            }
        }
        foreach ($axes as $axis) if (!$axis['options']) return $empty;
        $valid = [];
        foreach ($axes as $id => $axis) foreach ($axis['options'] as $option) {
            $valid[$id][$option['value']] = true;
        }
        $q = $db->getQuery(true)->select(['id','selection_json','sku','price','stock','image','image_alt'])
            ->from($db->quoteName('#__minimarket_product_variants'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->where($db->quoteName('state') . ' = 1')
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $items = [];
        foreach ((array) $db->loadAssocList() as $r) {
            $selection = json_decode((string)$r['selection_json'], true);
            if (!is_array($selection) || count($selection)!==count($axes)) continue;
            $ok = true;
            foreach ($axes as $id => $axis) {
                $choice = $selection[$id] ?? null;
                if (!is_string($choice) || !isset($valid[$id][$choice])) { $ok=false; break; }
            }
            if (!$ok) continue;
            $image = (string)($r['image'] ?? '');
            $image = self::safeImage($image);
            $items[] = [
                'id' => (int)$r['id'], 'selection'=>$selection,
                'sku'=>(string)$r['sku'], 'price'=>(string)$r['price'],
                'stock'=>(int)$r['stock'], 'image'=>$image,
                'image_alt'=>(string)($r['image_alt'] ?? ''),
                'photos'=>[],
            ];
        }
        if (!$items) return $empty;
        $ids = array_column($items, 'id');
        $index = array_flip($ids);
        $q = $db->getQuery(true)->select(['variant_id','path','alt'])
            ->from($db->quoteName('#__minimarket_product_variant_images'))
            ->where($db->quoteName('variant_id') . ' IN (' . implode(',', $ids) . ')')
            ->order('variant_id ASC, sort_order ASC, id ASC');
        $db->setQuery($q);
        foreach ((array) $db->loadAssocList() as $r) {
            $id = (int)$r['variant_id'];
            $path = self::safeImage((string)$r['path']);
            if ($path !== '' && isset($index[$id])) {
                $items[$index[$id]]['photos'][] = [
                    'path'=>$path, 'alt'=>(string)$r['alt'],
                ];
            }
        }
        // First available variant by default; show out-of-stock choices too.
        usort($items, static fn(array $a,array $b): int =>
            (($b['stock'] > 0) <=> ($a['stock'] > 0)) ?: 0);
        return ['axes'=>$axes, 'items'=>$items];
    }

    private static function safeImage(string $path): string
    {
        return strlen($path) <= 1024 && str_starts_with($path,'images/')
            && !str_contains($path, '..') && !str_contains($path, '//')
            && !preg_match('~[\x00-\x1f<>?#\\\\]~', $path)
            && preg_match('~\.(?:jpe?g|png|webp|gif|avif)$~iD', $path)
            ? $path : '';
    }
}
