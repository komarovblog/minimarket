<?php
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $key):string {return $key;}
        public static function sprintf(string $key,mixed ...$args):string {return $key.':'.implode(',',$args);}
    }
}
namespace MiniMarket\Smoke54 {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/ProductBadges.php';
    require __DIR__.'/../src/com_minimarket/site/src/Helper/BadgeHelper.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductBadges;
    use MiniMarket\Component\Minimarket\Site\Helper\BadgeHelper;
    $n=0;
    function verify(bool $yes,string $label):void {
        global $n; ++$n; if(!$yes) throw new \RuntimeException('MM_054_FAILED: '.$label);
    }
    $root=__DIR__.'/../src/com_minimarket/';
    $product=(object)[
        'price'=>'1490.00','old_price'=>'1990.00','badge_sale'=>1,
        'badge_benefit'=>0,'product_type'=>'simple','bundle_price_mode'=>'fixed',
        'badge_new'=>0,'badge_popular'=>0
    ];
    $result=ProductBadges::forProduct($product);
    verify($result['sale']===26,'percentage rounds up from old price');
    verify($result['sale_amount']==='500.00','money sale is old minus current');
    verify($result['benefit_amount']===null,'regular product cannot have benefit');
    verify(str_contains(BadgeHelper::render($product),'BADGE_SALE_LABEL') &&
        !str_contains(BadgeHelper::render($product),'500.00'),
        'default discount display shows percentage only');
    $product->product_type='bundle';
    $product->bundle_price_mode='discount';
    $product->badge_benefit=1;
    $product->bundle_discount_amount='350.10';
    $benefit=ProductBadges::forProduct($product);
    verify($benefit['benefit_amount']==='350.10','discount bundle saving is positive money');
    verify(str_contains(BadgeHelper::render($product),'350.10'),'site bundle badge shows benefit');
    $product->bundle_price_mode='fixed';
    verify(ProductBadges::forProduct($product)['benefit_amount']===null,
        'savings badge is not valid in fixed price mode');
    $product->bundle_price_mode='discount';
    $product->bundle_discount_amount='0.00';
    verify(ProductBadges::forProduct($product)['benefit_amount']===null,
        'no savings badge for zero benefit');
    $xml=simplexml_load_file($root.'admin/forms/category.xml');
    verify(count($xml->xpath("//fieldset[@name='display']/field[@name='show_bundles']"))===1,
        'category has independent show bundles option');
    $xml=simplexml_load_file($root.'admin/forms/product.xml');
    verify(count($xml->xpath("//fieldset[@name='merchandising']/field[@name='brand_id' and @type='Brandpicker']"))===1,
        'single brand is searchable, not a huge dropdown');
    verify(count($xml->xpath("//fieldset[@name='bundle_pricing']/field[@name='bundle_price_mode']"))===1,
        'bundle price mode on Details');
    verify(count($xml->xpath("//fieldset[@name='bundle_pricing']/field[@name='bundle_discount_pct']"))===1,
        'bundle discount percentage on Details');
    verify(count($xml->xpath("//fieldset[@name='bundle']/field[@name='bundle_price_mode']"))===0,
        'composition tab does not contain pricing controls');
    verify(count($xml->xpath("//fieldset[@name='bundle_benefit']/field[@name='badge_benefit']"))===1,
        'Savings flag is bundle-specific');
    $edit=(string)file_get_contents($root.'admin/tmpl/product/edit.php');
    verify(str_contains($edit,"unset(\$sections[\$isBundle?'variants':'bundle'])"),
        'product hides composition, bundle hides SKU matrix');
    verify(str_contains($edit,"'COM_MINIMARKET_BUNDLE_GROUPS'"),
        'bundle relationship tab is correctly named');
    verify(str_contains($edit,"renderField('bundle_price_mode')") &&
        str_contains($edit,'data-mm-bundle-totals'),
        'bundle price on Details, component total in composition');
    $attributes=(string)file_get_contents($root.'admin/src/Field/ProductattributesField.php');
    verify(substr_count($attributes,"COM_MINIMARKET_EDIT_ATTRIBUTE")===1,
        'same edit-characteristic link for every attribute type');
    verify(!str_contains($attributes,"Text::_('COM_MINIMARKET_EDIT_VALUES')"),
        'color-only edit-values link removed');
    $import=(string)file_get_contents($root.'admin/tmpl/import/default.php');
    verify(strpos($import,'task=import.preview') < strpos($import,'COM_MINIMARKET_EXPORT_TITLE'),
        'import form appears inside first visual block before export');
    verify(str_contains($import,"form.token"),'import retains CSRF token');
    $picker=(string)file_get_contents($root.'media/js/entity-picker.js');
    verify(str_contains($picker,"mode==='brand'") &&
        str_contains($picker,"row.dataset.mmUnitPrice") &&
        str_contains($picker,"[data-mm-bundle-totals]"),
        'brand single selection and dynamic bundle line/totals implemented');
    $field=(string)file_get_contents($root.'admin/src/Field/BrandpickerField.php');
    verify(str_contains($field,'data-mm-picker="brand"') &&
        str_contains($field,'value="0"'),
        'brand removal explicitly clears assignment to zero');
    $api=(string)file_get_contents($root.'admin/src/Controller/ProductpickerController.php');
    verify(str_contains($api,'function brands()') &&
        str_contains($api,'$this->ensureAllowed();') &&
        str_contains($api,'$db->setQuery($q,0,25)'),
        'protected paginated brand search endpoint');
    $productJs=(string)file_get_contents($root.'media/js/product-badges.js');
    verify(str_contains($productJs,'sale.disabled=!eligible') &&
        !str_contains($productJs,'saleContainer.hidden'),
        'discount flag stays visible but disabled until old price entered');
    verify(str_contains($productJs,"mode.value==='discount'") &&
        str_contains($productJs,'benefit.disabled=!discounted'),
        'bundle discounted price UI and benefit flag conditions');
    $table=(string)file_get_contents($root.'admin/src/Table/ProductTable.php');
    verify(str_contains($table,'COM_MINIMARKET_BADGE_BENEFIT_INVALID'),
        'server enforces savings badge eligibility');
    $category=(string)file_get_contents($root.'site/src/View/Category/HtmlView.php');
    verify(str_contains($category,'CategoryDisplayOptions::resolve') &&
        str_contains($category,'show_bundles') &&
        str_contains($category,'show_descendant_bundles'),
        'category displays kits independently of ordinary products');
    $migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.54.sql');
    verify(str_contains($migration,'ADD COLUMN `show_bundles`') &&
        str_contains($migration,'ADD COLUMN `badge_benefit`') &&
        !preg_match('/\bDROP\b|\bDELETE\b|\bTRUNCATE\b/i',$migration),
        '0.5.54 only adds safe optional columns');
    foreach(['admin','site'] as $surface) {
        foreach(['ru-RU','en-GB'] as $lang) {
            $translation=(string)file_get_contents($root.$surface.'/language/'.$lang.'/com_minimarket.ini');
            verify(str_contains($translation,'COM_MINIMARKET_BADGE_BENEFIT_LABEL='),
                'savings label bilingual '.$surface.'/'.$lang);
        }
    }
    echo 'MM_054_UI_OK '.$n." assertions\n";
}
