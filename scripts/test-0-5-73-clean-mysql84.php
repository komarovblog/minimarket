<?php
/**
 * Execute the exact clean-catalog SQL produced by PowerShell on disposable
 * MySQL 8.4 CI database only. Never use on an installed Joomla database.
 */
declare(strict_types=1);
if (getenv('MINIMARKET_CANONICAL_CI') !== '1'
    || getenv('MM_CI_DB_DATABASE') !== 'minimarket_ci_canonical73') {
    fwrite(STDERR, "MM73_CLEAN_CI_REFUSED\n");
    exit(2);
}
$file = $argv[1] ?? '';
if (!is_file($file) || !str_ends_with($file, '.sql')) {
    throw new RuntimeException('MM73_CLEAN_SQL_FILE_MISSING');
}
$sql = file_get_contents($file);
if (!is_string($sql)
    || !str_contains($sql, 'USE `minimarket_ci_canonical73`;')
    || !str_contains($sql, 'nnop3_minimarket_categories')
    || str_contains($sql, '#__minimarket_')
    || str_contains($sql, 'GREATEST(@@SESSION.group_concat_max_len')) {
    throw new RuntimeException('MM73_CLEAN_SQL_WRONG_TARGET');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(
    getenv('MM_CI_DB_HOST') ?: '127.0.0.1',
    getenv('MM_CI_DB_USER') ?: '',
    getenv('MM_CI_DB_PASSWORD') ?: '',
    'minimarket_ci_canonical73',
    (int) (getenv('MM_CI_DB_PORT') ?: 3306)
);
$db->set_charset('utf8mb4');
$db->query('CREATE TABLE IF NOT EXISTS nnop3_extensions (
    extension_id INT NOT NULL PRIMARY KEY,
    type VARCHAR(32) NOT NULL,
    element VARCHAR(128) NOT NULL
) ENGINE=InnoDB');
$db->query('CREATE TABLE IF NOT EXISTS nnop3_schemas (
    extension_id INT NOT NULL PRIMARY KEY,
    version_id VARCHAR(32) NOT NULL
) ENGINE=InnoDB');
$db->query("INSERT INTO nnop3_extensions (extension_id,type,element)
    VALUES (930073,'component','com_minimarket')
    ON DUPLICATE KEY UPDATE type=VALUES(type),element=VALUES(element)");
$db->query("INSERT INTO nnop3_schemas (extension_id,version_id)
    VALUES (930073,'0.5.69')
    ON DUPLICATE KEY UPDATE version_id='0.5.69'");

try {
    if (!$db->multi_query($sql)) {
        throw new RuntimeException('MM73_CLEAN_MULTISTATEMENT_FAILED');
    }
    do {
        $result = $db->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
        if (!$db->more_results()) {
            break;
        }
    } while ($db->next_result());
} catch (Throwable $e) {
    throw new RuntimeException('MM73_CLEAN_SQL_IMPORT_ERROR: '.$e->getMessage(),0,$e);
}

foreach ([
    'attributes' => 12,
    'attribute_values' => 52,
    'product_attribute_options' => 496,
    'product_attribute_scalars' => 462,
] as $name => $expected) {
    $got = (int) $db->query('SELECT COUNT(*) FROM nnop3_minimarket_'.$name)
        ->fetch_row()[0];
    if ($got !== $expected) {
        throw new RuntimeException("MM73_CLEAN_ROW_COUNT: $name $got != $expected");
    }
}
$version = $db->query("SELECT version_id FROM nnop3_schemas
    WHERE extension_id=930073")->fetch_row()[0] ?? '';
if ($version !== '0.5.73') {
    throw new RuntimeException('MM73_CLEAN_JOOMLA_SCHEMA_MARKER_INCORRECT: '.$version);
}
$legacyColumns = $db->query(
    "SHOW COLUMNS FROM nnop3_minimarket_attributes LIKE 'input_mode'"
)->num_rows;
if ($legacyColumns) {
    throw new RuntimeException('MM73_CLEAN_LEFT_LEGACY_ATTRIBUTE_COLUMN');
}
if ((int)$db->query('SELECT @@SESSION.foreign_key_checks')->fetch_row()[0] !== 1) {
    throw new RuntimeException('MM73_CLEAN_FOREIGN_KEY_CHECKS_NOT_RESTORED');
}
echo "MM73_CLEAN_SQL_MYSQL84_PASS: 12 attributes, 52 values, 496 options, 462 scalars\n";

// Additive color-switch demo: execute exactly the same optional SQL supplied
// to the user (only its USE target differs), then verify photos and SKU links.
$demoFile = dirname(__DIR__) . '/sample-data/minimarket_demo_color_switch_0_5_73.sql';
$demoSource = file_get_contents($demoFile);
if (!is_string($demoSource)) {
    throw new RuntimeException('MM73_COLOR_DEMO_SOURCE_MISSING');
}
$demoSql = str_replace('USE `minimarket`;', 'USE `minimarket_ci_canonical73`;', $demoSource);
for ($pass = 1; $pass <= 2; $pass++) {
    try {
        $db->multi_query($demoSql);
        do {
            $result = $db->store_result();
            if ($result instanceof mysqli_result) $result->free();
            if (!$db->more_results()) break;
        } while ($db->next_result());
    } catch (Throwable $e) {
        throw new RuntimeException('MM73_COLOR_DEMO_IMPORT_FAIL_PASS_'.$pass.': '.$e->getMessage(), 0, $e);
    }
}
foreach ([
    "SELECT COUNT(*) FROM nnop3_minimarket_products WHERE id IN (1201,1202)" => 2,
    "SELECT COUNT(*) FROM nnop3_minimarket_product_variants WHERE product_id IN (1201,1202)" => 12,
    "SELECT COUNT(*) FROM nnop3_minimarket_product_images WHERE product_id IN (1201,1202)" => 10,
    "SELECT COUNT(*) FROM nnop3_minimarket_product_image_variants WHERE variant_id BETWEEN 7401 AND 7414" => 24,
    "SELECT COUNT(*) FROM nnop3_minimarket_variant_value_assignments WHERE variant_id BETWEEN 7401 AND 7414" => 24,
    "SELECT COUNT(*) FROM nnop3_minimarket_product_variants
       WHERE product_id IN (1201,1202) AND signature=SHA2(selection_json,256)" => 12,
    "SELECT COUNT(DISTINCT price) FROM nnop3_minimarket_product_variants WHERE product_id=1201" => 1,
    "SELECT COUNT(*) FROM nnop3_minimarket_product_image_variants piv
      JOIN nnop3_minimarket_product_images pi ON pi.id=piv.image_id
      JOIN nnop3_minimarket_product_variants v ON v.id=piv.variant_id
      WHERE piv.is_cover=1 AND v.product_id IN (1201,1202)" => 12,
] as $query => $expected) {
    $actual = (int)$db->query($query)->fetch_row()[0];
    if ($actual !== $expected) {
        throw new RuntimeException('MM73_COLOR_DEMO_EXPECTED_'.$expected.'_GOT_'.$actual.' SQL: '.$query);
    }
}
echo "MM73_COLOR_DEMO_MYSQL84_PASS: 2 products, 12 SKUs, 10 gallery images, 24 SKU photo links; repeat import OK\n";

