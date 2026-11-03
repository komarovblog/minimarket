<?php
/** SKU-v4: options, independent product groups and inventory mode contracts. */
declare(strict_types=1);

namespace Joomla\Database { interface DatabaseInterface {} }

namespace MiniMarket\OptionArchitecture {
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/admin/src/Service/ProductGroups.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;

    $checks=0;
    function verify(bool $condition,string $why): void {
        global $checks;
        $checks++;
        if (!$condition) throw new \RuntimeException('OPTION_MODEL_FAIL: '.$why);
    }

    verify(ProductGroups::normalize('0','') === [0,''],
        'unlinked product does not need a group');
    verify(ProductGroups::normalize('7','') === [7,''],
        'independent product links to existing group');
    verify(ProductGroups::normalize(0,'T-shirt Classic') === [0,'T-shirt Classic'],
        'new product group has no SEO or sale identity');
    foreach ([
        ['7','new title'], ['-1',''], ['7.5',''], ['4abc',''],
        [0,'<script>'], [0,str_repeat('A',256)], [[], ''], [0,[]],
    ] as [$id,$title]) {
        try {
            ProductGroups::normalize($id,$title);
            verify(false,'invalid group input accepted');
        } catch (\InvalidArgumentException $error) {
            verify($error->getMessage()==='COM_MINIMARKET_PRODUCT_GROUP_INVALID',
                'reject invalid/mixed product grouping');
        }
    }
    $form=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product.xml');
    verify($form !== false,'valid Joomla form');
    $modeField=$form->xpath("//fieldset[@name='details']/field[@name='option_stock_mode' and @type='radio']");
    verify(count($modeField)===1,'product has native shared vs per-combination stock switch');
    verify((string)$modeField[0]['default']==='1','new product defaults to physical SKU-level inventory');
    verify(count($modeField[0]->xpath("./option[@value='0']"))===1 &&
        count($modeField[0]->xpath("./option[@value='1']"))===1,
        'optional shared stock mode remains available explicitly');
    $skuDatabaseSchema=file_get_contents(__DIR__.'/../src/com_minimarket/admin/sql/updates/mysql/0.5.17.sql');
    verify(str_contains($skuDatabaseSchema,'UNIQUE KEY `idx_mm_variant_sku` (`sku`)') &&
        str_contains($skuDatabaseSchema,'UNIQUE KEY `idx_mm_variant_combo`'),
        'each physical color/size combination must have its own unique SKU');
    $defaultMigration=file_get_contents(__DIR__.'/../src/com_minimarket/admin/sql/updates/mysql/0.5.28.sql');
    verify(str_contains($defaultMigration,'ALTER COLUMN `option_stock_mode` SET DEFAULT 1')
        && !str_contains($defaultMigration,'UPDATE `#__minimarket_products`')
        && !str_contains($defaultMigration,'DELETE FROM'),
        'default changes without rewriting existing shared stock products or SKU rows');
    verify(count($form->xpath("//fieldset[@name='groups']/field[@name='group_id' and @type='Productgroup']"))===1,
        'independent URLs are linked by a Joomla group selector');
    verify(count($form->xpath("//fieldset[@name='groups']/field[@name='new_group_title']"))===1,
        'can create group directly from product card');
    verify(count($form->xpath("//fieldset[@name='variants']/field[@name='variant_dimensions']"))===1,
        'order options are distinct from informational attributes');
    verify(count($form->xpath("//fieldset[@name='attributes']/field[@name='attribute_values']"))===1,
        'descriptive attributes remain independent');
    $model=file_get_contents(__DIR__.'/../src/com_minimarket/admin/src/Model/ProductModel.php');
    verify(str_contains($model,'ProductGroups::assign') &&
        str_contains($model,'$db->transactionCommit()'),
        'group membership is saved in product transaction');
    $view=file_get_contents(__DIR__.'/../src/com_minimarket/site/src/View/Product/HtmlView.php');
    verify(str_contains($view,'getRelatedProducts($id)'),
        'storefront resolves links between independent products');
    $template=file_get_contents(__DIR__.'/../src/com_minimarket/site/tmpl/product/default.php');
    verify(str_contains($template,'RouteHelper::product((int)$linked->id)') &&
        str_contains($template,'data-mm-stock-mode'),
        'linked products retain individual URLs while options select stock mode');
    $siteJs=file_get_contents(__DIR__.'/../src/com_minimarket/media/js/variants.js');
    verify(str_contains($siteJs,'sharedStock ? baseStock : Number(variant.stock)'),
        'public stock respects shared-vs-combination mode');
    $adminJs=file_get_contents(__DIR__.'/../src/com_minimarket/media/js/variants-local.js');
    verify(str_contains($adminJs,'sharedInventory()') && str_contains($adminJs,'refreshInventory()'),
        'SKU stock matrix hidden in shared-stock mode');
    $sql=file_get_contents(__DIR__.'/../src/com_minimarket/admin/sql/updates/mysql/0.5.26.sql');
    verify(str_contains($sql,'WHERE EXISTS') && str_contains($sql,'option_stock_mode'),
        'upgrade retains old SKU inventory mode');
    verify(str_contains($sql,'product_group_members') &&
        str_contains($sql,'FOREIGN KEY'),
        'link tables have database foreign key references');

    echo 'OPTION_MODEL_PASS: '.$checks." cases\n";
}
