<?php
/** Basic MiniMarket catalog exchange v1: JSON, CSV, XLSX and safe export. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace MiniMarket\TabularV1Smoke {
    \define('_JEXEC',1);
    $root=__DIR__ . '/../src/com_minimarket/';
    require $root.'admin/src/Service/CatalogImport.php';
    require $root.'admin/src/Service/CatalogTabularV1.php';
    require $root.'admin/src/Service/CatalogExportV1.php';
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\CatalogImport;
    use MiniMarket\Component\Minimarket\Administrator\Service\CatalogTabularV1 as T;
    use MiniMarket\Component\Minimarket\Administrator\Service\CatalogExportV1 as E;
    $count=0;
    function ok(bool $test,string $label):void{
        global $count;
        ++$count;
        if (!$test) throw new \RuntimeException('TABULAR_V1_FAIL: '.$label);
    }
    $source=(string)file_get_contents($root.'admin/examples/import-catalog-v1.json');
    $doc=CatalogImport::decode($source);
    $csv=T::csv($doc);
    ok(str_starts_with($csv,"\xEF\xBB\xBF"),'UTF-8 BOM for Excel CSV');
    $decoded=CatalogImport::decode(json_encode(T::parseCsv($csv),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
    foreach (T::FIELDS as $section=>$fields) {
        ok(($decoded[$section]??[])==($doc[$section]??[]),
            'CSV preserves '.$section.' including nested values/links');
    }
    ok($decoded['source']==='minimarket-demo','CSV source identity');
    ok(!empty($decoded['options'][0]['values']),'CSV values JSON array survives');

    $rich = CatalogImport::decode((string)file_get_contents(
        $root.'admin/examples/import-rich-catalog-v1.json'));
    $richCsv = CatalogImport::decode(json_encode(T::parseCsv(T::csv($rich)),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    ok($richCsv['bundles'] == $rich['bundles'],
        'rich bundle items survive CSV roundtrip');
    $richXlsx = CatalogImport::decode(json_encode(T::parseXlsx(T::xlsx($rich)),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    ok($richXlsx['bundles'] == $rich['bundles'],
        'rich bundle items survive XLSX roundtrip');
    $formulaSafe=$doc;
    $formulaSafe['categories'][0]['title']='=HYPERLINK("https://example.invalid")';
    $guardedCsv=T::csv($formulaSafe);
    ok(str_contains($guardedCsv,"'=HYPERLINK("),
        'CSV escapes potentially executable spreadsheet formulas');
    ok(T::parseCsv($guardedCsv)['categories'][0]['title']===
        $formulaSafe['categories'][0]['title'],
        'CSV formula escaping is reversible without altering the catalog');
    $xlsx=T::xlsx($doc);
    ok(str_starts_with($xlsx,'PK'),'XLSX real OOXML zip');
    $temp=tempnam(sys_get_temp_dir(),'mm-smoke-');
    file_put_contents($temp,$xlsx);
    $archive=new \ZipArchive();
    ok($archive->open($temp)===true,'XLSX ZIP opens');
    ok($archive->getFromName('xl/styles.xml')!==false &&
        str_contains((string)$archive->getFromName('xl/styles.xml'),'numFmtId="49"'),
        'Excel columns have text formatting to preserve leading-zero SKUs');
    $archive->close();
    unlink($temp);
    $round=CatalogImport::decode(json_encode(T::parseXlsx($xlsx),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
    foreach (T::FIELDS as $section=>$fields) {
        ok(($round[$section]??[])==($doc[$section]??[]),
            'XLSX preserves '.$section.' with stable keys');
    }
    ok($round['products'][0]['sku']==='TOPKA-10',
        'SKU remains exact text in workbook');
    $empty=CatalogImport::decode((string)file_get_contents($root.'admin/examples/import-empty-v1.json'));
    ok(T::parseXlsx(T::xlsx($empty))['source']==='my-catalog',
        'blank XLSX template has valid source and all headers');

    foreach ([
        "section,source,adopt_existing,key\nproducts,xx,0,abc\n",
        "bad,csv,headers",
        "section,source,adopt_existing,key\nmeta,bad source,0,\n"
    ] as $bad) {
        try {T::parseCsv($bad);ok(false,'invalid CSV rejected');}
        catch (\InvalidArgumentException $e) {ok(true,'invalid CSV rejected');}
    }
    $withAdoption=$doc;$withAdoption['adopt_existing']=true;
    ok(T::parseXlsx(T::xlsx($withAdoption))['adopt_existing']===true,
        'adopt existing boolean survives XLSX');

    final class Q {
        public string $table='';
        public function select($v):self{return $this;}
        public function from($v):self{$this->table=(string)$v;return $this;}
        public function order($v):self{return $this;}
    }
    final class DB implements DatabaseInterface {
        private ?Q $query=null;
        public array $tables=[];
        public function getQuery($new=true):Q{return new Q();}
        public function quoteName($name):string{return (string)$name;}
        public function setQuery($query):void{$this->query=$query;}
        public function loadAssocList():array{return $this->tables[$this->query->table]??[];}
    }
    $db=new DB();
    $db->tables=[
        '#__minimarket_categories'=>[['id'=>1,'title'=>'Топки','alias'=>'topki','parent_id'=>0,'state'=>1]],
        '#__minimarket_attributes'=>[],
        '#__minimarket_attribute_values'=>[],
        '#__minimarket_order_options'=>[],
        '#__minimarket_order_option_values'=>[],
        '#__minimarket_products'=>[['id'=>7,'title'=>'Топка','alias'=>'topka',
            'sku'=>'000123','price'=>'490.00','stock'=>2,'option_stock_mode'=>0,
            'category_id'=>1,'state'=>1]],
        '#__minimarket_product_groups'=>[],
        '#__minimarket_product_group_members'=>[],
        '#__minimarket_category_attributes'=>[],
        '#__minimarket_category_order_options'=>[],
    ];
    $export=E::document($db);
    ok($export['source']==='minimarket-export' &&
        $export['products'][0]['sku']==='000123',
        'export stable IDs and preserve SKU leading zeros');
    $check=CatalogImport::decode(json_encode(T::parseCsv(T::csv($export)),JSON_THROW_ON_ERROR));
    ok($check['categories'][0]['key']==='category-1' &&
        $check['products'][0]['category']==='category-1',
        'export/import roundtrip retains category reference');
    $db->tables['#__minimarket_products'][0]['sku']='';
    try {E::document($db);ok(false,'non-restorable blank base SKU rejected');}
    catch (\RuntimeException $error) {
        ok($error->getMessage()==='COM_MINIMARKET_EXPORT_INCOMPATIBLE',
            'export refuses to invent SKU for variant products');
    }

    $controller=(string)file_get_contents($root.'admin/src/Controller/ImportController.php');
    $ui=(string)file_get_contents($root.'admin/tmpl/import/default.php');
    ok(str_contains($controller,"CatalogTabularV1::parseXlsx") &&
       str_contains($controller,"CatalogTabularV1::parseCsv") &&
       str_contains($controller,"CatalogExportV1::document") &&
       str_contains($controller,"Session::checkToken('get')"),
       'routes use shared importer and protected exports');
    ok(str_contains($ui,'task=import.export') &&
       str_contains($ui,'task=import.example') &&
       str_contains($ui,'accept=".xlsx,.csv,.json') &&
       !str_contains($ui,'github.com'),
       'admin offers local Excel/CSV/JSON templates and export');
    echo 'CATALOG_TABULAR_V1_OK: '.$count." cases\n";
}
