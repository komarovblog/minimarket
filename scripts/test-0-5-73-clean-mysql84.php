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
