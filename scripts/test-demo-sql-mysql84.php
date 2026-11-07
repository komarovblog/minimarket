<?php
/** CI-only test for the user's MySQL 8.4 demo SQL. Never use on a site DB. */
declare(strict_types=1);
if (getenv('MINIMARKET_DEMO_CI') !== '1'
    || getenv('MM_CI_DB_DATABASE') !== 'minimarket_ci_demo84') {
    fwrite(STDERR,"DEMO_SQL_TEST_REFUSED\n"); exit(2);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(getenv('MM_CI_DB_HOST') ?: '127.0.0.1',
    getenv('MM_CI_DB_USER') ?: '',
    getenv('MM_CI_DB_PASSWORD') ?: '',
    'minimarket_ci_demo84', (int)(getenv('MM_CI_DB_PORT') ?: 3306));
$db->set_charset('utf8mb4');
function runMulti(mysqli $db,string $sql,string $stage):void {
    if (!$db->multi_query($sql)) throw new RuntimeException($stage.': '.$db->error);
    do {
        $result=$db->store_result();
        if ($result instanceof mysqli_result) $result->free();
        if (!$db->more_results()) break;
    } while($db->next_result());
    if ($db->errno) throw new RuntimeException($stage.': '.$db->error);
}
function total(mysqli $db,string $table):int {
    return (int)$db->query('SELECT COUNT(*) n FROM `nnop3_minimarket_'.$table.'`')->fetch_assoc()['n'];
}
function verify(bool $condition,string $test):void {
    if (!$condition) throw new RuntimeException('DEMO_SQL_FAIL: '.$test);
}
$schema=file_get_contents(__DIR__.'/fixtures/legacy-install-0.5.72.sql');
$demo=file_get_contents(__DIR__.'/../sample-data/minimarket_mysql84_nnop3_full_demo.sql');
verify($schema!==false && $demo!==false, 'SQL files present');
verify(str_contains($demo,'CHECK (existing_rows=0)'), 'nonempty catalog import guard');
verify(!preg_match('/^\s*(DELETE FROM|TRUNCATE|ALTER TABLE|DROP TABLE)\b/im',$demo),
    'no destructive commands against real Joomla or MiniMarket tables');
verify(str_contains($demo,'sample-10.png') && !str_contains($demo,"'images/minimarket/sample.png'"),
    'all sample image names migrated');
runMulti($db,str_replace('#__','nnop3_',$schema),'fresh install');
// The distributed SQL deliberately targets the user's real database
// "minimarket"; CI safely rewrites only the USE statement to its disposable DB.
$demoCi = str_replace('USE `minimarket`;', 'USE `minimarket_ci_demo84`;', $demo);
runMulti($db,$demoCi,'demo import');
$bundleSql=file_get_contents(__DIR__.'/../sample-data/minimarket_bundles_demo_0_5_51.sql');
verify($bundleSql!==false && str_contains($bundleSql,'nnop3_minimarket_bundle_items'),
    'bundle demo SQL exists');
$bundleCi=str_replace('USE `minimarket`;', 'USE `minimarket_ci_demo84`;', $bundleSql);
runMulti($db,$bundleCi,'bundle seed');
$badgeSql=file_get_contents(__DIR__.'/../sample-data/minimarket_product_badges_demo_0_5_53.sql');
verify($badgeSql!==false,'badge demo SQL found');
$badgeCi=str_replace('USE `minimarket`;', 'USE `minimarket_ci_demo84`;', $badgeSql);
runMulti($db,$badgeCi,'badge demo import');
verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
 WHERE id IN (1001,1090) AND badge_sale=1 AND old_price IS NOT NULL"
)->fetch_assoc()['n']===2,'two sale badge examples with old prices');
verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
 WHERE id IN (1001,1003) AND badge_new=1"
)->fetch_assoc()['n']===2,'timed and indefinite new badge examples');
verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
 WHERE id IN (1002,1004) AND badge_popular=1"
)->fetch_assoc()['n']===2,'timed and indefinite popular badge examples');
runMulti($db,$badgeCi,'idempotent badge import');
verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
 WHERE badge_sale=1")->fetch_assoc()['n']===2,'reimport does not duplicate badge flags');

