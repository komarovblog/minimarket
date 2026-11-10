<?php
/**
 * Test the 0.5.77 Joomla update on disposable MySQL 8.4 shadow tables,
 * including migration of several old preset/free values and preserving
 * previous data in the archive. Never touches a real Joomla installation.
 */
declare(strict_types=1);
if (getenv('MINIMARKET_CANONICAL_CI')!=='1'
    || getenv('MM_CI_DB_DATABASE')!=='minimarket_ci_canonical73') {
    throw new RuntimeException('MM77_SQL_TEST_REFUSED_OUTSIDE_CI');
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(
    getenv('MM_CI_DB_HOST')?:'127.0.0.1',
    getenv('MM_CI_DB_USER')?:'',
    getenv('MM_CI_DB_PASSWORD')?:'',
    'minimarket_ci_canonical73',
    (int)(getenv('MM_CI_DB_PORT')?:3306)
);
$db->set_charset('utf8mb4');
$prefix='mm77_';
$tables=['attribute_multivalue_archive','product_attribute_scalars',
    'product_attribute_options','product_attribute_free_values','attributes'];
foreach ($tables as $name) $db->query('DROP TABLE IF EXISTS '.$prefix.$name);
try {
    foreach (array_diff($tables,['attribute_multivalue_archive']) as $name) {
        $db->query('CREATE TABLE '.$prefix.$name.' LIKE nnop3_minimarket_'.$name);
    }
    // A 0.5.76 database still allowed multiple entries.
    $db->query('ALTER TABLE mm77_product_attribute_options DROP INDEX idx_mm_pa_one_value');
    $db->query('ALTER TABLE mm77_product_attribute_free_values DROP INDEX idx_mm_free_one_value');
    $db->query("INSERT INTO mm77_attributes
       (id,title,code,input_type,value_source,is_multiple,
        filter_mode,filter_multiple,state)
       VALUES (10,'Material','material77','text','preset',1,'checkboxes',1,1),
              (11,'Size','size77','number','manual',1,'slider',0,1)");
    $db->query('INSERT INTO mm77_product_attribute_options
        (product_id,attribute_id,value_id)
        VALUES (101,10,100),(101,10,101),(101,10,102)');
    $db->query("INSERT INTO mm77_product_attribute_free_values
        (product_id,attribute_id,value_text,sort_order)
        VALUES (101,11,'5',0),(101,11,'10',1)");
    $sql=file_get_contents(dirname(__DIR__).'/src/com_minimarket/admin/sql/updates/mysql/0.5.77.sql');
    if (!is_string($sql)) throw new RuntimeException('MM77_SQL_MISSING');
    $sql=str_replace('#__minimarket_',$prefix,$sql);
    foreach (explode(';',$sql) as $statement) {
        $statement=trim((string)preg_replace('/^\s*--[^\n]*$/m','',$statement));
        if ($statement==='') continue;
        // All schema statements must be acceptable via Joomla's prepared driver.
        $query=$db->prepare($statement);
        $query->execute();
        $query->close();
    }
    $value=static function(string $query) use ($db):string {
        return (string)$db->query($query)->fetch_row()[0];
    };
    if ($value('SELECT COUNT(*) FROM mm77_product_attribute_options WHERE product_id=101')!=='1'
        || $value('SELECT value_id FROM mm77_product_attribute_options WHERE product_id=101')!=='100') {
        throw new RuntimeException('MM77_ACTIVE_PRESET_NOT_SINGLE');
    }
    if ($value('SELECT value_number FROM mm77_product_attribute_scalars WHERE product_id=101 AND attribute_id=11')!=='5.0000') {
        throw new RuntimeException('MM77_MANUAL_NUMBER_NOT_MIGRATED');
    }
    if ($value('SELECT COUNT(*) FROM mm77_product_attribute_free_values')!=='0'
        || $value('SELECT COUNT(*) FROM mm77_attribute_multivalue_archive')!=='4') {
        throw new RuntimeException('MM77_HISTORY_LOST');
    }
    if ($value("SELECT COUNT(*) FROM mm77_attribute_multivalue_archive WHERE source_kind='preset_extra'")!=='2'
        || $value("SELECT COUNT(*) FROM mm77_attribute_multivalue_archive WHERE source_kind='manual_legacy'")!=='2') {
        throw new RuntimeException('MM77_HISTORY_WRONG_VALUES');
    }
    if ($value('SELECT COUNT(*) FROM mm77_attributes WHERE is_multiple<>0')!=='0'
        || $value('SELECT filter_multiple FROM mm77_attributes WHERE id=10')!=='1') {
        throw new RuntimeException('MM77_FILTER_AND_PRODUCT_CARDINALITY_MIXED');
    }
    try {
        $db->query('INSERT INTO mm77_product_attribute_options
            (product_id,attribute_id,value_id) VALUES (101,10,103)');
        throw new RuntimeException('MM77_DOUBLE_PRESET_ALLOWED');
    } catch (mysqli_sql_exception $error) {
        if ($error->getCode()!==1062) throw $error;
    }
    $db->query("INSERT INTO mm77_product_attribute_free_values
        (product_id,attribute_id,value_text,sort_order) VALUES (101,11,'5',0)");
    try {
        $db->query("INSERT INTO mm77_product_attribute_free_values
            (product_id,attribute_id,value_text,sort_order) VALUES (101,11,'10',1)");
        throw new RuntimeException('MM77_DOUBLE_FREE_ALLOWED');
    } catch (mysqli_sql_exception $error) {
        if ($error->getCode()!==1062) throw $error;
    }
    echo "MM77_ONE_VALUE_SQL_MYSQL84_PASS: archive, single value, filter OR, unique keys\n";
} finally {
    foreach ($tables as $name) $db->query('DROP TABLE IF EXISTS '.$prefix.$name);
}
