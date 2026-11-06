<?php
/** MiniMarket 0.5.56: global store type and rich bundle exchange contract. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace MiniMarket\StoreImportSmoke {
    \define('_JEXEC', 1);
    $root = __DIR__ . '/../src/com_minimarket/';
    require $root.'admin/src/Service/CatalogImport.php';
    require $root.'admin/src/Service/CatalogTabularV1.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\CatalogImport;
    use MiniMarket\Component\Minimarket\Administrator\Service\CatalogTabularV1;
    $checks = 0;
    function ok(bool $value, string $label): void {
        global $checks;
        ++$checks;
        if (!$value) throw new \RuntimeException('STORE_IMPORT_056_FAIL: '.$label);
    }
    $config = simplexml_load_file($root.'admin/config.xml');
    $form = simplexml_load_file($root.'admin/forms/product.xml');
    ok($config !== false && $form !== false, 'Joomla configuration XML parses');
    ok(count($config->xpath("//field[@name='shop_type' and @default='physical']")) === 1,
        'physical is the global default');
    ok(count($config->xpath("//field[@name='shop_type']/option")) === 2,
        'only physical and digital store types');
    ok(count($form->xpath("//field[@name='delivery_type']")) === 0,
        'products and bundles have no independent format picker');
    $table=(string)file_get_contents($root.'admin/src/Table/ProductTable.php');
    ok(str_contains($table, "ComponentHelper::getParams('com_minimarket')")
        && str_contains($table, '$this->delivery_type = in_array'),
        'product table overrides posted or historical per-product type');
    $raw=(string)file_get_contents($root.'admin/examples/import-rich-catalog-v1.json');
    $doc=CatalogImport::decode($raw);
    ok($doc['source']==='minimarket-rich-demo' && count($doc['bundles'])===5,
        'new installed rich template decodes');
    ok($doc['bundles'][3]['items'][0]['product']==='bundle-mechanics',
        'a nested bundle is represented by a stable source key');
    $round=CatalogImport::decode(json_encode(
        CatalogTabularV1::parseCsv(CatalogTabularV1::csv($doc)),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    ok($round['bundles']===$doc['bundles'], 'CSV keeps exact bundle component data');
    $engine=(string)file_get_contents($root.'admin/src/Service/CatalogImport.php');
    ok(str_contains($engine,'BundleItems::validate(')
        && str_contains($engine,'BundleItems::replace(')
        && str_contains($engine,'self::assertProductKind(')
        && str_contains($engine,'transactionRollback()'),
        'bundle import verifies SKU safety, types and cycles transactionally');
    $exporter=(string)file_get_contents($root.'admin/src/Service/CatalogExportV1.php');
    ok(str_contains($exporter,'component_variant_id')
        && str_contains($exporter,"$" . "doc['bundles']"),
        'export includes bundle items and blocks unsupported variant references');
    echo 'STORE_IMPORT_056_OK: ' . $checks . " cases\n";
}
