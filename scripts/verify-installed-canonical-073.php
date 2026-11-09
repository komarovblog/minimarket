<?php
/**
 * Read-only guard for MiniMarket 0.5.73 canonical Joomla database.
 * This does NOT alter tables, schema versions, or Joomla configuration.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || count($argv) !== 2) {
    fwrite(STDERR, "MM73_SQL_HISTORY_USAGE\n");
    exit(2);
}
$configPath = realpath($argv[1]);
if ($configPath === false || basename($configPath) !== 'configuration.php') {
    fwrite(STDERR, "MM73_SQL_HISTORY_INVALID_JOOMLA_CONFIG\n");
    exit(2);
}
require_once $configPath;
if (!class_exists('JConfig')) {
    fwrite(STDERR, "MM73_SQL_HISTORY_JCONFIG_MISSING\n");
    exit(2);
}
$config = new JConfig();
$prefix = (string) ($config->dbprefix ?? '');
$database = (string) ($config->db ?? '');
$host = (string) ($config->host ?? '');
if (!preg_match('/^[a-zA-Z0-9_]{2,64}$/D', $prefix)
    || !preg_match('/^[a-zA-Z0-9_]{1,64}$/D', $database)
    || $host === '') {
    fwrite(STDERR, "MM73_SQL_HISTORY_INVALID_DB_CONFIG\n");
    exit(2);
}
try {
    $dsn = 'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4';
    $db = new PDO($dsn, (string)$config->user, (string)$config->password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 4,
    ]);
    $sql = str_replace('__P__', $prefix, "
        SELECT s.version_id
        FROM `__P__schemas` AS s
        INNER JOIN `__P__extensions` AS e ON e.extension_id=s.extension_id
        WHERE e.type='component' AND e.element='com_minimarket' LIMIT 1");
    $check = $db->query($sql)->fetchColumn();
    if ($check !== '0.5.73') {
        throw new RuntimeException('Joomla schema version is not exactly 0.5.73');
    }
    $tableStmt = $db->prepare(
        'SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=? AND TABLE_NAME IN (?,?,?)'
    );
    $tableStmt->execute([$database,
        $prefix.'minimarket_attributes',
        $prefix.'minimarket_product_attribute_options',
        $prefix.'minimarket_product_attribute_multi_values']);
    $tables = $tableStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($prefix.'minimarket_attributes', $tables, true)
        || !in_array($prefix.'minimarket_product_attribute_options', $tables, true)
        || in_array($prefix.'minimarket_product_attribute_multi_values', $tables, true)) {
        throw new RuntimeException('Canonical attribute tables do not match 0.5.73');
    }
    $columnStmt = $db->prepare(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=? AND TABLE_NAME=?'
    );
    $columnStmt->execute([$database, $prefix.'minimarket_attributes']);
    $columns = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach (['input_type', 'value_source'] as $required) {
        if (!in_array($required, $columns, true)) {
            throw new RuntimeException('Canonical column missing: '.$required);
        }
    }
    foreach (['input_mode', 'text_options', 'number_options'] as $legacy) {
        if (in_array($legacy, $columns, true)) {
            throw new RuntimeException('Legacy column unexpectedly present: '.$legacy);
        }
    }
    fwrite(STDOUT, "MM73_CANONICAL_DATABASE_VERIFIED prefix={$prefix} database={$database}\n");
} catch (Throwable $error) {
    // Never print configuration, database passwords or connection strings.
    fwrite(STDERR, "MM73_SQL_HISTORY_DATABASE_GUARD_FAILED: ".
        $error->getMessage()."\n");
    exit(1);
}
