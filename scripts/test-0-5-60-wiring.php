<?php
/** Static coverage audit for category override, editor/frontend and exchange wiring.
 * Never claims to click every Joomla button: browser E2E is separate.
 */
declare(strict_types=1);
$root=__DIR__.'/../src/com_minimarket/';
$checks=0;
function audit60(bool $ok,string $message):void {
    global $checks; ++$checks;
    if (!$ok) throw new RuntimeException('MM60_WIRING_FAIL: '.$message);
}
$config=simplexml_load_file($root.'admin/config.xml');
$category=simplexml_load_file($root.'admin/forms/category.xml');
$product=simplexml_load_file($root.'admin/forms/product.xml');
$video=simplexml_load_file($root.'admin/forms/product_video.xml');
audit60($config!==false && $category!==false && $product!==false && $video!==false,
 'all four critical form XML documents parse');
$global=['category_show_children'=>'show_children',
 'category_show_products'=>'show_products',
 'category_show_bundles'=>'show_bundles',
 'category_show_descendant_products'=>'show_descendant_products',
 'category_show_descendant_bundles'=>'show_descendant_bundles',
 'category_product_layout'=>'product_layout',
 'category_cards_xs'=>'cards_xs',
 'category_cards_sm'=>'cards_sm',
 'category_cards_md'=>'cards_md',
 'category_cards_lg'=>'cards_lg',
 'category_cards_xl'=>'cards_xl',
 'category_cards_xxl'=>'cards_xxl'];
$resolver=(string)file_get_contents($root.'site/src/Service/CategoryDisplayOptions.php');
$schema=(string)file_get_contents($root.'admin/sql/install.mysql.sql');
$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.58.sql');
$table=(string)file_get_contents($root.'admin/src/Table/CategoryTable.php');
foreach($global as $configName=>$local) {
    audit60(count($config->xpath("//fieldset[@name='display']/field[@name='$configName']"))===1,
        'global Categories must declare '.$configName);
    audit60(count($category->xpath("//fieldset[@name='display']/field[@name='$local']"))===1,
        'local category must override '.$local);
    $resolved = str_starts_with($configName,'category_cards_')
        ? str_contains($resolver,"'category_cards_'")
            && str_contains($resolver,"'cards_'")
        : str_contains($resolver,$configName) && str_contains($resolver,$local);
    audit60($resolved,'frontend must resolve '.$configName.' into '.$local);
    // All but the original show_* fields were added in 0.5.58 migration.
    audit60(str_contains($schema,'`'.$local.'`'),
        'fresh schema must persist '.$local);
}
foreach(['show_descendant_products','show_descendant_bundles','product_layout'] as $field) {
    audit60(str_contains($migration,'ADD COLUMN `'.$field.'`'),
        'historical 0.5.58 migration must add '.$field);
}
$gridMigration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.71.sql');
foreach(['xs','sm','md','lg','xl','xxl'] as $tier) {
    audit60(str_contains($schema,'`cards_'.$tier.'`'),
        'new DB must have cards_'.$tier);
    audit60(str_contains($table,"'cards_".$tier."'"),
        'category save must validate cards_'.$tier);
}
audit60(str_contains($gridMigration,'RENAME COLUMN `cards_mobile` TO `cards_xs`')
    && str_contains($gridMigration,'ADD COLUMN `cards_sm`')
    && str_contains($gridMigration,'ADD COLUMN `cards_xxl`'),
    '0.5.71 migration preserves existing four tiers');
$siteRepo=(string)file_get_contents($root.'site/src/Service/CatalogRepository.php');
$siteView=(string)file_get_contents($root.'site/src/View/Category/HtmlView.php');
$siteTpl=(string)file_get_contents($root.'site/tmpl/category/default.php');
audit60(str_contains($siteRepo,'descendantIds($id)') &&
 str_contains($siteRepo,'$descendantProducts') &&
 str_contains($siteRepo,'$descendantBundles') &&
 str_contains($siteView,'CategoryDisplayOptions::resolve'),
 'global/local descendant flags feed actual product query');
audit60(str_contains($siteTpl,'bootstrapGridClasses'),
 'grid setting must reach frontend template');
foreach(['badge_sale_display','badge_benefit_display'] as $name) {
    audit60(count($product->xpath("//field[@name='$name']"))===1,
        'product form must expose '.$name);
}
$badge=(string)file_get_contents($root.'admin/src/Service/ProductBadges.php');
audit60(str_contains($badge,'$saleDisplay = (string)') &&
 str_contains($badge,'$benefitDisplay = (string)'),
 'old products must get safe badge display defaults');
$promoForm=simplexml_load_file($root.'admin/forms/product_promo_video.xml');
audit60($promoForm!==false &&
 count($video->xpath("//field[@name='is_promo']"))===0 &&
 count($product->xpath("//field[@name='promo_video']"))===1,
 'dedicated promo video editor sits above ordinary videos');
$promoJs=(string)file_get_contents($root.'media/js/promo-video.js');
$promoTpl=(string)file_get_contents($root.'site/tmpl/product/default.php');
audit60(str_contains($promoJs,"player.addEventListener('click',toggle)") &&
 str_contains($promoTpl,'aria-expanded="false"'),
 'promo click and keyboard exposure');
audit60(str_contains($promoTpl,'data-mm-variant-axis') &&
 str_contains(file_get_contents($root.'media/js/variants.js'),"addEventListener('change'"),
 'SKU options have server HTML and JS change handler');
$importController=(string)file_get_contents($root.'admin/src/Controller/ImportController.php');
$importTpl=(string)file_get_contents($root.'admin/tmpl/import/default.php');
$archive=(string)file_get_contents($root.'admin/src/Service/CatalogArchiveV2.php');
audit60(str_contains($importController,'CatalogArchiveV2::') &&
 str_contains($importTpl,'import.applyFull') &&
 str_contains($importTpl,'import.cancelFull') &&
 str_contains($archive,'transactionRollback()'),
 'v2 archive preview/apply/cancel and atomic rollback are wired');
foreach(['ru-RU','en-GB'] as $lang) {
 $ini=parse_ini_file($root.'admin/language/'.$lang.'/com_minimarket.ini');
 foreach(['COM_MINIMARKET_CONFIG_CATEGORIES','COM_MINIMARKET_VIDEO_PROMO',
  'COM_MINIMARKET_BADGE_DISPLAY'] as $name)
  audit60(isset($ini[$name]),'admin locale '.$lang.' '.$name);
}
echo "MM60_WIRING_OK: $checks checks (static, not browser E2E)\n";
