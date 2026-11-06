<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Shared, pure selection rules for the feed editor and the public publisher.
 * "Automatic" means all currently published products in chosen categories,
 * plus explicit inclusions minus exclusions. "Manual" means explicit IDs only.
 */
final class FeedSelection
{
    public static function ids(mixed $values): array
    {
        if (!is_array($values)) return [];
        $ids = [];
        foreach ($values as $value) {
            if (!is_scalar($value) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string)$value)) continue;
            $id = (int)$value;
            if ($id < 1 || $id > 2147483647) continue;
            $ids[$id] = $id;
        }
        ksort($ids, SORT_NUMERIC);
        return array_values($ids);
    }

    /** Return only categories whose entire ancestor chain is published. */
    public static function visibleCategories(array $categories): array
    {
        $map = [];
        foreach ($categories as $category) {
            $item = (array)$category;
            if ((int)($item['id'] ?? 0) > 0) $map[(int)$item['id']] = $item;
        }
        $visible = [];
        $walk = static function(int $id, array $seen=[]) use (&$walk, &$visible, $map): bool {
            if (isset($visible[$id])) return $visible[$id];
            if (!isset($map[$id]) || (int)($map[$id]['state']??0) !== 1 || isset($seen[$id])) {
                return false;
            }
            $parent = (int)($map[$id]['parent_id'] ?? 0);
            $seen[$id] = true;
            return $visible[$id] = ($parent === 0 || $walk($parent, $seen));
        };
        foreach(array_keys($map) as $id) $walk($id);
        return array_keys(array_filter($visible));
    }

    public static function selectedCategoryIds(array $categories, array $selectedIds, bool $descendants): array
    {
        $published = array_fill_keys(self::visibleCategories($categories), true);
        $selected = array_intersect_key(array_fill_keys(self::ids($selectedIds), true), $published);
        if ($descendants && $selected) {
            $changed = true;
            while ($changed) {
                $changed = false;
                foreach ($categories as $category) {
                    $item = (array)$category;
                    $id=(int)($item['id']??0);
                    $parent=(int)($item['parent_id']??0);
                    if (isset($published[$id],$selected[$parent]) && !isset($selected[$id])) {
                        $selected[$id]=true;
                        $changed=true;
                    }
                }
            }
        }
        $result=array_keys($selected);
        sort($result,SORT_NUMERIC);
        return $result;
    }

    /** @return int[] */
    public static function productIds(
        array $categories, array $products, array $settings
    ): array {
        $effective = array_fill_keys(self::selectedCategoryIds(
            $categories, self::ids($settings['category_ids']??[]),
            (int)($settings['include_descendants']??1)===1
        ),true);
        $visible = array_fill_keys(self::visibleCategories($categories),true);
        $included = array_fill_keys(self::ids($settings['include_product_ids']??[]),true);
        $excluded = array_fill_keys(self::ids($settings['exclude_product_ids']??[]),true);
        $auto = ($settings['selection_mode']??'auto')==='auto';
        $bundles = (int)($settings['include_bundles']??1)===1;
        $result = [];
        foreach ($products as $product) {
            $item=(array)$product;
            $id=(int)($item['id']??0);
            $categoryId=(int)($item['category_id']??0);
            if ($id<1 || !isset($visible[$categoryId]) ||
                (int)($item['state']??0)!==1 ||
                (!$bundles && ($item['product_type']??'simple')==='bundle')) {
                continue;
            }
            if (isset($included[$id]) || ($auto && isset($effective[$categoryId]) && !isset($excluded[$id]))) {
                $result[$id]=$id;
            }
        }
        ksort($result,SORT_NUMERIC);
        return array_values($result);
    }
}
