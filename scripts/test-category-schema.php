<?php
/** Disposable CI-only MySQL test. Never run against a real Joomla DB. */
declare(strict_types=1);
$name = getenv('MM_CI_DB_DATABASE') ?: '';
if (getenv('MINIMARKET_CATEGORY_CI') !== '1'
    || !preg_match('/^minimarket_ci_[a-z0-9_]+$/D', $name)) {
    fwrite(STDERR, "CATEGORY_SCHEMA_REFUSED\n");
    exit(2);
}
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        getenv('MM_CI_DB_HOST') ?: '127.0.0.1',
        (int) (getenv('MM_CI_DB_PORT') ?: '3306'), $name),
    getenv('MM_CI_DB_USER') ?: '', getenv('MM_CI_DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
function runSql(PDO $pdo, string $filename, string $prefix): void
{
    $sql = file_get_contents($filename);
    if ($sql === false) { throw new RuntimeException('SQL_FILE_MISSING'); }
    $sql = str_replace('#__', $prefix, preg_replace('/^\s*--[^\n]*$/m', '', $sql));
    foreach (explode(';', $sql) as $part) {
        if (trim($part) !== '') { $pdo->exec(trim($part)); }
    }
}
function row(PDO $pdo, string $table): array
{
    return (array) $pdo->query('SELECT * FROM ' . $table . ' WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
}
$checks = 0;
function verify(bool $pass, string $label): void
{
    global $checks; ++$checks;
    if (!$pass) { throw new RuntimeException('CATEGORY_SCHEMA_FAIL: ' . $label); }
}
$dir = __DIR__ . '/../src/com_minimarket/admin/sql/';
runSql($pdo, $dir . 'updates/mysql/0.2.0.sql', 'catci_');
$pdo->exec("INSERT INTO catci_minimarket_categories
    (id, parent_id, title, alias, description, state)
    VALUES (1, 0, 'Original', 'original', 'Original text', 1)");
$pdo->exec("INSERT INTO catci_minimarket_products
    (id, category_id, title, alias, sku, price, stock, state)
    VALUES (1, 1, 'Test product', 'test-product', 'TEST-001', 1990.50, 3, 1)");
$beforeCategory = row($pdo, 'catci_minimarket_categories');
$beforeProduct = row($pdo, 'catci_minimarket_products');
runSql($pdo, $dir . 'updates/mysql/0.4.1.sql', 'catci_');
runSql($pdo, $dir . 'updates/mysql/0.4.2.sql', 'catci_');
$after = row($pdo, 'catci_minimarket_categories');
foreach ($beforeCategory as $col => $value) {
    verify(array_key_exists($col, $after) && $value === $after[$col],
        'existing category column ' . $col);
}
verify($beforeProduct === row($pdo, 'catci_minimarket_products'), 'product unchanged on upgrade');
foreach (['introtext', 'sort_order', 'image', 'image_alt', 'show_children', 'show_products', 'meta_title', 'meta_description', 'meta_robots',
    'og_title', 'og_description', 'og_image', 'og_image_alt'] as $col) {
    verify(array_key_exists($col, $after), 'new column ' . $col);
}
verify((int) $after['sort_order'] === 0, 'order default zero');
verify((int) $after['show_children'] === -1, 'inherit subcategories');
verify((int) $after['show_products'] === -1, 'inherit products');
$pdo->exec("UPDATE catci_minimarket_categories
    SET introtext='Intro', sort_order=4, image='images/cover.jpg', image_alt='Cover',
    show_children=1, show_products=0 WHERE id=1");
$pdo->exec("UPDATE catci_minimarket_categories SET meta_title='SEO title', meta_description='SEO description', meta_robots='noindex,follow', og_title='OG title' WHERE id=1");
verify(row($pdo, 'catci_minimarket_categories')['meta_title'] === 'SEO title', 'SEO field persisted');
verify(row($pdo, 'catci_minimarket_categories')['meta_robots'] === 'noindex,follow', 'Robots field persisted');
verify(row($pdo, 'catci_minimarket_categories')['introtext'] === 'Intro',
    'updated category description saved');
verify($beforeProduct === row($pdo, 'catci_minimarket_products'), 'product unchanged on edit');

// MySQL FK constraint identifiers are database-wide: clean only CI test tables.
$pdo->exec('DROP TABLE catci_minimarket_products');
$pdo->exec('DROP TABLE catci_minimarket_categories');
runSql($pdo, $dir . 'install.mysql.sql', 'freshcatci_');
$columns = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_categories')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['introtext', 'sort_order', 'image', 'image_alt', 'show_children', 'show_products', 'meta_title', 'meta_description', 'meta_robots',
    'og_title', 'og_description', 'og_image', 'og_image_alt'] as $col) {
    verify(in_array($col, $columns, true), 'fresh-install column ' . $col);
}
$pdo->exec("INSERT INTO freshcatci_minimarket_categories
    (id,title,alias,state) VALUES (1,'New category','new-category',1)");
verify((int) row($pdo, 'freshcatci_minimarket_categories')['sort_order'] === 0,
    'fresh category defaults');
echo 'CATEGORY_SCHEMA_OK: ' . $checks . " cases\n";
