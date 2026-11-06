<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Import package v1. No destructive sync, no SKU/gallery modifications.
 * Every run is transactional. Preview runs the same SQL then rolls it back.
 */
final class CatalogImport
{
    private const TABLES = [
        'categories' => '#__minimarket_categories',
        'attributes' => '#__minimarket_attributes',
        'options' => '#__minimarket_order_options',
        'products' => '#__minimarket_products',
        'links' => '#__minimarket_product_groups',
    ];
    private const SECTIONS = [
        'categories', 'attributes', 'options', 'products', 'bundles', 'links',
        'category_attributes', 'category_options',
    ];
    private const MODES = ['disabled' => 0, 'optional' => 1, 'required' => 2, 'inherit' => -1];

    public static function decode(string $raw): array
    {
        if ($raw === '' || strlen($raw) > 3 * 1024 * 1024) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_FILE_INVALID');
        }
        try {
            $doc = json_decode($raw, true, 48, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_FILE_INVALID', 0, $error);
        }
        if (!is_array($doc) || array_is_list($doc) || ($doc['version'] ?? null) !== 1
            || !is_string($doc['source'] ?? null)
            || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $doc['source'])) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
        }
        $allowed = array_fill_keys(array_merge(['version', 'source', 'adopt_existing'], self::SECTIONS), true);
        if (isset($doc['adopt_existing']) && !is_bool($doc['adopt_existing'])) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
        }
        foreach (array_keys($doc) as $key) {
            if (!isset($allowed[$key])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
            }
        }
        $count = 0;
        foreach (self::SECTIONS as $section) {
            $rows = $doc[$section] ?? [];
            if (!is_array($rows) || !array_is_list($rows)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
            }
            $count += count($rows);
            if ($count > 2500) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_FILE_INVALID');
            }
            $seen = [];
            foreach ($rows as $i => $row) {
                if (!is_array($row) || array_is_list($row)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                $key = self::key($row['key'] ?? null, $section . ':' . $i);
                if ($section !== 'category_options' && $section !== 'category_attributes') {
                    if (isset($seen[$key])) throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_DUPLICATE');
                    $seen[$key] = true;
                }
                $fields = match ($section) {
                    'categories' => ['key','title','alias','parent','state'],
                    'attributes' => ['key','title','code','type','state','values'],
                    'options' => ['key','title','code','state','values'],
                    'products' => ['key','title','alias','sku','price','old_price','badge_sale','stock','stock_mode','category','state'],
                    'bundles' => ['key','title','alias','sku','category','price','old_price','price_mode','discount_pct','badge_sale','badge_benefit','state','items'],
                    'links' => ['key','title','members','state'],
                    'category_attributes' => ['key','category','attribute','mode'],
                    'category_options' => ['key','category','option','mode'],
                };
                foreach (array_keys($row) as $field) {
                    if (!in_array($field, $fields, true)) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                    }
                }
                if ($section === 'bundles') {
                    $items = $row['items'] ?? null;
                    if (!is_array($items) || !array_is_list($items)
                        || count($items) < 1 || count($items) > 40) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                    }
                    $unique = [];
                    foreach ($items as $item) {
                        if (!is_array($item) || array_is_list($item)
                            || array_diff(array_keys($item), ['product','quantity'])) {
                            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                        }
                        $ref = self::key($item['product'] ?? null);
                        $qty = $item['quantity'] ?? null;
                        if (!is_int($qty) || $qty < 1 || $qty > 1000 || isset($unique[$ref])) {
                            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                        }
                        $unique[$ref] = true;
                    }
                }
                if (in_array($section, ['attributes','options'], true)) {
                    $values = $row['values'] ?? [];
                    if (!is_array($values) || !array_is_list($values) || count($values) > 250) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                    }
                    $codes = [];
                    foreach ($values as $value) {
                        if (!is_array($value) || array_is_list($value)) {
                            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                        }
                        foreach (array_keys($value) as $field) {
                            if (!in_array($field, ['code','title','state','color_hex'], true)) {
                                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                            }
                        }
                        $code = self::code($value['code'] ?? null);
                        self::name($value['title'] ?? null);
                        if (isset($codes[$code])) throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_DUPLICATE');
                        $codes[$code] = true;
                    }
                }
            }
        }
        // Product and bundle keys occupy one namespace (both reference products).
        $keys = array_column($doc['products'] ?? [], 'key');
        foreach ($doc['bundles'] ?? [] as $bundle) {
            if (in_array($bundle['key'], $keys, true)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_DUPLICATE');
            }
        }
        return $doc;
    }

    private static function key(mixed $raw, string $label = ''): string
    {
        if (!is_string($raw) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/D', $raw)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_KEY_INVALID: ' . $label);
        }
        return $raw;
    }

    private static function code(mixed $raw): string
    {
        if (!is_string($raw) || !preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $raw)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
        }
        return $raw;
    }

    private static function name(mixed $raw): string
    {
        if (!is_string($raw) || trim($raw) === '' || mb_strlen($raw) > 255
            || preg_match('/[\x00-\x1f<>]/u', $raw)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
        }
        return trim($raw);
    }

    private static function state(mixed $raw): int
    {
        if ($raw === 1 || $raw === true) return 1;
        if ($raw === 0 || $raw === false) return 0;
        throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
    }

    private static function money(mixed $raw): string
    {
        if (!is_string($raw) || !preg_match('/^(0|[1-9][0-9]{0,12})(?:\.[0-9]{1,2})?$/D', $raw)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
        }
        $parts = explode('.', $raw, 2);
        return $parts[0] . '.' . str_pad($parts[1] ?? '', 2, '0');
    }

    private static function qty(mixed $raw): int
    {
        if (!is_int($raw) || $raw < 0 || $raw > 4294967295) {
            throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
        }
        return $raw;
    }

    private static function lookup(DatabaseInterface $db, string $table, string $field, string $value): int
    {
        $q = $db->getQuery(true)->select('id')->from($db->quoteName($table))
            ->where($db->quoteName($field) . ' = ' . $db->quote($value));
        $db->setQuery($q);
        return (int) ($db->loadResult() ?? 0);
    }

    private static function mappedId(
        DatabaseInterface $db, string $source, string $kind, string $key
    ): int {
        $q = $db->getQuery(true)->select('local_id')
            ->from($db->quoteName('#__minimarket_import_entity_keys'))
            ->where('source_key = ' . $db->quote($source))
            ->where('entity_kind = ' . $db->quote($kind))
            ->where('external_key = ' . $db->quote($key));
        $db->setQuery($q);
        return (int) ($db->loadResult() ?? 0);
    }

    private static function localId(
        DatabaseInterface $db, string $source, string $kind, string $key
    ): int {
        $id = self::mappedId($db, $source, $kind, $key);
        if ($id < 1) throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_REFERENCE_INVALID: ' . $kind . ':' . $key);
        return $id;
    }

    private static function save(
        DatabaseInterface $db, string $table, int $id, array $values
    ): int {
        if ($id > 0) {
            $q = $db->getQuery(true)->update($db->quoteName($table));
            foreach ($values as $field => $value) {
                $q->set($db->quoteName($field) . ' = ' .
                    ($value === null ? 'NULL' : $db->quote((string) $value)));
            }
            $q->where('id = ' . $id);
        } else {
            $q = $db->getQuery(true)->insert($db->quoteName($table))
                ->columns($db->quoteName(array_keys($values)))
                ->values(implode(',', array_map(
                    static fn($v) => $v === null ? 'NULL' : $db->quote((string) $v),
                    array_values($values)
                )));
        }
        $db->setQuery($q);
        $db->execute();
        return $id > 0 ? $id : (int) $db->insertid();
    }

    private static function upsert(
        DatabaseInterface $db, string $source, string $kind, array $row, array $fields,
        string $naturalField, bool $adoptExisting
    ): array {
        $key = self::key($row['key'] ?? null, $kind);
        $table = self::TABLES[$kind];
        if ($kind === 'categories' || $kind === 'products') {
            $otherTable = $kind === 'categories' ? self::TABLES['products'] : self::TABLES['categories'];
            if (self::lookup($db, $otherTable, 'alias', (string) $fields['alias']) > 0) {
                throw new InvalidArgumentException('COM_MINIMARKET_IMPORT_ALIAS_CONFLICT');
            }
        }
        $id = self::mappedId($db, $source, $kind, $key);
        if ($id > 0) {
            $q = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName($table))
                ->where('id = ' . $id);
            $db->setQuery($q);
            if ((int) $db->loadResult() !== 1) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_MAPPING_BROKEN: ' . $kind . ':' . $key);
            }
        } else {
            // Never silently take ownership of an unrelated local record or
            // another supplier's shared code/alias.
            $candidate = self::lookup($db, $table, $naturalField, (string) $fields[$naturalField]);
            if ($candidate > 0 && !$adoptExisting) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_ADOPTION_REQUIRED: ' . $kind . ':' . $key);
            }
            $id = $candidate;
        }
        $new = $id === 0;
        $id = self::save($db, $table, $id, $fields);
        if ($id < 1) throw new \RuntimeException('COM_MINIMARKET_IMPORT_SAVE_FAILED');
        if (self::mappedId($db, $source, $kind, $key) === 0) {
            $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_import_entity_keys'))
                ->columns($db->quoteName(['source_key','entity_kind','external_key','local_id']))
                ->values(implode(',', [
                    $db->quote($source), $db->quote($kind), $db->quote($key), $id
                ]));
            $db->setQuery($q);
            $db->execute();
        }
        return [$id, $new];
    }

    /** Protect the technical product/bundle identity on re-import or adoption. */
    private static function assertProductKind(DatabaseInterface $db, string $source,
        array $row, string $expected): void
    {
        $id = self::mappedId($db, $source, 'products', self::key($row['key']));
        if (!$id) {
            $id = self::lookup($db, '#__minimarket_products', 'alias',
                self::code($row['alias'] ?? null));
        }
        if ($id) {
            $q = $db->getQuery(true)->select('product_type')
                ->from($db->quoteName('#__minimarket_products'))->where('id = ' . $id);
            $db->setQuery($q);
            if ((string) $db->loadResult() !== $expected) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
            }
        }
    }

    private static function shopType(): string
    {
        $value = (string) ComponentHelper::getParams('com_minimarket')->get('shop_type', 'physical');
        return in_array($value, ['physical', 'digital'], true) ? $value : 'physical';
    }

    /** Idempotent value import by scoped natural code; never remove unmentioned values. */
    private static function values(
        DatabaseInterface $db, string $table, string $owner, int $ownerId,
        array $values, bool $color
    ): void {
        foreach ($values as $i => $row) {
            $code = self::code($row['code'] ?? null);
            $title = self::name($row['title'] ?? null);
            $state = self::state($row['state'] ?? 1);
            $hex = (string) ($row['color_hex'] ?? '');
            if ($hex !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/D', $hex)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
            }
            if (!$color && $hex !== '') {
                throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
            }
            $q = $db->getQuery(true)->select('id')->from($db->quoteName($table))
                ->where($db->quoteName($owner) . ' = ' . $ownerId)
                ->where('code = ' . $db->quote($code));
            $db->setQuery($q);
            $id = (int) ($db->loadResult() ?? 0);
            self::save($db, $table, $id, [
                $owner => $ownerId, 'title' => $title, 'code' => $code,
                'state' => $state, 'sort_order' => $i,
                'color_hex' => strtoupper($hex),
                ...($table === '#__minimarket_attribute_values' && $id === 0 ? ['sample_image' => ''] : [])
            ]);
        }
    }

    private static function assertTree(DatabaseInterface $db): void
    {
        $q = $db->getQuery(true)->select(['id','parent_id'])
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q);
        $parents = [];
        foreach ((array) $db->loadAssocList() as $r) {
            $parents[(int) $r['id']] = (int) $r['parent_id'];
        }
        foreach ($parents as $id => $_parent) {
            $seen = [];
            while ($id > 0) {
                if (isset($seen[$id]) || !isset($parents[$id])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_CATEGORY_CYCLE');
                }
                $seen[$id] = true;
                $id = $parents[$id];
            }
        }
    }

    private static function link(DatabaseInterface $db, int $groupId, int $productId): void
    {
        $q = $db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_product_group_members'))
            ->where('group_id = ' . $groupId)->where('product_id = ' . $productId);
        $db->setQuery($q);
        if ((int) $db->loadResult() > 0) return;
        $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_product_group_members'))
            ->columns($db->quoteName(['group_id','product_id']))
            ->values($groupId . ',' . $productId);
        $db->setQuery($q);
        $db->execute();
    }

    /** Same transaction for preview and apply; preview never commits. */
    public static function run(DatabaseInterface $db, array $doc, bool $commit = false): array
    {
        // Validate the document once more (for callers that do not use decode).
        $doc = self::decode(json_encode($doc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $source = $doc['source'];
        $adoptExisting = (bool) ($doc['adopt_existing'] ?? false);
        $counts = [];
        foreach (self::SECTIONS as $section) {
            $counts[$section] = ['created' => 0, 'updated' => 0, 'linked' => 0];
        }
        $db->transactionStart();
        try {
            foreach ($doc['categories'] ?? [] as $row) {
                $title = self::name($row['title'] ?? null);
                $alias = self::code($row['alias'] ?? null);
                [, $new] = self::upsert($db, $source, 'categories', $row, [
                    'title' => $title, 'alias' => $alias,
                    'state' => self::state($row['state'] ?? 0)
                ], 'alias', $adoptExisting);
                ++$counts['categories'][$new ? 'created' : 'updated'];
            }
            // Parents are resolved only after all category IDs exist.
            foreach ($doc['categories'] ?? [] as $row) {
                if (!array_key_exists('parent', $row)) continue;
                $childId = self::localId($db, $source, 'categories', self::key($row['key']));
                $parent = $row['parent'] ?? null;
                $parentId = $parent === null ? 0 : self::localId(
                    $db, $source, 'categories', self::key($parent)
                );
                if ($parentId === $childId) throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_CATEGORY_CYCLE');
                $q = $db->getQuery(true)->update($db->quoteName('#__minimarket_categories'))
                    ->set('parent_id = ' . $parentId)->where('id = ' . $childId);
                $db->setQuery($q);
                $db->execute();
            }
            self::assertTree($db);
            foreach ($doc['attributes'] ?? [] as $row) {
                $type = (string) ($row['type'] ?? 'text');
                if (!in_array($type, ['text','number','editor','boolean','document','color'], true)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                $code = self::code($row['code'] ?? null);
                $existingId = self::mappedId($db, $source, 'attributes', self::key($row['key']));
                if ($existingId === 0) {
                    $existingId = self::lookup($db, '#__minimarket_attributes', 'code', $code);
                }
                if ($existingId > 0) {
                    $q = $db->getQuery(true)->select('input_type')
                        ->from($db->quoteName('#__minimarket_attributes'))
                        ->where('id = ' . $existingId);
                    $db->setQuery($q);
                    if ((string) $db->loadResult() !== $type) {
                        throw new InvalidArgumentException('COM_MINIMARKET_IMPORT_TYPE_CHANGE');
                    }
                }
                [$id, $new] = self::upsert($db, $source, 'attributes', $row, [
                    'title' => self::name($row['title'] ?? null),
                    'code' => $code, 'input_type' => $type,
                    'state' => self::state($row['state'] ?? 0)
                ], 'code', $adoptExisting);
                ++$counts['attributes'][$new ? 'created' : 'updated'];
                if (($row['values'] ?? []) && $type !== 'color') {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                self::values($db, '#__minimarket_attribute_values', 'attribute_id',
                    $id, $row['values'] ?? [], $type === 'color');
            }
            foreach ($doc['options'] ?? [] as $row) {
                [$id, $new] = self::upsert($db, $source, 'options', $row, [
                    'title' => self::name($row['title'] ?? null),
                    'code' => self::code($row['code'] ?? null),
                    'state' => self::state($row['state'] ?? 0)
                ], 'code', $adoptExisting);
                ++$counts['options'][$new ? 'created' : 'updated'];
                self::values($db, '#__minimarket_order_option_values', 'option_id',
                    $id, $row['values'] ?? [], true);
            }
            foreach ($doc['products'] ?? [] as $row) {
                $sku = (string) ($row['sku'] ?? '');
                if ($sku === '' || mb_strlen($sku) > 100 || preg_match('/[\x00-\x1f<>]/u', $sku)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                $stockMode = $row['stock_mode'] ?? 1;
                if ($stockMode !== 0 && $stockMode !== 1) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                $categoryId = self::localId($db, $source, 'categories', self::key($row['category'] ?? null));
                self::assertProductKind($db, $source, $row, 'simple');
                $price = self::money($row['price'] ?? null);
                $oldPrice = array_key_exists('old_price', $row) ? self::money($row['old_price']) : null;
                $sale = array_key_exists('badge_sale', $row) ? self::state($row['badge_sale']) : 0;
                if ($sale && ($oldPrice === null || (float) $oldPrice <= (float) $price)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                [, $new] = self::upsert($db, $source, 'products', $row, [
                    'title' => self::name($row['title'] ?? null),
                    'alias' => self::code($row['alias'] ?? null),
                    'category_id' => $categoryId,
                    'sku' => $sku, 'price' => $price,
                    'product_type' => 'simple', 'delivery_type' => self::shopType(),
                    ...(array_key_exists('old_price', $row) ? ['old_price' => $oldPrice] : []),
                    ...(array_key_exists('badge_sale', $row) ? ['badge_sale' => $sale] : []),
                    'stock' => self::qty($row['stock'] ?? 0),
                    'option_stock_mode' => $stockMode,
                    'state' => self::state($row['state'] ?? 0)
                ], 'alias', $adoptExisting);
                ++$counts['products'][$new ? 'created' : 'updated'];
            }
            // Create all bundle identities before resolving components, so
            // references may point to a later bundle or a nested bundle.
            foreach ($doc['bundles'] ?? [] as $row) {
                $categoryId = self::localId($db, $source, 'categories',
                    self::key($row['category'] ?? null));
                $sku = trim((string) ($row['sku'] ?? ''));
                if ($sku === '' || mb_strlen($sku) > 100 || preg_match('/[\x00-\x1f<>]/u', $sku)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                $mode = $row['price_mode'] ?? 'fixed';
                $discount = (string) ($row['discount_pct'] ?? '0');
                if (!in_array($mode, ['fixed','discount'], true)
                    || !preg_match('/^(?:[0-9]{1,2}|100)(?:\.[0-9]{1,2})?$/D', $discount)
                    || (float) $discount > 100) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                $price = self::money($row['price'] ?? null);
                $oldPrice = array_key_exists('old_price', $row) ? self::money($row['old_price']) : null;
                $sale = self::state($row['badge_sale'] ?? 0);
                $benefit = self::state($row['badge_benefit'] ?? 0);
                if (($mode === 'discount' && ($oldPrice !== null || $sale))
                    || ($mode === 'fixed' && $benefit)
                    || ($sale && ($oldPrice === null || (float) $oldPrice <= (float) $price))) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                self::assertProductKind($db, $source, $row, 'bundle');
                [, $new] = self::upsert($db, $source, 'products', $row, [
                    'title' => self::name($row['title'] ?? null),
                    'alias' => self::code($row['alias'] ?? null),
                    'sku' => $sku, 'category_id' => $categoryId, 'state' => self::state($row['state'] ?? 0),
                    'product_type' => 'bundle', 'delivery_type' => self::shopType(),
                    'price' => $price, 'old_price' => $oldPrice, 'stock' => 0,
                    'option_stock_mode' => 0, 'bundle_price_mode' => $mode,
                    'bundle_discount_pct' => number_format((float) $discount, 2, '.', ''),
                    'badge_sale' => $sale, 'badge_benefit' => $benefit,
                ], 'alias', $adoptExisting);
                ++$counts['bundles'][$new ? 'created' : 'updated'];
            }
            foreach ($doc['bundles'] ?? [] as $row) {
                $id = self::localId($db, $source, 'products', self::key($row['key']));
                $items = [];
                foreach ($row['items'] as $item) {
                    $componentId = self::localId($db, $source, 'products',
                        self::key($item['product']));
                    $items[] = ['component' => 'product:' . $componentId,
                        'quantity' => $item['quantity']];
                }
                $resolved = BundleItems::validate($db, $id, BundleItems::normalize($items));
                BundleItems::replace($db, $id, $resolved);
                $counts['bundles']['linked'] += count($resolved);
            }
            foreach ($doc['links'] ?? [] as $row) {
                [$id, $new] = self::upsert($db, $source, 'links', $row, [
                    'title' => self::name($row['title'] ?? null),
                    'state' => self::state($row['state'] ?? 1),
                    'type' => 'similar'
                ], 'title', $adoptExisting);
                ++$counts['links'][$new ? 'created' : 'updated'];
                $members = $row['members'] ?? [];
                if (!is_array($members) || !array_is_list($members)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                }
                foreach ($members as $key) {
                    $productId = self::localId($db, $source, 'products', self::key($key));
                    self::link($db, $id, $productId);
                    ++$counts['links']['linked'];
                }
            }
            foreach (['category_attributes' => ['attributes','attribute_id','#__minimarket_category_attributes'],
                      'category_options' => ['options','option_id','#__minimarket_category_order_options']] as $section => $config) {
                [$kind, $field, $table] = $config;
                foreach ($doc[$section] ?? [] as $row) {
                    $categoryId = self::localId($db, $source, 'categories',
                        self::key($row['category'] ?? null));
                    $entityKey = $kind === 'attributes' ? 'attribute' : 'option';
                    $entityId = self::localId($db, $source, $kind,
                        self::key($row[$entityKey] ?? null));
                    $mode = $row['mode'] ?? null;
                    if (!is_string($mode) || !array_key_exists($mode, self::MODES)) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
                    }
                    $q = $db->getQuery(true)->delete($db->quoteName($table))
                        ->where('category_id = ' . $categoryId)
                        ->where($db->quoteName($field) . ' = ' . $entityId);
                    $db->setQuery($q);
                    $db->execute();
                    if ($mode !== 'inherit') {
                        $values = $section === 'category_attributes'
                            ? ['category_id' => $categoryId, 'attribute_id' => $entityId,
                               'enabled' => self::MODES[$mode] > 0 ? 1 : 0,
                               'required' => self::MODES[$mode] === 2 ? 1 : 0]
                            : ['category_id' => $categoryId, 'option_id' => $entityId,
                               'mode' => self::MODES[$mode]];
                        $q = $db->getQuery(true)->insert($db->quoteName($table))
                            ->columns($db->quoteName(array_keys($values)))
                            ->values(implode(',', array_values($values)));
                        $db->setQuery($q);
                        $db->execute();
                    }
                    ++$counts[$section]['linked'];
                }
            }
            if ($commit) $db->transactionCommit();
            else $db->transactionRollback();
        } catch (\Throwable $error) {
            $db->transactionRollback();
            throw $error;
        }
        return $counts;
    }
}
