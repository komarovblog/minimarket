<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

/** Pure global/category display option resolver. -1 means component default. */
final class CategoryDisplayOptions
{
    public static function resolve(object $category, object $global): array
    {
        $flags = [
            'show_children' => ['category_show_children', 1],
            'show_products' => ['category_show_products', 1],
            'show_bundles' => ['category_show_bundles', 1],
            'show_descendant_products' => ['category_show_descendant_products', 0],
            'show_descendant_bundles' => ['category_show_descendant_bundles', 0],
        ];
        $result = [];
        foreach ($flags as $field => [$key, $default]) {
            $local = (int) ($category->$field ?? -1);
            $result[$field] = $local === -1
                ? (int) $global->get($key, $default) === 1
                : $local === 1;
        }
        $layout = (string) ($category->product_layout ?? 'inherit');
        if (!in_array($layout, ['list', 'grid'], true)) {
            $layout = (string) $global->get('category_product_layout', 'list');
        }
        $result['layout'] = in_array($layout, ['list', 'grid'], true) ? $layout : 'list';
        foreach (['mobile' => 1, 'tablet' => 2, 'desktop' => 3, 'wide' => 4] as $size => $default) {
            $local = (int) ($category->{'cards_' . $size} ?? -1);
            $count = $local === -1
                ? (int) $global->get('category_cards_' . $size, $default)
                : $local;
            $result['cards_' . $size] = max(1, min(6, $count));
        }
        return $result;
    }

    public static function bootstrapGridClasses(array $config): string
    {
        return 'row row-cols-' . $config['cards_mobile']
            . ' row-cols-md-' . $config['cards_tablet']
            . ' row-cols-lg-' . $config['cards_desktop']
            . ' row-cols-xl-' . $config['cards_wide'] . ' g-3';
    }
}
