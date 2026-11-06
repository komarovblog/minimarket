<?php
/** Import package v1: strict schema validation and controller wiring. */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace MiniMarket\ImportTest {
    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/CatalogImport.php';

    use MiniMarket\Component\Minimarket\Administrator\Service\CatalogImport;

    $count = 0;
    function verify(bool $value, string $message): void
    {
        global $count;
        ++$count;
        if (!$value) throw new \RuntimeException('CATALOG_IMPORT_FAIL: ' . $message);
    }

    $example = file_get_contents(__DIR__ . '/../examples/import-catalog-v1.json');
    verify(is_string($example) && $example !== '', 'example package bundled');
    $doc = CatalogImport::decode($example);
    verify($doc['version'] === 1 && $doc['source'] === 'minimarket-demo', 'versioned source identity');
    verify(count($doc['categories']) === 3, 'three sample categories');
    verify(count($doc['products']) === 3, 'three independent sample products');
    verify(count($doc['attributes']) === 3, 'sample attributes with multiple types');
    verify(count($doc['options']) === 2 && count($doc['options'][0]['values']) === 3,
        'global order options and values in sample');
    verify(count($doc['category_options']) === 2
        && $doc['category_options'][0]['mode'] === 'required',
        'category order-option relationships use required mode');

    $explicitAdoption = $doc;
    $explicitAdoption['adopt_existing'] = true;
    verify(CatalogImport::decode(json_encode($explicitAdoption, JSON_THROW_ON_ERROR))['adopt_existing'] === true,
        'adopting an existing local record requires an explicit boolean');
    $mutations = [
        function ($d) { $d['version'] = 9; return $d; },
        function ($d) { $d['source'] = '../../unsafe'; return $d; },
        function ($d) { $d['adopt_existing'] = 'yes'; return $d; },
        function ($d) { $d['unknown_section'] = []; return $d; },
        function ($d) { $d['categories'][0]['key'] = '../../unsafe'; return $d; },
        function ($d) { $d['categories'][1]['key'] = $d['categories'][0]['key']; return $d; },
        function ($d) { $d['options'][0]['values'][1]['code'] = $d['options'][0]['values'][0]['code']; return $d; },
        function ($d) { $d['products'] = 'not an array'; return $d; },
        function ($d) { $d['products'][0]['delete_missing'] = true; return $d; },
        function ($d) { $d['categories'] = array_fill(0, 2501, ['key'=>'dup']); return $d; },
    ];
    foreach ($mutations as $mutation) {
        try {
            CatalogImport::decode(json_encode($mutation($doc), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            verify(false, 'invalid schema accepted');
        } catch (\InvalidArgumentException $e) {
            verify(true, 'invalid schema rejected');
        }
    }

    $bundled = __DIR__ . '/../src/com_minimarket/admin/examples/';
    $sample = (string) file_get_contents($bundled . 'import-catalog-v1.json');
    $blank = (string) file_get_contents($bundled . 'import-empty-v1.json');
    verify(CatalogImport::decode($sample)['source'] === 'minimarket-demo',
        'filled example installed inside Joomla, no GitHub required');
    verify(CatalogImport::decode($blank)['source'] === 'my-catalog',
        'empty template installed and valid');
    $base = __DIR__ . '/../src/com_minimarket/';
    $manifest = file_get_contents($base . 'minimarket.xml');
    preg_match('/<version>([^<]+)<\/version>/', $manifest, $versionMatches);
    verify(version_compare($versionMatches[1] ?? '0', '0.5.35', '>=')
        && str_contains($manifest, 'view=import'), 'import menu remains available on later component versions');
    $ui = file_get_contents($base . 'admin/tmpl/import/default.php');
    verify(!str_contains($ui, 'github.com') &&
        str_contains($ui, 'task=import.example') &&
        str_contains($ui, 'COM_MINIMARKET_IMPORT_TITLE_ONLY') &&
        str_contains($ui, 'COM_MINIMARKET_IMPORT_COMPACT_HELP'),
        'all examples are local downloads and instructions inside administrator');
    $controller = file_get_contents($base . 'admin/src/Controller/ImportController.php');
    verify(str_contains($controller, "Session::checkToken('get')") &&
        str_contains($controller, "function example(): void") &&
        str_contains($controller, "assertCanImport()") &&
        str_contains($controller, "Content-Disposition"),
        'local example download checks administrator permissions and token');
    verify(str_contains($controller, "Session::checkToken('post')")
        && str_contains($controller, "authorise('core.create'")
        && str_contains($controller, "authorise('core.edit'"),
        'import POST tasks require CSRF and create/edit privileges');
    verify(str_contains($controller, 'is_uploaded_file(')
        && str_contains($controller, 'MAX_UPLOAD'), 'uploaded files are bounded and verified');
    verify(str_contains($controller, 'CatalogImport::run($db, $doc, false)')
        && str_contains($controller, 'CatalogImport::run($db, $doc, true)'),
        'preview and apply use same importer engine');
    $engine = file_get_contents($base . 'admin/src/Service/CatalogImport.php');
    verify(str_contains($engine, '$db->transactionRollback()')
        && str_contains($engine, '$db->transactionCommit()')
        && str_contains($engine, 'self::mappedId('),
        'imports are transactional and resolve external source keys');
    $sql = file_get_contents($base . 'admin/sql/updates/mysql/0.5.35.sql');
    verify(str_contains($sql, 'import_entity_keys')
        && str_contains($sql, 'PRIMARY KEY'),
        'stable cross-section import mapping exists');
    echo 'CATALOG_IMPORT_PASS: ' . $count . " cases\n";
}
