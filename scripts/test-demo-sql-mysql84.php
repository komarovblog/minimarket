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
$schema=file_get_contents(__DIR__.'/../src/com_minimarket/admin/sql/install.mysql.sql');
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
echo "DEMO_MYSQL84_OK: " . total($db,'categories') . " categories, "
    .total($db,'products')." products, ".total($db,'product_variants')
    ." variants, ".total($db,'brands')." brands, ".total($db,'collections')
    ." collections\n";
