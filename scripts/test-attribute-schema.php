<?php
/**
 * CI-only integration test for schema 0.4.0.
 * Never run against an installed Joomla database.
 */
declare(strict_types=1);

if (getenv('MINIMARKET_SCHEMA_TEST') !== '1') {
    fwrite(STDERR, "ATTRIBUTES_SCHEMA_SKIP: CI opt-in is required\n");
    exit(2);
}

$database = getenv('MM_CI_DB_DATABASE') ?: '';

if (!preg_match('/^minimarket_ci_[a-z0-9_]+$/D', $database)) {
    fwrite(STDERR, "ATTRIBUTES_SCHEMA_REFUSED: unsafe database name\n");
    exit(2);
}

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        getenv('MM_CI_DB_HOST') ?: '127.0.0.1',
        (int) (getenv('MM_CI_DB_PORT') ?: '3306'),
        $database
    ),
    getenv('MM_CI_DB_USER') ?: '',
    getenv('MM_CI_DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function tbl(string $name): string
{
    return chr(96) . $name . chr(96);
}

function executeSqlFile(PDO $pdo, string $file, string $prefix): void
{
    $sql = file_get_contents($file);

    if ($sql === false) {
        throw new RuntimeException('MISSING_SQL_FILE');
    }

    $sql = preg_replace('/^\s*--[^\n]*$/m', '', $sql);
    $sql = str_replace('#__', $prefix, $sql);

    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);

        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

$checks = 0;

function requireTrue(bool $condition, string $label): void
{
    global $checks;
    ++$checks;

    if (!$condition) {
        throw new RuntimeException('ATTRIBUTES_SCHEMA_FAIL: ' . $label);
    }
}

function one(PDO $pdo, string $sql): array
{
    return (array) $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function requireConstraint(PDO $pdo, string $sql, string $label): void
{
    try {
        $pdo->exec($sql);
    } catch (PDOException $exception) {
        requireTrue($exception->getCode() === '23000', $label);
        return;
    }

    requireTrue(false, $label);
}

$dir = __DIR__ . '/../src/com_minimarket/admin/sql/';
$prefix = 'upgrade_mmci_';
$products = tbl($prefix . 'minimarket_products');
$categories = tbl($prefix . 'minimarket_categories');
$attributes = tbl($prefix . 'minimarket_attributes');
$values = tbl($prefix . 'minimarket_attribute_values');

executeSqlFile($pdo, $dir . 'updates/mysql/0.2.0.sql', $prefix);
$pdo->exec("INSERT INTO " . $categories
    . " (id,parent_id,title,alias,state) VALUES (1,0,'Test category','test-category',1)");
$pdo->exec("INSERT INTO " . $products
    . " (id,category_id,title,alias,sku,price,stock,state)"
    . " VALUES (1,1,'Test product','test-product','TEST-001',1990.50,3,1)");

$before = one($pdo, "SELECT * FROM " . $products . " WHERE id = 1");
executeSqlFile($pdo, $dir . 'updates/mysql/0.4.0.sql', $prefix);
$after = one($pdo, "SELECT * FROM " . $products . " WHERE id = 1");
requireTrue($before === $after, 'old product unchanged on upgrade');

$count = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "
    . $pdo->quote($database)
    . " AND table_name LIKE 'upgrade_mmci_minimarket_attribute%'")->fetchColumn();
requireTrue($count === 2, 'two new attribute tables exist');

$pdo->exec("INSERT INTO " . $attributes . " (id,title,code,input_type,is_variant,state)"
    . " VALUES (1,'Color','color','list',1,1)");
$pdo->exec("INSERT INTO " . $values . " (id,attribute_id,title,code,state)"
    . " VALUES (1,1,'Red','red',1),(2,1,'Yellow','yellow',1)");

requireTrue((int) $pdo->query("SELECT COUNT(*) FROM " . $values)->fetchColumn() === 2,
    'dictionary values are stored');
requireConstraint($pdo, "INSERT INTO " . $values
    . " (attribute_id,title,code) VALUES (1,'Duplicate','red')",
    'unique value code inside attribute');
requireConstraint($pdo, "INSERT INTO " . $values
    . " (attribute_id,title,code) VALUES (999,'Orphan','orphan')",
    'foreign key rejects orphan value');
requireConstraint($pdo, "INSERT INTO " . $attributes
    . " (title,code) VALUES ('Duplicate','color')",
    'unique attribute code');

executeSqlFile($pdo, $dir . 'updates/mysql/0.4.0.sql', $prefix);
requireTrue((int) $pdo->query("SELECT COUNT(*) FROM " . $attributes)->fetchColumn() === 1,
    'repeatable migration is idempotent');
requireTrue(one($pdo, "SELECT * FROM " . $products . " WHERE id = 1") === $before,
    'repeatable migration preserves old product');

$fresh = 'fresh_mmci_';
executeSqlFile($pdo, $dir . 'install.mysql.sql', $fresh);
$freshTables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "
    . $pdo->quote($database)
    . " AND table_name LIKE 'fresh_mmci_minimarket_%'")->fetchColumn();
requireTrue($freshTables === 4, 'fresh install creates categories, products and dictionaries');

executeSqlFile($pdo, $dir . 'updates/mysql/0.4.0.sql', $fresh);
$freshTablesAgain = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "
    . $pdo->quote($database)
    . " AND table_name LIKE 'fresh_mmci_minimarket_%'")->fetchColumn();
requireTrue($freshTablesAgain === 4, 'updating fresh install preserves schema');

echo 'ATTRIBUTES_SCHEMA_OK: ' . $checks . ' cases' . PHP_EOL;