$pricingFile=file_get_contents(__DIR__.'/../sample-data/minimarket_bundle_prices_demo_0_5_52.sql');
verify($pricingFile!==false,'pricing demo seed present');
runMulti($db,str_replace('USE `minimarket`;', 'USE `minimarket_ci_demo84`;', $pricingFile),
    'bundle pricing demo');
verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
    WHERE id IN (1091,1092) AND bundle_price_mode='discount'")->fetch_assoc()['n']===2,
    'existing bundles can switch to percentage pricing without data reset');
$uiSeed=file_get_contents(__DIR__.'/../sample-data/minimarket_ui_benefit_demo_0_5_54.sql');
verify($uiSeed!==false,'0.5.54 optional demo SQL exists');
$uiCi=str_replace('USE `minimarket`;', 'USE `minimarket_ci_demo84`;', $uiSeed);
runMulti($db,$uiCi,'new 0.5.54 benefit and category visibility examples');
runMulti($db,$uiCi,'idempotent 0.5.54 UI demo');
verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
 WHERE id IN (1091,1092) AND product_type='bundle'
 AND bundle_price_mode='discount' AND badge_benefit=1")->fetch_assoc()['n']===2,
 'two discount kits show benefit on demand');
verify((int)$db->query("SELECT show_bundles FROM nnop3_minimarket_categories
 WHERE id=101 AND alias='myagkie-igrushki'")->fetch_row()[0]===1,
 'category can show bundles independently in demo');




verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
    WHERE product_type='bundle'")->fetch_assoc()['n']===3,'3 bundle products');
verify(total($db,'bundle_items')===6,'6 component rows, including SKU and nested bundle');
$stockService=__DIR__.'/../src/com_minimarket/admin/src/Service/BundleStock.php';
define('_JEXEC',1);
require_once $stockService;
$map=[];
foreach ([
    'products'=>['id','category_id','product_type','state','stock','option_stock_mode'],
    'categories'=>['id','parent_id','state'],
    'variants'=>['id','product_id','stock','state'],
] as $key=>$columns) {
    $result=$db->query('SELECT '.implode(',',$columns).' FROM nnop3_minimarket_'.
        ($key==='variants'?'product_variants':$key));
    $map[$key]=[];
    while ($row=$result->fetch_assoc()) $map[$key][(int)$row['id']]=$row;
}
$map['hasVariants']=[];
foreach ($map['variants'] as $row) $map['hasVariants'][(int)$row['product_id']]=true;
$map['items']=[];
$result=$db->query('SELECT * FROM nnop3_minimarket_bundle_items ORDER BY sort_order,id');
while ($row=$result->fetch_assoc()) {
    $map['items'][(int)$row['bundle_product_id']][]=$row;
}
$bundleClass=\MiniMarket\Component\Minimarket\Administrator\Service\BundleStock::class;
verify($bundleClass::available(1090,$map)===14,'bundle 1090 counts quantities');
verify($bundleClass::available(1091,$map)===9,'bundle 1091 uses exact SKU stock');
verify($bundleClass::available(1092,$map)===9,
    'nested bundle sums repeated component demand (28 / 3 = 9)');
$map['products'][1003]['state']=0;
verify($bundleClass::available(1092,$map)===0,
    'unpublished component blocks nested bundle availability');
$map['products'][1003]['state']=1;
$map['variants'][7004]['stock']=0;
verify($bundleClass::available(1091,$map)===0,'zero SKU stock blocks availability');

verify(total($db,'categories')>=20,'categories imported');
verify(total($db,'products')>=70,'products imported');
verify(total($db,'attributes')>=5,'attributes imported');
verify(total($db,'attribute_values')>=10,'attribute choices imported');
verify(total($db,'order_options')>=3,'order options imported');
verify(total($db,'product_variants')>=10,'SKUs imported');
verify(total($db,'brands')===5,'5 demo brands');
verify(total($db,'collections')===6,'6 demo collections');
verify(total($db,'product_collections')>total($db,'products'),
    'some products have multiple collections');
verify(total($db,'product_groups')>=5,'named product links imported');
$ordinaryGroupCount=(int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_product_groups
 WHERE type<>'bundle'")->fetch_row()[0];
$bundleGroupCount=(int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_product_groups
 WHERE type='bundle'")->fetch_row()[0];
verify($ordinaryGroupCount+$bundleGroupCount===total($db,'product_groups'),
    'dashboard group counters partition exactly the existing link groups');
verify($ordinaryGroupCount>=1 && $bundleGroupCount>=0,
    'dashboard correctly allows zero bundle relation groups in current demo catalog');
verify(total($db,'brands')>=1 && total($db,'collections')>=1
    && total($db,'order_options')>=1 && total($db,'attributes')>=1,
    'dashboard ancillary section counters map to populated real tables');
verify(total($db,'product_images')>0,'gallery imported');
verify(total($db,'product_image_variants')>0,'SKU cover mapping imported');
$unbranded=(int)$db->query('SELECT COUNT(*) n FROM nnop3_minimarket_products WHERE brand_id IS NULL')->fetch_assoc()['n'];
verify($unbranded===0,'every demo product receives brand');
$badPath=$db->query("SELECT image FROM nnop3_minimarket_products
    WHERE image NOT REGEXP '^images/minimarket/sample-(0[1-9]|10)\\\\.png$' LIMIT 1");
verify($badPath->num_rows===0,'all main images use provided files');
$allImages=$db->query("SELECT COUNT(DISTINCT image) n FROM nnop3_minimarket_products")->fetch_assoc()['n'];
verify((int)$allImages===10,'all ten sample images used');
$joomla=$db->query("SHOW TABLES LIKE 'nnop3_users'")->num_rows;
verify($joomla===0,'demo SQL test does not create Joomla system tables');
// Add a separate unpublished digital sample AFTER all historical demo data assertions.
$digitalSql=file_get_contents(__DIR__.'/../sample-data/minimarket_digital_demo_0_5_55.sql');
verify($digitalSql!==false,'0.5.55 digital demo found');
$digitalCi=str_replace('USE `minimarket`;', 'USE `minimarket_ci_demo84`;', $digitalSql);
runMulti($db,$digitalCi,'digital demo import');
runMulti($db,$digitalCi,'digital demo repeatable import');
verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
 WHERE id=1200 AND sku='DIGITAL-STEM-1200' AND delivery_type='digital'
 AND product_type='simple' AND state=0")->fetch_assoc()['n']===1,
 'one unpublished digital example, no duplicates');
verify((int)$db->query("SELECT COUNT(*) n FROM nnop3_minimarket_products
 WHERE delivery_type='digital'")->fetch_assoc()['n']===1,
 'old demo products stay physical by default');
// 0.5.57: the cumulative catalog updates an existing database in-place.
$updateSql=file_get_contents(__DIR__.'/../sample-data/minimarket_mysql84_nnop3_update_0_5_65.sql');
verify($updateSql!==false,'cumulative 0.5.60 SQL exists');
$updateCi=str_replace('USE `minimarket`;','USE `minimarket_ci_demo84`;',$updateSql);
$demoOptions=$db->query("SHOW COLUMNS FROM nnop3_minimarket_categories LIKE 'product_layout'")->fetch_assoc();
verify((string)($demoOptions['Default']??'')==='inherit','new category layout inherits on fresh install');
$beforeProducts=total($db,'products');
$beforeBrands=total($db,'brands');
$beforeCollections=total($db,'collections');
$db->query("UPDATE nnop3_minimarket_products SET price=1234.56 WHERE id=1001 AND sku='TOY-1001'");
$db->query("DELETE FROM nnop3_minimarket_product_group_members WHERE product_id=1001 AND group_id=901");
runMulti($db,$updateCi,'0.5.58 cumulative update on existing catalog');
runMulti($db,$updateCi,'0.5.58 second update without duplicates');
verify(total($db,'products')===$beforeProducts,'cumulative update does not duplicate products');
verify(total($db,'brands')===$beforeBrands,'cumulative update does not duplicate brands');
verify(total($db,'collections')===$beforeCollections,'cumulative update does not duplicate collections');
verify((float)$db->query("SELECT price FROM nnop3_minimarket_products WHERE id=1001")->fetch_row()[0]===1234.56,
    'manual demo price remains untouched');
verify((int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_product_group_members WHERE product_id=1001 AND group_id=901")->fetch_row()[0]===1,
    'missing demo relation gets restored exactly once');
verify(total($db,'bundle_items')===6,'bundle components not duplicated');

// Storefront SKU data contract against ACTUAL imported MySQL 8.4 data.
// A variant is selectable only if every product-local dimension maps to a
// valid value and matches selection_json (v:<code>), not an old attribute.
$skuRows=$db->query("SELECT id,product_id,sku,selection_json,stock
 FROM nnop3_minimarket_product_variants WHERE state=1");
$visibleSkus=0;$orderableSkus=0;$twoAxisSkus=0;
while ($sku=$skuRows->fetch_assoc()) {
    $id=(int)$sku['id'];$pid=(int)$sku['product_id'];
    $selection=json_decode((string)$sku['selection_json'],true);
    verify(is_array($selection) && count($selection)>=1 && count($selection)<=4,
        'public SKU selection is normalized JSON '.$id);
    $dims=$db->query("SELECT d.code AS axis,v.code AS value_code
      FROM nnop3_minimarket_variant_value_assignments a
      JOIN nnop3_minimarket_variant_dimensions d ON d.id=a.dimension_id
      JOIN nnop3_minimarket_variant_dimension_values v
        ON v.id=a.value_id AND v.dimension_id=d.id
      WHERE a.variant_id=$id AND d.product_id=$pid");
    $expected=[];
    while ($dim=$dims->fetch_assoc()) {
        verify(!isset($expected[$dim['axis']]), 'public SKU axis used only once '.$id);
        $expected[$dim['axis']]='v:'.$dim['value_code'];
    }
    verify($expected==$selection && count($expected)===count($selection),
        'SKU selection JSON matches actual configured local options '.$id);
    $axisTotal=(int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_variant_dimensions
      WHERE product_id=$pid")->fetch_row()[0];
    verify(count($selection)===$axisTotal,
        'every public SKU includes ALL required order-option axes '.$id);
    if ($axisTotal>1)$twoAxisSkus++;
    if ((int)$sku['stock']>0)$orderableSkus++;
    $visibleSkus++;
}
verify($visibleSkus>=15 && $orderableSkus>=12 && $twoAxisSkus>=2,
    'demo has usable one- and two-option SKU combinations');

verify((int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_product_images WHERE product_id=1012")->fetch_row()[0]>=3,
 'example product has three or more gallery images');
verify((int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_product_videos WHERE product_id=1012 AND is_promo=1")->fetch_row()[0]===1,
 'one optional promo remains after repeated SQL');


// Upgrade the populated historical catalog to the canonical schema.
// Every product ID and pre-existing dictionary value ID must survive.
$oldScalar=total($db,'product_attribute_scalars');
$oldMultiple=total($db,'product_attribute_multi_values');
$oldLinks=total($db,'product_attribute_options');
verify($oldScalar>=462 && $oldMultiple===111 && $oldLinks===77,
    'historical product attributes available before migration');
$upgrade=file_get_contents(__DIR__.'/../src/com_minimarket/admin/sql/updates/mysql/0.5.73.sql');
verify(is_string($upgrade),'canonical upgrade SQL exists');
runMulti($db,str_replace('#__','nnop3_',$upgrade),'canonical attributes upgrade');
verify(total($db,'attributes')===12,'all 12 attribute definitions retained');
verify(total($db,'attribute_values')===52,'13 prior dictionary IDs plus 39 presets');
verify(total($db,'product_attribute_options')===496,'419 existing textual assignments now use IDs');
verify(total($db,'product_attribute_scalars')===462,'number and boolean scalar values retained');
verify((int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_attribute_values
 WHERE id BETWEEN 301 AND 313")->fetch_row()[0]===13,
 'existing color value IDs preserved');
verify($db->query("SHOW TABLES LIKE 'nnop3_minimarket_product_attribute_multi_values'")->num_rows===0,
 'old multi storage removed from installed schema');
foreach(['input_mode','number_options','text_options'] as $oldColumn) {
    verify($db->query("SHOW COLUMNS FROM nnop3_minimarket_attributes
      LIKE '$oldColumn'")->num_rows===0, 'obsolete metadata removed: '.$oldColumn);
}
verify((int)$db->query("SELECT COUNT(*) FROM nnop3_minimarket_attributes
 WHERE value_source='legacy'")->fetch_row()[0]===0, 'all attributes in canonical mode');
echo "DEMO_MYSQL84_OK: " . total($db,'categories') . " categories, "
    .total($db,'products')." products, ".total($db,'product_variants')
    ." variants, ".total($db,'brands')." brands, ".total($db,'collections')
    ." collections\n";
