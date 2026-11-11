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

// Product 0.5.0 upgrade: existing trading fields and ID must not change.
runSql($pdo, $dir . 'updates/mysql/0.5.0.sql', 'catci_');
$afterProduct = row($pdo, 'catci_minimarket_products');
foreach ($beforeProduct as $col => $value) {
    verify(array_key_exists($col, $afterProduct) && $value === $afterProduct[$col],
        '0.5.0 preserved product ' . $col);
}
$productEditorColumns = [
    'introtext', 'description', 'sort_order', 'image', 'image_alt',
    'meta_title', 'meta_description', 'meta_robots',
    'og_title', 'og_description', 'og_image', 'og_image_alt',
];
foreach ($productEditorColumns as $col) {
    verify(array_key_exists($col, $afterProduct), '0.5.0 product column ' . $col);
}
$pdo->exec("UPDATE catci_minimarket_products SET introtext='<p>Short</p>',
    description='<p>Full</p>', image='images/sample.jpg', image_alt='Cover',
    meta_title='Title', meta_description='Description', meta_robots='noindex,follow',
    og_title='Shared title', og_image='images/social.jpg', sort_order=4 WHERE id=1");
verify(row($pdo, 'catci_minimarket_products')['meta_title'] === 'Title',
    '0.5.0 SEO persists');
verify(row($pdo, 'catci_minimarket_products')['sku'] === 'TEST-001',
    '0.5.0 legacy SKU unchanged by editor update');
verify((string) row($pdo, 'catci_minimarket_products')['price'] === (string) $beforeProduct['price'],
    '0.5.0 legacy price unchanged by editor update');

// 0.5.2 metadata cleanup: only remove Joomla Media's adapter fragment.
$rich = 'images/powered_by.png#joomlaImage://local-images/powered_by.png?width=294&height=44';
$pdo->exec("UPDATE catci_minimarket_categories SET image=" . $pdo->quote($rich) .
    ", og_image=" . $pdo->quote($rich) . " WHERE id=1");
$pdo->exec("UPDATE catci_minimarket_products SET image=" . $pdo->quote($rich) .
    ", og_image=" . $pdo->quote($rich) . " WHERE id=1");
runSql($pdo, $dir . 'updates/mysql/0.5.2.sql', 'catci_');
foreach (['catci_minimarket_categories', 'catci_minimarket_products'] as $tableName) {
    $clean = row($pdo, $tableName);
    verify($clean['image'] === 'images/powered_by.png', $tableName . ' cleans image');
    verify($clean['og_image'] === 'images/powered_by.png', $tableName . ' cleans OG image');
}
$cleanProduct = row($pdo, 'catci_minimarket_products');
foreach (['id', 'sku', 'price', 'stock', 'category_id', 'state'] as $column) {
    verify($cleanProduct[$column] === $afterProduct[$column],
        '0.5.2 preserves product ' . $column);
}
$cleanCategory = row($pdo, 'catci_minimarket_categories');
verify($cleanCategory['parent_id'] === $after['parent_id']
    && $cleanCategory['title'] === $after['title'], 'category hierarchy/title unchanged');
runSql($pdo, $dir . 'updates/mysql/0.5.2.sql', 'catci_');
verify(row($pdo, 'catci_minimarket_products')['image'] === 'images/powered_by.png',
    'clean media migration is idempotent');

// New 0.5.3 media relation; old item must not be modified by upgrade.
$preGalleryProduct = row($pdo, 'catci_minimarket_products');
runSql($pdo, $dir . 'updates/mysql/0.5.3.sql', 'catci_');
verify($preGalleryProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.3 schema upgrade preserves all existing product columns');
$pdo->exec("INSERT INTO catci_minimarket_product_images
    (product_id,path,alt,sort_order) VALUES
    (1,'images/side.jpg','Side',0),(1,'images/back.jpg','Back',1)");
$gallery = $pdo->query("SELECT path,alt FROM catci_minimarket_product_images
    WHERE product_id=1 ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
verify(count($gallery) === 2 && $gallery[0]['path'] === 'images/side.jpg',
    '0.5.3 gallery relations maintain order');
try {
    $pdo->exec("INSERT INTO catci_minimarket_product_images
        (product_id,path) VALUES (1,'images/side.jpg')");
    verify(false, 'duplicate gallery path rejected');
} catch (PDOException $e) {
    verify(true, 'duplicate gallery path rejected');
}
$pdo->exec("DELETE FROM catci_minimarket_product_images WHERE path='images/side.jpg'");
verify((int) $pdo->query("SELECT COUNT(*) FROM catci_minimarket_product_images")->fetchColumn() === 1,
    'removing gallery link does not delete product');
verify(row($pdo, 'catci_minimarket_products')['sku'] === 'TEST-001',
    '0.5.3 keeps SKU, stock and legacy item');
// Preserve the gallery table so 0.5.39 can add SKU visibility.

// 0.5.4: characteristics dictionaries are additive; existing product/gallery untouched.
$preAttributesProduct = row($pdo, 'catci_minimarket_products');
runSql($pdo, $dir . 'updates/mysql/0.5.4.sql', 'catci_');
verify($preAttributesProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.4 preserves existing product SKU/price/stock and fields');
$pdo->exec("INSERT INTO catci_minimarket_attributes
    (id,title,code,input_type,is_variant,is_filterable,state)
    VALUES (1,'Color','color','list',1,1,1), (2,'Size','size','list',1,1,1)");
$pdo->exec("INSERT INTO catci_minimarket_attribute_values
    (attribute_id,title,code,state)
    VALUES (1,'Red','red',1),(1,'Yellow','yellow',1),(2,'S','s',1),(2,'M','m',1)");
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_attribute_values')->fetchColumn() === 4,
    '0.5.4 creates reusable color and size dictionaries');
try {
    $pdo->exec("INSERT INTO catci_minimarket_attribute_values
        (attribute_id,title,code) VALUES (1,'Duplicate Red','red')");
    verify(false, 'duplicate value in same attribute rejected');
} catch (PDOException $e) {
    verify(true, 'duplicate value in same attribute rejected');
}
try {
    $pdo->exec("INSERT INTO catci_minimarket_attribute_values
        (attribute_id,title,code) VALUES (999,'Orphan','missing')");
    verify(false, 'orphan value rejected');
} catch (PDOException $e) {
    verify(true, 'orphan value rejected');
}
try {
    $pdo->exec("INSERT INTO catci_minimarket_attributes
        (title,code) VALUES ('Duplicate','color')");
    verify(false, 'duplicate attribute code rejected');
} catch (PDOException $e) {
    verify(true, 'duplicate attribute code rejected');
}
runSql($pdo, $dir . 'updates/mysql/0.5.4.sql', 'catci_');
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_attributes')->fetchColumn() === 2,
    '0.5.4 migration can be rerun without duplicate dictionaries');
verify($preAttributesProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.4 remains safe on repeat');
// 0.5.5: category assignments, inheritance rules and legacy data safety.
$beforeCatAssignProduct = row($pdo, 'catci_minimarket_products');
$beforeCatAssignCategory = row($pdo, 'catci_minimarket_categories');
runSql($pdo, $dir . 'updates/mysql/0.5.5.sql', 'catci_');
verify($beforeCatAssignProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.5 keeps legacy product SKU/price/stock');
verify($beforeCatAssignCategory === row($pdo, 'catci_minimarket_categories'),
    '0.5.5 keeps category fields and parent');
$pdo->exec("INSERT INTO catci_minimarket_category_attributes (category_id,attribute_id,enabled)
    VALUES (1,1,1),(1,2,0)");
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_category_attributes')->fetchColumn() === 2,
    'category can include and explicitly exclude attributes');
try {
    $pdo->exec("INSERT INTO catci_minimarket_category_attributes
        (category_id,attribute_id,enabled) VALUES (1,1,0)");
    verify(false, 'duplicate assignment rejected by DB');
} catch (PDOException $error) {
    verify(true, 'duplicate assignment rejected by DB');
}
try {
    $pdo->exec("INSERT INTO catci_minimarket_category_attributes
        (category_id,attribute_id,enabled) VALUES (1,999,1)");
    verify(false, 'invalid attribute FK rejected');
} catch (PDOException $error) {
    verify(true, 'invalid attribute FK rejected');
}
runSql($pdo, $dir . 'updates/mysql/0.5.5.sql', 'catci_');
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_category_attributes')->fetchColumn() === 2,
    '0.5.5 migration repeat does not lose assignments');
verify($beforeCatAssignProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.5 rerun preserves trading data');
// 0.5.7: typed product attributes are relations, not product trading columns.
$beforeAttributeProduct = row($pdo, 'catci_minimarket_products');
runSql($pdo, $dir . 'updates/mysql/0.5.7.sql', 'catci_');
verify($beforeAttributeProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.7 keeps all legacy product SKU/price/stock/media');
$pdo->exec("INSERT INTO catci_minimarket_attributes
    (id,title,code,input_type,state) VALUES (3,'Width','width','number',1)");
$pdo->exec("INSERT INTO catci_minimarket_product_attribute_scalars
    (product_id,attribute_id,value_number) VALUES (1,3,12.5000)");
$pdo->exec("INSERT INTO catci_minimarket_product_attribute_options
    (product_id,attribute_id,value_id) VALUES (1,1,1),(1,1,2)");
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_attribute_options')->fetchColumn() === 2,
    '0.5.7 allows two color selections for one product');
verify((string) $pdo->query('SELECT value_number FROM catci_minimarket_product_attribute_scalars')->fetchColumn() === '12.5000',
    '0.5.7 preserves typed decimal');
try {
    $pdo->exec("INSERT INTO catci_minimarket_product_attribute_options
        (product_id,attribute_id,value_id) VALUES (1,1,1)");
    verify(false,'duplicate option blocked by primary key');
} catch (PDOException $e) {
    verify(true, 'duplicate option blocked by primary key');
}
try {
    $pdo->exec("INSERT INTO catci_minimarket_product_attribute_options
        (product_id,attribute_id,value_id) VALUES (1,1,999)");
    verify(false,'unknown value blocked by FK');
} catch (PDOException $e) {
    verify(true, 'unknown value blocked by FK');
}
runSql($pdo, $dir . 'updates/mysql/0.5.7.sql', 'catci_');
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_attribute_options')->fetchColumn() === 2,
    'rerun 0.5.7 does not remove selected values');
verify($beforeAttributeProduct === row($pdo, 'catci_minimarket_products'),
    'rerun 0.5.7 preserves trading columns');
// 0.5.11: separate list single/multiple from is_variant without losing data.
$beforeMultipleProduct = row($pdo, 'catci_minimarket_products');
runSql($pdo, $dir . 'updates/mysql/0.5.11.sql', 'catci_');
verify($beforeMultipleProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.11 preserves all product trading fields and URL');
verify((int) $pdo->query('SELECT is_multiple FROM catci_minimarket_attributes WHERE id=1')->fetchColumn() === 1,
    '0.5.11 preserves legacy multi color from is_variant');
verify((int) $pdo->query('SELECT is_multiple FROM catci_minimarket_attributes WHERE id=2')->fetchColumn() === 1,
    '0.5.11 preserves legacy multi size from is_variant');
verify((int) $pdo->query('SELECT is_multiple FROM catci_minimarket_attributes WHERE id=3')->fetchColumn() === 0,
    '0.5.11 leaves non-list cardinality single');
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_attribute_options')->fetchColumn() === 2,
    '0.5.11 keeps all selected dictionary value IDs');
$pdo->exec('UPDATE catci_minimarket_attributes SET is_variant=0 WHERE id=1');
verify((int) $pdo->query('SELECT is_multiple FROM catci_minimarket_attributes WHERE id=1')->fetchColumn() === 1,
    '0.5.11 allows normal multi-select without SKU flag');
// 0.5.12: metadata changes and scalar TEXT widening must not rewrite products or values.
$beforePresentationProduct = row($pdo, 'catci_minimarket_products');
$beforePresentationSelections = (int) $pdo->query(
    'SELECT COUNT(*) FROM catci_minimarket_product_attribute_options'
)->fetchColumn();
runSql($pdo, $dir . 'updates/mysql/0.5.12.sql', 'catci_');
verify($beforePresentationProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.12 preserves product SKU, price, stock, URL and category');
verify($beforePresentationSelections === (int) $pdo->query(
    'SELECT COUNT(*) FROM catci_minimarket_product_attribute_options'
)->fetchColumn(), '0.5.12 preserves selected dictionary option IDs');
verify((int) $pdo->query(
    'SELECT filter_multiple FROM catci_minimarket_attributes WHERE id=1'
)->fetchColumn() === 1, '0.5.12 preserves existing list filter intent as multi-checkboxes');
$pdo->exec("UPDATE catci_minimarket_attributes
    SET input_type='number',input_mode='presets',number_options='1000,1500,2000',
        number_unit='Вт',number_min=1000,number_max=2000,number_step=500,
        filter_mode='slider',filter_multiple=0 WHERE id=3");
verify((string) $pdo->query(
    "SELECT input_mode FROM catci_minimarket_attributes WHERE id=3"
)->fetchColumn() === 'presets', '0.5.12 stores numeric input independently');
verify((string) $pdo->query(
    "SELECT filter_mode FROM catci_minimarket_attributes WHERE id=3"
)->fetchColumn() === 'slider', '0.5.12 stores filter presentation independently');
verify($beforePresentationProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.12 attribute metadata update never changes commercial values');
// 0.5.14: no impact on existing catalog or option selections.
$oldProduct = row($pdo, 'catci_minimarket_products');
$oldOptionsCount = (int) $pdo->query(
    'SELECT COUNT(*) FROM catci_minimarket_product_attribute_options'
)->fetchColumn();
runSql($pdo, $dir . 'updates/mysql/0.5.14.sql', 'catci_');
verify($oldProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.14 preserves product SKU, price, stock, image, URL and ID');
verify($oldOptionsCount === (int) $pdo->query(
    'SELECT COUNT(*) FROM catci_minimarket_product_attribute_options'
)->fetchColumn(), '0.5.14 preserves selected color option IDs');
$pdo->exec("UPDATE catci_minimarket_attribute_values
    SET color_hex='#FF0000' WHERE attribute_id=1");
verify((string) $pdo->query(
    'SELECT color_hex FROM catci_minimarket_attribute_values WHERE attribute_id=1 LIMIT 1'
)->fetchColumn() === '#FF0000', '0.5.14 swatches use original dictionary value IDs');
$pdo->exec("INSERT INTO catci_minimarket_product_videos
    (product_id, title, source, sort_order)
    VALUES (1,'Demo','https://www.youtube.com/watch?v=dQw4w9WgXcQ',0)");
verify((int) $pdo->query(
    'SELECT COUNT(*) FROM catci_minimarket_product_videos WHERE product_id=1'
)->fetchColumn() === 1,'0.5.14 videos linked without editing product fields');
// 0.5.15 and 0.5.16: additive attribute media and mandatory category rules.
$beforeMandatoryProduct = row($pdo, 'catci_minimarket_products');
$previousLink = $pdo->query(
    'SELECT category_id,attribute_id,enabled FROM catci_minimarket_category_attributes ORDER BY attribute_id'
)->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo, $dir . 'updates/mysql/0.5.15.sql', 'catci_');
runSql($pdo, $dir . 'updates/mysql/0.5.16.sql', 'catci_');
$links = $pdo->query(
    'SELECT category_id,attribute_id,enabled,required FROM catci_minimarket_category_attributes ORDER BY attribute_id'
)->fetchAll(PDO::FETCH_ASSOC);
verify(count($links) === count($previousLink), 'required migration preserves every category assignment');
foreach ($previousLink as $i => $row) {
    verify($row['category_id'] === $links[$i]['category_id']
        && $row['attribute_id'] === $links[$i]['attribute_id']
        && $row['enabled'] === $links[$i]['enabled']
        && (int) $links[$i]['required'] === 0,
        'existing category relation remains unchanged and optional');
}
$pdo->exec("UPDATE catci_minimarket_category_attributes SET required=1
    WHERE category_id=1 AND attribute_id=1");
verify((int) $pdo->query("SELECT required FROM catci_minimarket_category_attributes
    WHERE category_id=1 AND attribute_id=1")->fetchColumn() === 1,
    'required flag can be saved');
verify($beforeMandatoryProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.16 does not modify product data or URL');
$preVariantProduct = row($pdo,'catci_minimarket_products');
runSql($pdo, $dir . 'updates/mysql/0.5.17.sql', 'catci_');
$pdo->exec("INSERT INTO catci_minimarket_product_variants
    (product_id,sku,selection_json,signature,price,stock,image,sort_order)
    VALUES (1,'RED-S','{\"1\":\"d:1\"}','".str_repeat('a',64)."',1590.00,5,'images/red.webp',0),
           (1,'RED-M','{\"1\":\"d:1\"}','".str_repeat('b',64)."',1790.00,0,'images/red-m.webp',1)");
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_variants')->fetchColumn() === 2,
    'multiple SKU rows under same product');
$pdo->exec("INSERT INTO catci_minimarket_product_variant_images (variant_id,path,alt,sort_order)
    VALUES (1,'images/red-back.webp','Back',0)");
verify((int) $pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_variant_images')->fetchColumn() === 1,
    'variant gallery linked');
try {
    $pdo->exec("INSERT INTO catci_minimarket_product_variants
        (product_id,sku,selection_json,signature,price,stock)
        VALUES (1,'RED-S-DUP','{}','".str_repeat('a',64)."',1500,1)");
    verify(false,'duplicate SKU combination prevented');
} catch (PDOException $e) {
    verify(true,'duplicate SKU combination prevented');
}
verify($preVariantProduct === row($pdo, 'catci_minimarket_products'),
    '0.5.17 preserves product ID, URL, price, SKU, stock, and photos');
// 0.5.20 — additive normalized local options and source/external identities.
// Never rewrite existing product, variant IDs, SKU, price, stock, URLs or gallery.
$beforeV2Product = row($pdo, 'catci_minimarket_products');
$beforeV2Variants = $pdo->query(
    'SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo, $dir . 'updates/mysql/0.5.20.sql', 'catci_');
verify($beforeV2Product === row($pdo, 'catci_minimarket_products'),
    '0.5.20 keeps all legacy product data untouched');
verify($beforeV2Variants === $pdo->query(
    'SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC), '0.5.20 keeps all variant IDs and trade data untouched');

$pdo->exec("INSERT INTO catci_minimarket_variant_dimensions (id,product_id,code,title,sort_order)
    VALUES (1,1,'color','Цвет',0),(2,1,'size','Размер',1)");
$pdo->exec("INSERT INTO catci_minimarket_variant_dimension_values
    (id,dimension_id,code,title,sort_order) VALUES
    (1,1,'red','Красный',0),(2,1,'blue','Синий',1),
    (3,2,'s','S',0),(4,2,'m','M',1)");
$pdo->exec("INSERT INTO catci_minimarket_variant_value_assignments (variant_id,dimension_id,value_id)
    VALUES (1,1,1),(1,2,3),(2,1,1),(2,2,4)");
verify((int) $pdo->query("SELECT COUNT(*) FROM catci_minimarket_variant_value_assignments")
    ->fetchColumn() === 4, 'two SKUs with local Color and Size assignments');
$pdo->exec("INSERT INTO catci_minimarket_import_product_keys
    (source_key,external_product_key,product_id) VALUES ('erp','group-1001',1)");
$pdo->exec("INSERT INTO catci_minimarket_import_sku_keys
    (source_key,external_sku_key,variant_id) VALUES
    ('erp','line-a',1),('erp','line-b',2)");
verify((int) $pdo->query("SELECT variant_id FROM catci_minimarket_import_sku_keys
    WHERE source_key='erp' AND external_sku_key='line-a'")->fetchColumn() === 1,
    'external SKU identity maps to stable variant ID');
try {
    $pdo->exec("INSERT INTO catci_minimarket_import_sku_keys
        (source_key,external_sku_key,variant_id) VALUES ('erp','line-a',2)");
    verify(false,'duplicate external SKU reference must fail');
} catch (PDOException $e) {
    verify(true,'duplicate external SKU reference rejected');
}
try {
    $pdo->exec("DELETE FROM catci_minimarket_product_variants WHERE id=1");
    verify(false,'SKU with external identity cannot be deleted');
} catch (PDOException $e) {
    verify(true,'SKU with external identity protected');
}
try {
    $pdo->exec("DELETE FROM catci_minimarket_variant_dimension_values WHERE id=1");
    verify(false,'assigned option cannot be deleted');
} catch (PDOException $e) {
    verify(true,'assigned option protected');
}
try {
    $pdo->exec("INSERT INTO catci_minimarket_variant_dimensions
        (product_id,code,title) VALUES (1,'color','Duplicate')");
    verify(false,'dimension code unique per product');
} catch (PDOException $e) {
    verify(true,'dimension code unique per product');
}
try {
    $pdo->exec("INSERT INTO catci_minimarket_variant_dimension_values
        (dimension_id,code,title) VALUES (2,'s','Duplicate')");
    verify(false,'value code unique per axis');
} catch (PDOException $e) {
    verify(true,'value code unique per axis');
}
$pdo->exec("INSERT INTO catci_minimarket_import_sku_keys
    (source_key,external_sku_key,variant_id) VALUES ('ERP','line-a',2)");
verify((int) $pdo->query("SELECT COUNT(*) FROM catci_minimarket_import_sku_keys
    WHERE external_sku_key='line-a'")->fetchColumn() === 2,
    'external source keys are case-sensitive');
// 0.5.25: add descriptions, SKU videos and shared execution photos without
// changing any historical product or SKU record.
$beforeSkuV3=$pdo->query('SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$beforeProductV3=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.25.sql','catci_');
verify($beforeSkuV3===$pdo->query('SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.25 upgrade retains SKU IDs, selections, prices, stocks');
verify($beforeProductV3===row($pdo,'catci_minimarket_products'),
    '0.5.25 upgrade preserves existing product/SEO data');
$pdo->exec("UPDATE catci_minimarket_product_variants SET
    description_mode=1,introtext_override='<p>Blue</p>',description_override='Description',video_mode=1 WHERE id=1");
$pdo->exec("INSERT INTO catci_minimarket_product_variant_videos
    (variant_id,title,source,sort_order) VALUES
    (1,'YouTube','https://www.youtube.com/watch?v=dQw4w9WgXcQ',0)");
$pdo->exec("INSERT INTO catci_minimarket_variant_value_images
    (value_id,path,alt,sort_order) VALUES
    (1,'images/red.webp','Red',0),(1,'images/red-back.webp','Red rear',1)");
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_variant_value_images WHERE value_id=1')->fetchColumn()===2,
    'one color option owns reusable multi-photo gallery');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_variant_videos WHERE variant_id=1')->fetchColumn()===1,
    'video belongs to one concrete SKU');
verify((int)$pdo->query("SELECT description_mode FROM catci_minimarket_product_variants WHERE id=2")->fetchColumn()===0,
    'historical SKU descriptions inherit by default');
try {
    $pdo->exec("INSERT INTO catci_minimarket_product_variant_videos
       (variant_id,title,source,sort_order) VALUES
       (1,'Duplicate','https://www.youtube.com/watch?v=dQw4w9WgXcQ',1)");
    verify(false,'duplicate SKU video source should be rejected');
} catch(PDOException $e) { verify(true,'duplicate SKU video source rejected'); }
// 0.5.26: independent products may be related without joining their URLs.
$beforeOptionSkus=$pdo->query('SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$beforeOptionProduct=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.26.sql','catci_');
foreach ($beforeOptionProduct as $column=>$value) {
    verify(row($pdo,'catci_minimarket_products')[$column]===$value,
        '0.5.26 keeps existing product column '.$column);
}
verify($beforeOptionSkus===$pdo->query('SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.26 retains historical SKU/stock/price/selection unchanged');
verify((int)$pdo->query('SELECT option_stock_mode FROM catci_minimarket_products WHERE id=1')->fetchColumn()===1,
    'existing variants retain per-combination stock mode');
$pdo->exec("INSERT INTO catci_minimarket_products
    (id,category_id,title,alias,sku,price,stock,state)
    VALUES (2,1,'Blue shirt','blue-shirt','BLUE-BASE',1990,15,1)");
verify((int)$pdo->query('SELECT option_stock_mode FROM catci_minimarket_products WHERE id=2')->fetchColumn()===0,
    'new product defaults to shared stock');
$pdo->exec("INSERT INTO catci_minimarket_product_groups (id,title)
    VALUES (7,'Shirts Classic')");
$pdo->exec("INSERT INTO catci_minimarket_product_group_members (product_id,group_id)
    VALUES (1,7),(2,7)");
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_group_members WHERE group_id=7')->fetchColumn()===2,
    'two independent products belong to one non-selling group');
verify((string)$pdo->query('SELECT alias FROM catci_minimarket_products WHERE id=2')->fetchColumn()==='blue-shirt',
    'linked product preserves its own unique URL alias');
try {
    $pdo->exec("INSERT INTO catci_minimarket_product_group_members (product_id,group_id) VALUES (1,7)");
    verify(false,'duplicate group membership was allowed');
} catch(PDOException $e) { verify(true,'each product belongs to at most one group'); }
try {
    $pdo->exec('DELETE FROM catci_minimarket_product_groups WHERE id=7');
    verify(false,'deleting an occupied linked group was allowed');
} catch(PDOException $e) { verify(true,'linked group cannot be deleted while referenced'); }
// 0.5.28: per-SKU stock is default for NEW records, preserving each old choice.
$beforeDefaultUpgradeProductOne=row($pdo,'catci_minimarket_products');
$beforeDefaultUpgradeSkus=$pdo->query(
    'SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC);
$beforeSharedProduct=$pdo->query('SELECT option_stock_mode FROM catci_minimarket_products WHERE id=2')->fetchColumn();
verify((int)$beforeSharedProduct===0,'0.5.26 simple product explicitly remains shared before new default');
runSql($pdo,$dir.'updates/mysql/0.5.28.sql','catci_');
verify($beforeDefaultUpgradeProductOne===row($pdo,'catci_minimarket_products'),
    '0.5.28 default migration does not alter any existing product fields');
verify((int)$pdo->query('SELECT option_stock_mode FROM catci_minimarket_products WHERE id=2')->fetchColumn()===0,
    '0.5.28 preserves existing shared-stock setting');
verify($beforeDefaultUpgradeSkus===$pdo->query(
    'SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id'
)->fetchAll(PDO::FETCH_ASSOC),'0.5.28 preserves all existing size/color SKU trade data');
$modeColumn=$pdo->query("SHOW COLUMNS FROM catci_minimarket_products LIKE 'option_stock_mode'")->fetch(PDO::FETCH_ASSOC);
verify((int)$modeColumn['Default']===1,'0.5.28 SQL default is per-SKU inventory');
$pdo->exec("INSERT INTO catci_minimarket_products
    (id,category_id,title,alias,sku,price,stock,state)
    VALUES (3,1,'New size-based T-shirt','size-based-shirt','SIZE-BASE',990,0,0)");
verify((int)$pdo->query('SELECT option_stock_mode FROM catci_minimarket_products WHERE id=3')->fetchColumn()===1,
    '0.5.28 new product created without explicit stock mode defaults to per-SKU');
$pdo->exec('DELETE FROM catci_minimarket_products WHERE id=3');
// 0.5.29 removes the obsolete attribute-as-SKU column permanently.
$beforeCleanSkus=$pdo->query('SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo,$dir.'updates/mysql/0.5.29.sql','catci_');
$cleanAttrs=$pdo->query('SHOW COLUMNS FROM catci_minimarket_attributes')->fetchAll(PDO::FETCH_COLUMN);
verify(!in_array('is_variant',$cleanAttrs,true),'0.5.29 eliminates attribute-is-SKU flag');
verify($beforeCleanSkus===$pdo->query('SELECT id,product_id,sku,price,stock,selection_json FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.29 does not change actual trade SKU IDs, prices or quantities');
// 0.5.32: preserve old group rows, enable many-to-many and stable external group keys.
$beforeGroupProducts=$pdo->query('SELECT product_id,group_id,sort_order FROM catci_minimarket_product_group_members ORDER BY product_id')->fetchAll(PDO::FETCH_ASSOC);
$beforeGroupSkuData=$pdo->query('SELECT id,product_id,sku,price,stock FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$beforeGroupProduct=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.32.sql','catci_');
verify($beforeGroupProducts===$pdo->query('SELECT product_id,group_id,sort_order FROM catci_minimarket_product_group_members ORDER BY product_id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.32 preserves old memberships including order');
verify($beforeGroupProduct===row($pdo,'catci_minimarket_products'),
    '0.5.32 does not change product data or SEO');
verify($beforeGroupSkuData===$pdo->query('SELECT id,product_id,sku,price,stock FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.32 SKU identity, price and stock unchanged');
verify((string)$pdo->query('SELECT type FROM catci_minimarket_product_groups WHERE id=7')->fetchColumn()==='similar',
    '0.5.32 existing group assigned safe default type');
$pdo->exec("INSERT INTO catci_minimarket_product_groups (id,title,type,state)
    VALUES (8,'Summer collection','collection',1)");
$pdo->exec('INSERT INTO catci_minimarket_product_group_members (product_id,group_id) VALUES (1,8)');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_group_members WHERE product_id=1')->fetchColumn()===2,
    'same independent product belongs to two groups');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_group_members WHERE group_id=7')->fetchColumn()===2,
    'old group members remain after adding overlapping membership');
try {
    $pdo->exec('INSERT INTO catci_minimarket_product_group_members (product_id,group_id) VALUES (1,8)');
    verify(false,'duplicate product-group pair allowed');
} catch(PDOException $e) { verify(true,'duplicate membership pair rejected'); }
$pdo->exec("INSERT INTO catci_minimarket_import_group_keys (source_key, external_group_key, group_id)
    VALUES ('erp','summer-2026',8)");
verify((int)$pdo->query("SELECT group_id FROM catci_minimarket_import_group_keys
    WHERE source_key='erp' AND external_group_key='summer-2026'")->fetchColumn()===8,
    'external import group identifier maps to exact group ID');
$memberIndexes=$pdo->query('SHOW INDEX FROM catci_minimarket_product_group_members')->fetchAll(PDO::FETCH_ASSOC);
verify(count(array_filter($memberIndexes,static fn($x)=>$x['Key_name']==='PRIMARY'))===2,
    'composite group-product primary key installed');
// Removing a product from a relationship must not delete the relationship or
// remove any of its other participants. A newly created group can remain empty.
$beforeUnlinkSkus=$pdo->query('SELECT id,product_id,sku,price,stock
    FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$pdo->exec('DELETE FROM catci_minimarket_product_group_members
    WHERE group_id=7 AND product_id=1');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_groups
    WHERE id=7')->fetchColumn()===1,
    'group still exists after founding product is removed');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_group_members
    WHERE group_id=7')->fetchColumn()===1,
    'other product remains in group after founder leaves');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_group_members
    WHERE group_id=8 AND product_id=1')->fetchColumn()===1,
    'removing membership in one group does not touch other groups');
$pdo->exec("INSERT INTO catci_minimarket_product_groups (id,title,type,state)
    VALUES (9,'Group independent of original product','similar',1)");
$pdo->exec('INSERT INTO catci_minimarket_product_group_members
    (product_id,group_id) VALUES (1,9)');
$pdo->exec('DELETE FROM catci_minimarket_product_group_members
    WHERE group_id=9 AND product_id=1');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_groups
    WHERE id=9')->fetchColumn()===1,
    'new group remains in group list even with no members');
verify($beforeUnlinkSkus===$pdo->query('SELECT id,product_id,sku,price,stock
    FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    'unlinked member retains its own product SKU, price and stock');


/** Global order-option dictionaries are additive and category overrides are independent. */
$beforeOrderProduct=row($pdo,'catci_minimarket_products');
$beforeOrderSkus=$pdo->query('SELECT id,product_id,sku,price,stock FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo,$dir.'updates/mysql/0.5.34.sql','catci_');
verify($beforeOrderProduct===row($pdo,'catci_minimarket_products'),
    '0.5.34 preserves existing product, price, stock, SEO');
verify($beforeOrderSkus===$pdo->query('SELECT id,product_id,sku,price,stock FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.34 preserves every existing SKU');
$pdo->exec("INSERT INTO catci_minimarket_order_options
    (id,title,code,state) VALUES (11,'Size','size',1)");
$pdo->exec("INSERT INTO catci_minimarket_order_option_values
    (id,option_id,title,code,sort_order,state) VALUES
    (21,11,'S','s',0,1),(22,11,'M','m',1,1),(23,11,'L','l',2,1)");
$pdo->exec("INSERT INTO catci_minimarket_category_order_options
    (category_id,option_id,mode) VALUES (1,11,2)");
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_order_option_values
    WHERE option_id=11')->fetchColumn()===3,'one global option has three reusable values');
verify((int)$pdo->query('SELECT mode FROM catci_minimarket_category_order_options
    WHERE option_id=11 AND category_id=1')->fetchColumn()===2,
    'order option required by category without product SKU changes');
try {
    $pdo->exec("INSERT INTO catci_minimarket_order_option_values
        (option_id,title,code) VALUES (11,'Duplicate','m')");
    verify(false,'duplicate global option value code accepted');
} catch (PDOException $error) { verify(true,'unique global option value code enforced'); }
// The global option tables stay alive for the 0.5.37 foreign-key upgrade test.
// They are dropped only after the local dimension/value tables are removed.

// 0.5.35: a stable source-qualified external mapping for all catalog entities.
$beforeImportProduct=row($pdo,'catci_minimarket_products');
$beforeImportSkus=$pdo->query('SELECT id,product_id,sku,price,stock FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo,$dir.'updates/mysql/0.5.35.sql','catci_');
$pdo->exec("INSERT INTO catci_minimarket_import_entity_keys
    (source_key,entity_kind,external_key,local_id) VALUES
    ('feed','categories','topki',1),('feed','products','stove',1),
    ('supplier-b','categories','topki',1)");
verify((int)$pdo->query("SELECT COUNT(*) FROM catci_minimarket_import_entity_keys
    WHERE external_key='topki'")->fetchColumn()===2,
    'two sources can map the same external key without collisions');
try {
    $pdo->exec("INSERT INTO catci_minimarket_import_entity_keys
        (source_key,entity_kind,external_key,local_id)
        VALUES ('feed','categories','topki',2)");
    verify(false,'duplicate external mapping accepted');
} catch (PDOException $error) { verify(true,'source+kind+external key unique'); }
verify($beforeImportProduct===row($pdo,'catci_minimarket_products'),
    '0.5.35 migration preserves existing products');
verify($beforeImportSkus===$pdo->query('SELECT id,product_id,sku,price,stock FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.35 migration preserves all SKU identity, prices and stocks');
$pdo->exec('DROP TABLE catci_minimarket_import_entity_keys');

// 0.5.37: link existing option axes to the global dictionary by stable IDs;
// preserve every historical SKU and every unrelated local dimension.
$beforeSourceSkus=$pdo->query('SELECT id,product_id,sku,price,stock,selection_json
    FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$beforeSourceProducts=$pdo->query('SELECT * FROM catci_minimarket_products ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$beforeSourceAssignments=$pdo->query('SELECT * FROM catci_minimarket_variant_value_assignments
    ORDER BY variant_id,dimension_id')->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo,$dir.'updates/mysql/0.5.37.sql','catci_');
verify($beforeSourceSkus===$pdo->query('SELECT id,product_id,sku,price,stock,selection_json
    FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.37 preserves every SKU, price, stock and selection JSON');
verify($beforeSourceProducts===$pdo->query('SELECT * FROM catci_minimarket_products ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.37 preserves all product data');
verify($beforeSourceAssignments===$pdo->query('SELECT * FROM catci_minimarket_variant_value_assignments
    ORDER BY variant_id,dimension_id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.37 preserves relational variant identity and existing assignments');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_variant_dimensions
    WHERE source_option_id IS NOT NULL')->fetchColumn()===0,
    'no implicit adoption or rewriting of historic local SKU axes');
$pdo->exec('UPDATE catci_minimarket_variant_dimensions
    SET source_option_id=11 WHERE id=2');
$pdo->exec('UPDATE catci_minimarket_variant_dimension_values
    SET source_value_id=21 WHERE id=3');
$pdo->exec('UPDATE catci_minimarket_variant_dimension_values
    SET source_value_id=22 WHERE id=4');
verify((int)$pdo->query('SELECT source_option_id FROM catci_minimarket_variant_dimensions
    WHERE id=2')->fetchColumn()===11,
    'linked dimension retains stable global source option ID');
verify((int)$pdo->query('SELECT source_value_id FROM catci_minimarket_variant_dimension_values
    WHERE id=3')->fetchColumn()===21,
    'linked value retains stable global source value ID');
try {
    $pdo->exec('UPDATE catci_minimarket_variant_dimension_values
        SET source_value_id=999999 WHERE id=3');
    verify(false,'invalid global source value ID accepted');
} catch (PDOException $error) {
    verify(true,'global value FK rejects an unknown ID');
}
verify($beforeSourceAssignments===$pdo->query('SELECT * FROM catci_minimarket_variant_value_assignments
    ORDER BY variant_id,dimension_id')->fetchAll(PDO::FETCH_ASSOC),
    'global option linkage does not rewrite old variant assignments');

// 0.5.39: gallery per physical SKU and nullable article number, additive.
$before39Skus=$pdo->query('SELECT id,product_id,sku,price,stock,selection_json
    FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$before39Images=$pdo->query('SELECT id,product_id,path,alt,sort_order
    FROM catci_minimarket_product_images ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo,$dir.'updates/mysql/0.5.39.sql','catci_');
verify($before39Skus===$pdo->query('SELECT id,product_id,sku,price,stock,selection_json
    FROM catci_minimarket_product_variants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.39 retains existing SKU identity, price, stock and selections');
verify($before39Images===$pdo->query('SELECT id,product_id,path,alt,sort_order
    FROM catci_minimarket_product_images ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.39 keeps old product images, order and alt');
$skuColumn=$pdo->query("SHOW COLUMNS FROM catci_minimarket_product_variants LIKE 'sku'")
    ->fetch(PDO::FETCH_ASSOC);
verify(strtoupper((string)$skuColumn['Null'])==='YES',
    '0.5.39 allows NULL SKU labels with unique nonempty names');
$imgColumn=$pdo->query("SHOW COLUMNS FROM catci_minimarket_product_images LIKE 'visibility_mode'")
    ->fetch(PDO::FETCH_ASSOC);
verify((int)$imgColumn['Default']===0,'old photos remain visible everywhere');
$pdo->exec("INSERT INTO catci_minimarket_product_variants
    (product_id,sku,selection_json,signature,price,stock) VALUES
    (1,NULL,'{}','" . hash('sha256','no-label-1') . "',123,0),
    (1,NULL,'{}','" . hash('sha256','no-label-2') . "',123,0)");
verify((int)$pdo->query("SELECT COUNT(*) FROM catci_minimarket_product_variants
    WHERE sku IS NULL")->fetchColumn()===2,
    'different combinations may both have empty SKU labels');
$imgId=(int)$pdo->query("SELECT id FROM catci_minimarket_product_images LIMIT 1")->fetchColumn();
$pdo->exec("UPDATE catci_minimarket_product_images SET visibility_mode=1 WHERE id=".$imgId);
$pdo->exec("INSERT INTO catci_minimarket_product_image_variants
    (image_id,variant_id,is_cover) VALUES (".$imgId.",1,1)");
verify((int)$pdo->query("SELECT is_cover FROM catci_minimarket_product_image_variants
    WHERE image_id=".$imgId." AND variant_id=1")->fetchColumn()===1,
    'gallery image can be main for one stable variant ID');
try {
    $pdo->exec("INSERT INTO catci_minimarket_product_image_variants
        (image_id,variant_id,is_cover) VALUES (".$imgId.",999999,0)");
    verify(false,'gallery accepted unknown physical SKU');
} catch(PDOException $e) {
    verify(true,'gallery SKU FK rejects unknown variant ID');
}

// 0.5.43: option values now have optional images; old IDs/colors remain.
$beforeOptionImage=$pdo->query("SELECT id,option_id,title,code,color_hex,state
  FROM catci_minimarket_order_option_values ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo,$dir.'updates/mysql/0.5.43.sql','catci_');
$optionImageField=$pdo->query("SHOW COLUMNS FROM catci_minimarket_order_option_values
  LIKE 'sample_image'")->fetch(PDO::FETCH_ASSOC);
verify($optionImageField && $optionImageField['Default']==='',
  '0.5.43 gives existing option values an empty optional sample image');
verify($beforeOptionImage===$pdo->query("SELECT id,option_id,title,code,color_hex,state
  FROM catci_minimarket_order_option_values ORDER BY id")->fetchAll(PDO::FETCH_ASSOC),
  '0.5.43 retains every old option title, ID, HEX, code and status');
$pdo->exec("UPDATE catci_minimarket_order_option_values
  SET sample_image='images/minimarket/sample.png' ORDER BY id LIMIT 1");
verify((int)$pdo->query("SELECT COUNT(*) FROM catci_minimarket_order_option_values
  WHERE sample_image='images/minimarket/sample.png'")->fetchColumn()===1,
  'a reusable option value stores its sample image');

// 0.5.45: the additive migration must preserve old values and SKU associations.
$before45Scalar=$pdo->query('SELECT * FROM catci_minimarket_product_attribute_scalars
    ORDER BY product_id,attribute_id')->fetchAll(PDO::FETCH_ASSOC);
$before45Options=$pdo->query('SELECT * FROM catci_minimarket_product_attribute_options
    ORDER BY product_id,attribute_id,value_id')->fetchAll(PDO::FETCH_ASSOC);
$before45Values=$pdo->query('SELECT id,attribute_id,title,code,color_hex,sample_image,state
    FROM catci_minimarket_attribute_values ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
runSql($pdo,$dir.'updates/mysql/0.5.45.sql','catci_');
verify($before45Scalar===$pdo->query('SELECT * FROM catci_minimarket_product_attribute_scalars
    ORDER BY product_id,attribute_id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.45 preserves existing numeric and text scalars');
verify($before45Options===$pdo->query('SELECT * FROM catci_minimarket_product_attribute_options
    ORDER BY product_id,attribute_id,value_id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.45 preserves old dictionary value IDs');
verify($before45Values===$pdo->query('SELECT id,attribute_id,title,code,color_hex,sample_image,state
    FROM catci_minimarket_attribute_values ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
    '0.5.45 preserves value metadata');
verify((int)$pdo->query("SELECT COUNT(*) FROM catci_minimarket_attributes
    WHERE value_source='legacy'")->fetchColumn()>0,
    'legacy records stay in legacy mode');
$pdo->exec("INSERT INTO catci_minimarket_product_attribute_free_values
    (product_id,attribute_id,value_text,sort_order) VALUES (1,3,'5',0),(1,3,'10',1)");
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_product_attribute_free_values
    WHERE product_id=1 AND attribute_id=3')->fetchColumn()===2,
    'manual numeric field saves multiple separate rows');

// 0.5.49: non-destructive brand + collections upgrade, preserved old product.
$before49=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.49.sql','catci_');
$after49=row($pdo,'catci_minimarket_products');
foreach ($before49 as $col=>$value) {
    verify(array_key_exists($col,$after49) && $value===$after49[$col],
        '0.5.49 preserves product field '.$col);
}
verify(array_key_exists('brand_id',$after49) && $after49['brand_id']===null,
    'legacy product has no brand by default');
$pdo->exec("INSERT INTO catci_minimarket_brands (id,title,alias,state)
    VALUES (1,'Northstar','northstar',1)");
$pdo->exec("INSERT INTO catci_minimarket_collections (id,title,alias,state)
    VALUES (1,'Summer','summer',1),(2,'Wood','wood',1)");
$pdo->exec("UPDATE catci_minimarket_products SET brand_id=1 WHERE id=1");
$pdo->exec("INSERT INTO catci_minimarket_product_collections
    (product_id,collection_id,sort_order) VALUES (1,1,0),(1,2,1)");
verify((int)$pdo->query("SELECT COUNT(*) FROM catci_minimarket_product_collections
    WHERE product_id=1")->fetchColumn()===2,
    'product belongs to multiple collections');
verify((int)$pdo->query("SELECT brand_id FROM catci_minimarket_products WHERE id=1")
    ->fetchColumn()===1,'one brand linked without touching stock');
try {
    $pdo->exec('DELETE FROM catci_minimarket_brands WHERE id=1');
    verify(false,'in-use brand deletion rejected');
} catch (PDOException $e) {
    verify(true,'in-use brand deletion rejected by FK');
}
try {
    $pdo->exec('DELETE FROM catci_minimarket_collections WHERE id=2');
    verify(false,'in-use collection deletion rejected');
} catch (PDOException $e) {
    verify(true,'in-use collection deletion rejected by FK');
}


 // 0.5.51: bundle migration leaves old product and SKU records untouched.
$beforeBundle=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.51.sql','catci_');
$afterBundle=row($pdo,'catci_minimarket_products');
foreach ($beforeBundle as $field=>$value) {
    verify(array_key_exists($field,$afterBundle) && $afterBundle[$field]===$value,
        '0.5.51 preserves existing product field '.$field);
}
verify($afterBundle['product_type']==='simple','old products stay simple');
$pdo->exec("INSERT INTO catci_minimarket_products
    (id,category_id,title,alias,sku,price,stock,product_type,state)
    VALUES (1090,1,'Bundle','bundle','BUNDLE-1090',99,0,'bundle',1)");
$pdo->exec("INSERT INTO catci_minimarket_bundle_items
    (bundle_product_id,component_product_id,component_variant_id,quantity)
    VALUES (1090,1,NULL,2)");
verify((int)$pdo->query("SELECT quantity FROM catci_minimarket_bundle_items
    WHERE bundle_product_id=1090")->fetchColumn()===2,'bundle component quantity stored');
try {
    $pdo->exec('DELETE FROM catci_minimarket_products WHERE id=1');
    verify(false,'referenced component deletion should be rejected');
} catch (PDOException $error) {
    verify(true,'FK protects used component from deletion');
}
$pdo->exec('DELETE FROM catci_minimarket_products WHERE id=1090');
verify((int)$pdo->query('SELECT COUNT(*) FROM catci_minimarket_bundle_items')
    ->fetchColumn()===0,'deleting parent bundle cascades component rows');

// 0.5.52: new pricing fields are additive and preserve fixed prices.
$beforePricing=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.52.sql','catci_');
$afterPricing=row($pdo,'catci_minimarket_products');
foreach($beforePricing as $column=>$value){
    verify(array_key_exists($column,$afterPricing) && $value===$afterPricing[$column],
        '0.5.52 preserves original product field '.$column);
}
verify($afterPricing['bundle_price_mode']==='fixed' &&
    (float)$afterPricing['bundle_discount_pct']===0.0,
    'upgrade leaves existing products and bundles with fixed prices');

// 0.5.53: new promotional flags and activation dates preserve all old data.
$beforeBadges=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.53.sql','catci_');
$afterBadges=row($pdo,'catci_minimarket_products');
foreach($beforeBadges as $field=>$value) {
    verify(array_key_exists($field,$afterBadges) && $afterBadges[$field]===$value,
        '0.5.53 preserves product field '.$field);
}
foreach(['old_price','badge_new_days','badge_new_since',
    'badge_popular_days','badge_popular_since'] as $field) {
    verify($afterBadges[$field]===null,'0.5.53 optional field defaults to NULL: '.$field);
}
foreach(['badge_sale','badge_new','badge_popular'] as $field) {
    verify((int)$afterBadges[$field]===0,'0.5.53 all flags start disabled: '.$field);
}
$pdo->exec("UPDATE catci_minimarket_products
 SET old_price=250.00,badge_sale=1,badge_new=1,
     badge_new_days=7,badge_new_since='2026-11-01 12:00:00' WHERE id=1");
$afterSetBadges=row($pdo,'catci_minimarket_products');
verify((float)$afterSetBadges['old_price']===250.0
    && (int)$afterSetBadges['badge_sale']===1
    && (int)$afterSetBadges['badge_new_days']===7,
    '0.5.53 stores old price, toggle, timed badge and timestamp');
// 0.5.54: category bundle visibility and optional savings flag.
$beforeBundleVisibility=row($pdo,'catci_minimarket_categories');
$beforeBenefits=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.54.sql','catci_');
$afterBundleVisibility=row($pdo,'catci_minimarket_categories');
$afterBenefits=row($pdo,'catci_minimarket_products');
foreach($beforeBundleVisibility as $col=>$old) {
    verify($afterBundleVisibility[$col]===$old,'0.5.54 preserves category '.$col);
}
foreach($beforeBenefits as $col=>$old) {
    verify($afterBenefits[$col]===$old,'0.5.54 preserves product '.$col);
}
verify((int)$afterBundleVisibility['show_bundles']===-1,
    '0.5.54 category bundle visibility inherits by default');
verify((int)$afterBenefits['badge_benefit']===0,
    '0.5.54 old kits do not display savings without user consent');
$pdo->exec("UPDATE catci_minimarket_categories SET show_bundles=0 WHERE id=1");
$pdo->exec("UPDATE catci_minimarket_products SET badge_benefit=1 WHERE id=1");
verify((int)row($pdo,'catci_minimarket_categories')['show_bundles']===0,
    '0.5.54 category can hide bundles independently');
verify((int)row($pdo,'catci_minimarket_products')['badge_benefit']===1,
    '0.5.54 kit savings flag persists');
// 0.5.58 additive category layout overrides on an existing populated catalog.
$beforeDisplay = row($pdo, 'catci_minimarket_categories');
runSql($pdo, $dir . 'updates/mysql/0.5.58.sql', 'catci_');
$afterDisplay = row($pdo, 'catci_minimarket_categories');
foreach ($beforeDisplay as $col => $value) {
    verify(isset($afterDisplay[$col]) && $value === $afterDisplay[$col],
        '0.5.58 preserved existing category column ' . $col);
}
foreach (['show_descendant_products','show_descendant_bundles','product_layout',
    'cards_mobile','cards_tablet','cards_desktop','cards_wide'] as $field) {
    verify(array_key_exists($field,$afterDisplay),'0.5.58 new category option '.$field);
}
verify((int)$afterDisplay['show_descendant_products']===-1
    && (int)$afterDisplay['show_descendant_bundles']===-1,
    '0.5.58 defaults to inherited descendant visibility');
verify($afterDisplay['product_layout']==='inherit'
    && (int)$afterDisplay['cards_mobile']===-1,
    '0.5.58 layout and columns inherit by default');
$pdo->exec("UPDATE catci_minimarket_categories SET product_layout='grid',
    cards_mobile=1,cards_tablet=2,cards_desktop=3,cards_wide=4,
    show_descendant_products=1,show_descendant_bundles=0 WHERE id=1");
verify(row($pdo, 'catci_minimarket_categories')['product_layout']==='grid'
    && (int)row($pdo,'catci_minimarket_categories')['cards_desktop']===3,
    '0.5.58 override persists without touching category data');
verify($beforeProduct === row($pdo, 'catci_minimarket_products')
    || (string)row($pdo, 'catci_minimarket_products')['sku']==='TEST-001',
    '0.5.58 upgrade does not change product SKU');

// 0.5.59 safe schema upgrade of already populated product/video records.
$before59=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.59.sql','catci_');
$after59=row($pdo,'catci_minimarket_products');
foreach($before59 as $k=>$v) {
 verify(array_key_exists($k,$after59) && $after59[$k]===$v,
  '0.5.59 preserves product field '.$k);
}
verify($after59['badge_sale_display']==='percent'
 && $after59['badge_benefit_display']==='money','new default badge display');
$promoColumn=$pdo->query("SHOW COLUMNS FROM catci_minimarket_product_videos LIKE 'is_promo'")
 ->fetch(PDO::FETCH_ASSOC);
verify((int)$promoColumn['Default']===0,'existing videos remain ordinary');
$pdo->exec("UPDATE catci_minimarket_products SET badge_sale_display='money' WHERE id=1");
verify(row($pdo,'catci_minimarket_products')['badge_sale_display']==='money',
 'badge display is persisted');
// 0.5.55: delivery type is a distinct attribute of ordinary products.
$beforeDelivery=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.55.sql','catci_');
$afterDelivery=row($pdo,'catci_minimarket_products');
foreach($beforeDelivery as $column=>$value) {
    verify(array_key_exists($column,$afterDelivery) && $afterDelivery[$column]===$value,
        '0.5.55 upgrade retains product data '.$column);
}
verify($afterDelivery['delivery_type']==='physical',
    '0.5.55 existing products default to physical, not digital');
$pdo->exec("UPDATE catci_minimarket_products SET delivery_type='digital' WHERE id=1");
verify(row($pdo,'catci_minimarket_products')['delivery_type']==='digital',
    '0.5.55 digital delivery flag persists on product');
// Upgrade a populated old-schema category: rename each original value rather
// than dropping and recreating it. Existing commercial data must be untouched.
$beforeGridCategory=row($pdo,'catci_minimarket_categories');
$beforeGridProduct=row($pdo,'catci_minimarket_products');
runSql($pdo,$dir.'updates/mysql/0.5.71.sql','catci_');
$afterGridCategory=row($pdo,'catci_minimarket_categories');
foreach (['id','title','alias','parent_id','state','sort_order'] as $field) {
    verify($beforeGridCategory[$field]===$afterGridCategory[$field],
        '0.5.71 preserves category field '.$field);
}
foreach (['mobile'=>'xs','tablet'=>'md','desktop'=>'lg','wide'=>'xl'] as $before=>$after) {
    verify($beforeGridCategory['cards_'.$before]===$afterGridCategory['cards_'.$after],
        '0.5.71 maps '.$before.' -> '.$after);
}
verify((int)$afterGridCategory['cards_sm']===-1 &&
    (int)$afterGridCategory['cards_xxl']===-1,
    '0.5.71 new breakpoint overrides inherit globally by default');
foreach (['cards_mobile','cards_tablet','cards_desktop','cards_wide'] as $oldField) {
    verify(!array_key_exists($oldField,$afterGridCategory),
        '0.5.71 removes old column '.$oldField);
}
verify($beforeGridProduct===row($pdo,'catci_minimarket_products'),
    '0.5.71 grid schema migration does not modify products');


$pdo->exec('DROP TABLE catci_minimarket_product_collections');
$pdo->exec('DROP TABLE catci_minimarket_import_group_keys');
$pdo->exec('DROP TABLE catci_minimarket_product_group_members');
$pdo->exec('DROP TABLE catci_minimarket_product_groups');
$pdo->exec('DELETE FROM catci_minimarket_products WHERE id=2');
$pdo->exec('DROP TABLE catci_minimarket_product_variant_videos');
$pdo->exec('DROP TABLE catci_minimarket_variant_value_images');
$pdo->exec('DROP TABLE catci_minimarket_import_sku_keys');
$pdo->exec('DROP TABLE catci_minimarket_import_product_keys');
$pdo->exec('DROP TABLE catci_minimarket_variant_value_assignments');
$pdo->exec('DROP TABLE catci_minimarket_variant_dimension_values');
$pdo->exec('DROP TABLE catci_minimarket_variant_dimensions');
$pdo->exec('DROP TABLE catci_minimarket_category_order_options');
$pdo->exec('DROP TABLE catci_minimarket_order_option_values');
$pdo->exec('DROP TABLE catci_minimarket_order_options');
$pdo->exec('DROP TABLE catci_minimarket_product_image_variants');
$pdo->exec('DROP TABLE catci_minimarket_product_images');
$pdo->exec('DROP TABLE catci_minimarket_product_variant_images');
$pdo->exec('DROP TABLE catci_minimarket_bundle_items');
$pdo->exec('DROP TABLE catci_minimarket_product_variants');
$pdo->exec('DROP TABLE catci_minimarket_product_attribute_free_values');
$pdo->exec('DROP TABLE catci_minimarket_product_attribute_multi_values');
$pdo->exec('DROP TABLE catci_minimarket_product_videos');
$pdo->exec('DROP TABLE catci_minimarket_product_attribute_options');
$pdo->exec('DROP TABLE catci_minimarket_product_attribute_scalars');

$pdo->exec('DROP TABLE catci_minimarket_category_attributes');
$pdo->exec('DROP TABLE catci_minimarket_attribute_values');
$pdo->exec('DROP TABLE catci_minimarket_attributes');

// MySQL FK constraint identifiers are database-wide: clean only CI test tables.
$pdo->exec('DROP TABLE catci_minimarket_products');
$pdo->exec('DROP TABLE catci_minimarket_brands');
$pdo->exec('DROP TABLE catci_minimarket_collections');
$pdo->exec('DROP TABLE catci_minimarket_categories');
runSql($pdo, $dir . 'install.mysql.sql', 'freshcatci_');
$columns = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_categories')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['introtext', 'sort_order', 'image', 'image_alt', 'show_children', 'show_products', 'show_bundles', 'meta_title', 'meta_description', 'meta_robots',
    'og_title', 'og_description', 'og_image', 'og_image_alt'] as $col) {
    verify(in_array($col, $columns, true), 'fresh-install column ' . $col);
}
$freshSkuNullable=$pdo->query("SHOW COLUMNS FROM freshcatci_minimarket_product_variants LIKE 'sku'")->fetch(PDO::FETCH_ASSOC);
verify(strtoupper((string)$freshSkuNullable['Null'])==='YES',
    'fresh 0.5.39 variants accept blank SKU');
$freshImageScope=$pdo->query("SHOW COLUMNS FROM freshcatci_minimarket_product_images LIKE 'visibility_mode'")->fetch(PDO::FETCH_ASSOC);
verify((int)$freshImageScope['Default']===0,
    'fresh 0.5.39 old photos visible to every SKU');
$freshPhotoLinks=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_image_variants')->fetchAll(PDO::FETCH_COLUMN);
foreach (['image_id','variant_id','is_cover'] as $column)
    verify(in_array($column,$freshPhotoLinks,true),'fresh 0.5.39 photo link '.$column);
$freshSkus=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_variants')->fetchAll(PDO::FETCH_COLUMN);
foreach (['description_mode','introtext_override','description_override','video_mode'] as $column) {
    verify(in_array($column,$freshSkus,true),'fresh 0.5.25 SKU override '.$column);
}
foreach ([
  'product_variant_videos'=>['id','variant_id','title','source','poster','sort_order'],
  'variant_value_images'=>['id','value_id','path','alt','sort_order'],
] as $table=>$required) {
    $columns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_'.$table)->fetchAll(PDO::FETCH_COLUMN);
    foreach ($required as $col) verify(in_array($col,$columns,true),'fresh 0.5.25 '.$table.' '.$col);
}
$freshGroupColumns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_groups')->fetchAll(PDO::FETCH_COLUMN);
foreach (['id','title','type','state','sort_order'] as $column) verify(in_array($column,$freshGroupColumns,true),'fresh 0.5.32 group '.$column);
$freshImportEntities=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_import_entity_keys')->fetchAll(PDO::FETCH_COLUMN);
foreach (['source_key','entity_kind','external_key','local_id'] as $column)
    verify(in_array($column,$freshImportEntities,true),'fresh 0.5.35 import mapping '.$column);
$freshAxisColumns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_variant_dimensions')->fetchAll(PDO::FETCH_COLUMN);
verify(in_array('source_option_id',$freshAxisColumns,true),'fresh 0.5.37 global option FK column');
$freshValueColumns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_variant_dimension_values')->fetchAll(PDO::FETCH_COLUMN);
verify(in_array('source_value_id',$freshValueColumns,true),'fresh 0.5.37 global value FK column');
$freshOrderOptions=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_order_options')->fetchAll(PDO::FETCH_COLUMN);
foreach (['id','code','title','state','sort_order'] as $column)
    verify(in_array($column,$freshOrderOptions,true),'fresh 0.5.34 global option '.$column);
$freshOrderValues=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_order_option_values')->fetchAll(PDO::FETCH_COLUMN);
verify(in_array('sample_image',$freshOrderValues,true),
  'fresh 0.5.43 installation supports option value images');
foreach (['id','option_id','title','code','color_hex','state','sort_order'] as $column)
    verify(in_array($column,$freshOrderValues,true),'fresh 0.5.34 order option value '.$column);
$freshOrderRules=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_category_order_options')->fetchAll(PDO::FETCH_COLUMN);
foreach (['category_id','option_id','mode'] as $column)
    verify(in_array($column,$freshOrderRules,true),'fresh 0.5.34 category order option '.$column);

$freshMemberColumns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_group_members')->fetchAll(PDO::FETCH_COLUMN);
foreach (['product_id','group_id','sort_order'] as $column)
    verify(in_array($column,$freshMemberColumns,true),'fresh 0.5.26 member '.$column);
$freshGroupImport=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_import_group_keys')->fetchAll(PDO::FETCH_COLUMN);
foreach (['source_key','external_group_key','group_id'] as $column)
    verify(in_array($column,$freshGroupImport,true),'fresh 0.5.32 import group '.$column);
$freshMemberPrimary=$pdo->query("SHOW INDEX FROM freshcatci_minimarket_product_group_members WHERE Key_name = 'PRIMARY'")->fetchAll(PDO::FETCH_ASSOC);
verify(count($freshMemberPrimary)===2,'fresh 0.5.32 composite group/product primary key');

$freshProducts = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_products')
    ->fetchAll(PDO::FETCH_COLUMN);
verify(in_array('option_stock_mode',$freshProducts,true),'fresh 0.5.26 option stock mode');
verify(in_array('brand_id',$freshProducts,true),'fresh 0.5.49 optional brand ID');
verify(in_array('product_type',$freshProducts,true),'fresh 0.5.51 bundle type');
verify(in_array('delivery_type',$freshProducts,true),'fresh 0.5.55 physical/digital type');
verify(in_array('badge_sale_display',$freshProducts,true)
 && in_array('badge_benefit_display',$freshProducts,true),
 'fresh badge display mode columns');
verify(in_array('is_promo',$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_videos')
 ->fetchAll(PDO::FETCH_COLUMN),true),'fresh promo column');
verify(in_array('bundle_price_mode',$freshProducts,true) &&
    in_array('bundle_discount_pct',$freshProducts,true),
    'fresh 0.5.52 pricing columns');
foreach(['old_price','badge_sale','badge_benefit','badge_new','badge_new_days',
    'badge_new_since','badge_popular','badge_popular_days','badge_popular_since'] as $field) {
    verify(in_array($field,$freshProducts,true),'fresh 0.5.53 badge column '.$field);
}
$freshBundleColumns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_bundle_items')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['bundle_product_id','component_product_id','component_variant_id','quantity','sort_order'] as $col) {
    verify(in_array($col,$freshBundleColumns,true),'fresh bundle item '.$col);
}
$brandColumns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_brands')->fetchAll(PDO::FETCH_COLUMN);
$collectionColumns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_collections')->fetchAll(PDO::FETCH_COLUMN);
foreach (['id','title','alias','description','image','meta_title','state','sort_order'] as $col) {
    verify(in_array($col,$brandColumns,true),'fresh brand column '.$col);
    verify(in_array($col,$collectionColumns,true),'fresh collection column '.$col);
}
verify(in_array('website_url',$brandColumns,true),'brand HTTPS website');
$pivotColumns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_collections')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['product_id','collection_id','sort_order'] as $col) {
    verify(in_array($col,$pivotColumns,true),'fresh product collection '.$col);
}
$freshMode=$pdo->query("SHOW COLUMNS FROM freshcatci_minimarket_products LIKE 'option_stock_mode'")->fetch(PDO::FETCH_ASSOC);
verify((int)$freshMode['Default']===1,'fresh 0.5.28 products default to per-SKU inventory');
foreach ($productEditorColumns as $col) {
    verify(in_array($col, $freshProducts, true), 'fresh 0.5.0 product column ' . $col);
}
$freshGalleryColumns = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_images')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['id','product_id','path','alt','sort_order'] as $col) {
    verify(in_array($col, $freshGalleryColumns, true), 'fresh 0.5.3 gallery column ' . $col);
}
$freshAttrColumns = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_attributes')
    ->fetchAll(PDO::FETCH_COLUMN);
verify(!in_array('is_variant',$freshAttrColumns,true),'fresh 0.5.29 contains no obsolete SKU attribute flag');
foreach (['value_source','color_display'] as $col) {
    verify(in_array($col,$freshAttrColumns,true),'fresh 0.5.45 attribute '.$col);
}
foreach (['id','title','code','input_type','value_source','number_unit',
    'number_min','number_max','number_step','is_multiple','is_filterable',
    'filter_mode','filter_multiple','sort_order','state'] as $col) {
    verify(in_array($col, $freshAttrColumns, true), 'fresh 0.5.4 attribute column ' . $col);
}
$freshValueColumns = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_attribute_values')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['id','attribute_id','title','code','color_hex','sample_image','value_payload','sort_order','state'] as $col) {
    verify(in_array($col, $freshValueColumns, true), 'fresh 0.5.4 value column ' . $col);
}
$freshAssignmentColumns = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_category_attributes')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['category_id','attribute_id','enabled','required','show_in_list'] as $col) {
    verify(in_array($col, $freshAssignmentColumns, true), 'fresh 0.5.5 assignment column ' . $col);
}
$freshVideos = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_videos')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['id','product_id','title','source','poster','sort_order'] as $col) {
    verify(in_array($col,$freshVideos,true),'fresh video relation column '.$col);
}
$freshFree = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_attribute_free_values')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['id','product_id','attribute_id','value_text','sort_order'] as $col) {
    verify(in_array($col,$freshFree,true),'fresh 0.5.45 manual multi-value '.$col);
}
$freshScalars = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_attribute_scalars')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['product_id','attribute_id','value_text','value_number','value_boolean'] as $col) {
    verify(in_array($col,$freshScalars,true),'fresh 0.5.7 scalar column ' . $col);
}
$freshOptions = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_product_attribute_options')
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['product_id','attribute_id','value_id'] as $col) {
    verify(in_array($col,$freshOptions,true),'fresh 0.5.7 option column ' . $col);
}
foreach ([
    'variant_dimensions' => ['id','product_id','code','title','sort_order'],
    'variant_dimension_values' => ['id','dimension_id','code','title','image','color_hex','sort_order'],
    'variant_value_assignments' => ['variant_id','dimension_id','value_id'],
    'import_product_keys' => ['source_key','external_product_key','product_id'],
    'import_sku_keys' => ['source_key','external_sku_key','variant_id'],
] as $tableSuffix => $requiredColumns) {
    $columns = $pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_' . $tableSuffix)
        ->fetchAll(PDO::FETCH_COLUMN);
    foreach ($requiredColumns as $column) {
        verify(in_array($column, $columns, true), 'fresh 0.5.20 '
            . $tableSuffix . ' column ' . $column);
    }
}
$pdo->exec("INSERT INTO freshcatci_minimarket_categories
    (id,title,alias,state) VALUES (1,'New category','new-category',1)");
verify((int) row($pdo, 'freshcatci_minimarket_categories')['sort_order'] === 0,
    'fresh category defaults');
$freshDisplay = $pdo->query("SHOW COLUMNS FROM freshcatci_minimarket_categories")
    ->fetchAll(PDO::FETCH_COLUMN);
foreach (['show_descendant_products','show_descendant_bundles','product_layout',
    'cards_xs','cards_sm','cards_md','cards_lg','cards_xl','cards_xxl'] as $field) {
    verify(in_array($field,$freshDisplay,true),'fresh install has current grid tier '.$field);
}
foreach (['cards_mobile','cards_tablet','cards_desktop','cards_wide'] as $oldField) {
    verify(!in_array($oldField,$freshDisplay,true),
        'fresh install does not retain old responsive column '.$oldField);
}
foreach (['category_attributes','category_order_options'] as $table) {
    $columns=$pdo->query('SHOW COLUMNS FROM freshcatci_minimarket_'.$table)
        ->fetchAll(PDO::FETCH_COLUMN);
    verify(in_array('show_in_list',$columns,true),
        'fresh category assignment supports per-category card flag '.$table);
}
echo 'CATEGORY_SCHEMA_OK: ' . $checks . " cases\n";
