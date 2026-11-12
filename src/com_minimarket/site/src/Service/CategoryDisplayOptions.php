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
        $result['show_add_to_cart']=(int)$global->get('category_show_add_to_cart',1)===1
            && (string)$global->get('shop_type','physical')==='physical';
        $result['quick_view']=(int)$global->get('category_quick_view',0)===1;
        $result['show_badges']=(int)$global->get('category_show_badges',1)===1;
        $result['category_image']=(int)$global->get('category_show_image',1)===1;
        $result['category_intro']=(int)$global->get('category_show_introtext',1)===1;
        $result['category_description']=(int)$global->get('category_show_description',1)===1;
        $result['product_gallery_enabled']=(int)$global->get('category_product_gallery_enabled',1)===1;
        $result['show_sort']=(int)$global->get('category_show_sort',1)===1;
        $result['lazy_loading']=(int)$global->get('category_lazy_loading',1)===1;
        $result['allow_layout_switch']=(int)$global->get('category_allow_layout_switch',1)===1;
        $result['show_list_attributes']=(int)$global->get('category_list_show_attributes',1)===1;
        $result['show_list_options']=(int)$global->get('category_list_show_options',1)===1;
        $nav=(string)$global->get('category_navigation_mode','pages');
        $result['navigation_mode']=in_array($nav,['pages','load_more'],true)?$nav:'pages';
        $trigger=(string)$global->get('category_load_trigger','button');
        $result['load_trigger']=in_array($trigger,['button','scroll'],true)?$trigger:'button';
        // Always provide accessible page links and two-way product loading.
        $result['pagination_page_links']=true;
        $result['pagination_next']=true;
        $result['pagination_previous']=true;
        $result['load_previous']=true;
        $limit=(int)$global->get('category_product_gallery_limit',5);
        $result['product_gallery_limit']=in_array($limit,[3,5,8],true)?$limit:5;
        // Custom information properties default to the full product page.
        // Each system property has its own location, independent of custom attributes.
        // Information attributes and SKU options have their own placement.
        // Obsolete component-wide custom-property settings must not override
        // individual display_location or its product-page default.
        $result['attribute_display_location']='product';
        $result['option_display_location']='product';
        $legacy=(string)$global->get('system_display_location','both');
        if(!in_array($legacy,['product','category','both'],true))$legacy='both';
        foreach(['price','sku','stock'] as $name){
            $key='system_'.$name.'_display_location';
            $location=(string)$global->get($key,$legacy);
            $result[$key]=in_array($location,['product','category','both'],true)?$location:$legacy;
        }
        foreach ($flags as $field => [$key, $default]) {
            $local = (int) ($category->$field ?? -1);
            $result[$field] = $local === -1
                ? (int) $global->get($key, $default) === 1
                : $local === 1;
        }
        $layout = (string) ($category->product_layout ?? 'inherit');
        if (!in_array($layout, ['list', 'grid'], true)) {
            $layout = (string) $global->get('category_product_layout', 'grid');
        }
        $result['layout'] = in_array($layout, ['list', 'grid'], true) ? $layout : 'grid';
        // All six Bootstrap 5 viewport breakpoints have independent column
        // counts; -1 is always the global component setting.
        $categoryOwnsGrid = (string)($category->product_layout ?? 'inherit') === 'grid';
        foreach (['xs'=>1,'sm'=>1,'md'=>2,'lg'=>3,'xl'=>4,'xxl'=>4] as $size=>$default) {
            // Category overrides are active only when its layout is Grid.
            // "Inherit" means use both global layout AND global card counts.
            // Previously entered overrides remain saved for future Grid use.
            $local=$categoryOwnsGrid ? (int)($category->{'cards_'.$size} ?? -1) : -1;
            $globalValue=(int)$global->get('category_cards_'.$size,$default);
            $count=$local===-1?$globalValue:$local;
            $result['cards_'.$size]=max(1,min(6,$count));
        }
        // Subcategory tile count is independent of product list/grid layout.
        foreach (['xs'=>1,'sm'=>1,'md'=>2,'lg'=>3,'xl'=>4,'xxl'=>4] as $size=>$default) {
            $local=(int)($category->{'subcategory_cards_'.$size}??-1);
            $globalValue=(int)$global->get('subcategory_cards_'.$size,$default);
            $result['subcategory_cards_'.$size]=max(1,min(6,$local===-1?$globalValue:$local));
        }
        $globalLimit=(int)$global->get('category_products_per_page',10);
        if (!in_array($globalLimit,[10,20,30,50],true)) $globalLimit=10;
        $localLimit=(int)($category->products_per_page??0);
        $result['products_per_page']=in_array($localLimit,[10,20,30,50],true)
            ?$localLimit:$globalLimit;
        foreach (['filter_price_mode'=>['none','range','slider'],
                  'filter_sku_mode'=>['none','text']] as $field=>$allowed) {
            $fallback=$field==='filter_price_mode'?'slider':'none';
            $glob=(string)$global->get($field,$fallback);
            if (!in_array($glob,$allowed,true)) $glob=$fallback;
            $local=(string)($category->$field??'inherit');
            $result[$field]=in_array($local,$allowed,true)?$local:$glob;
        }
        $stock=(int)($category->filter_stock_mode??-1);
        $result['filter_stock_enabled']=$stock===-1
            ?(int)$global->get('filter_stock_enabled',0)===1:$stock===1;
        return $result;
    }

    public static function subcategoryGridClasses(array $config): string
    {
        return 'row row-cols-' . $config['subcategory_cards_xs']
            . ' row-cols-sm-' . $config['subcategory_cards_sm']
            . ' row-cols-md-' . $config['subcategory_cards_md']
            . ' row-cols-lg-' . $config['subcategory_cards_lg']
            . ' row-cols-xl-' . $config['subcategory_cards_xl']
            . ' row-cols-xxl-' . $config['subcategory_cards_xxl'] . ' g-3';
    }

    public static function bootstrapGridClasses(array $config): string
    {
        return 'row row-cols-' . $config['cards_xs']
            . ' row-cols-sm-' . $config['cards_sm']
            . ' row-cols-md-' . $config['cards_md']
            . ' row-cols-lg-' . $config['cards_lg']
            . ' row-cols-xl-' . $config['cards_xl']
            . ' row-cols-xxl-' . $config['cards_xxl'] . ' g-3';
    }
}
