<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Public product characteristics. Reads only a product already authorized by
 * CatalogRepository::getProduct(); never exposes unpublished/unassigned data.
 * Product-level selections are not variant combinations.
 */
final class ProductAttributeCatalog
{
    /**
     * @param int[] $ancestors Published category IDs, root first, leaf last.
     * @return array<int,array{title:string,type:string,unit:string,values:array<int,array{text:string,hex:string,image:string}>}>
     */
    public static function load(DatabaseInterface $db, int $productId, array $ancestors): array
    {
        if ($productId < 1 || $ancestors === []) {
            return [];
        }
        $ids = implode(',', array_map('intval', $ancestors));
        $q = $db->getQuery(true)
            ->select(['category_id', 'attribute_id', 'enabled'])
            ->from($db->quoteName('#__minimarket_category_attributes'))
            ->where($db->quoteName('category_id') . ' IN (' . $ids . ')');
        $db->setQuery($q);
        $byCategory = [];
        foreach ((array) $db->loadObjectList() as $rule) {
            $byCategory[(int) $rule->category_id][(int) $rule->attribute_id] =
                (int) $rule->enabled === 1;
        }
        $enabled = [];
        foreach ($ancestors as $categoryId) {
            foreach ($byCategory[(int) $categoryId] ?? [] as $attributeId => $active) {
                $enabled[$attributeId] = $active;
            }
        }
        $allowed = array_keys(array_filter($enabled));
        if ($allowed === []) {
            return [];
        }
        $allowSql = implode(',', array_map('intval', $allowed));
        $q = $db->getQuery(true)
            ->select(['id', 'title', 'input_type', 'number_unit', 'value_source', 'color_display'])
            ->from($db->quoteName('#__minimarket_attributes'))
            ->where($db->quoteName('id') . ' IN (' . $allowSql . ')')
            ->where($db->quoteName('state') . ' = 1')
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $attributes = [];
        foreach ((array) $db->loadObjectList() as $row) {
            $id = (int) $row->id;
            if (!isset($enabled[$id]) || !$enabled[$id]) {
                continue;
            }
            $type = (string) $row->input_type;
            $attributes[$id] = [
                'title' => (string) $row->title,
                'type' => $type,
                'unit' => (string) ($row->number_unit ?? ''),
                'source' => (string) ($row->value_source ?? 'manual'),
                'color_display' => (string) ($row->color_display ?? 'both'),
                'values' => [],
            ];
        }
        if ($attributes === []) {
            return [];
        }

        $attrSql = implode(',', array_map('intval', array_keys($attributes)));
        $q = $db->getQuery(true)
            ->select(['attribute_id', 'value_text', 'value_number', 'value_boolean'])
            ->from($db->quoteName('#__minimarket_product_attribute_scalars'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->where($db->quoteName('attribute_id') . ' IN (' . $attrSql . ')');
        $db->setQuery($q);
        foreach ((array) $db->loadObjectList() as $scalar) {
            $id = (int) $scalar->attribute_id;
            if (!isset($attributes[$id])) continue;
            $type = $attributes[$id]['type'];
            $value = $scalar->value_text ?? $scalar->value_number ?? $scalar->value_boolean ?? null;
            if ($value === null || (string) $value === '') continue;
            $attributes[$id]['values'][] = [
                'text' => (string) $value,
                'hex' => $type === 'color' && preg_match('/^#[a-fA-F0-9]{6}$/D', (string) $value)
                    ? (string) $value : '',
                'image' => '',
            ];
        }

        // Dictionary values are joined to the product's chosen IDs; no
        // accidental display of other products' values or unpublished choices.
        $q = $db->getQuery(true)
            ->select(['o.attribute_id', 'v.title', 'v.color_hex', 'v.sample_image', 'v.value_payload'])
            ->from($db->quoteName('#__minimarket_product_attribute_options', 'o'))
            ->join('INNER', $db->quoteName('#__minimarket_attribute_values', 'v')
                . ' ON ' . $db->quoteName('v.id') . ' = ' . $db->quoteName('o.value_id')
                . ' AND ' . $db->quoteName('v.attribute_id') . ' = ' . $db->quoteName('o.attribute_id'))
            ->where($db->quoteName('o.product_id') . ' = ' . $productId)
            ->where($db->quoteName('o.attribute_id') . ' IN (' . $attrSql . ')')
            ->where($db->quoteName('v.state') . ' = 1')
            ->order('v.sort_order ASC, v.id ASC');
        $db->setQuery($q);
        foreach ((array) $db->loadObjectList() as $option) {
            $id = (int) $option->attribute_id;
            if (!isset($attributes[$id])) continue;
            $type = $attributes[$id]['type'];
            if ($attributes[$id]['source'] !== 'preset') continue;
            $hex = (string) ($option->color_hex ?? '');
            $payload = (string) ($option->value_payload ?? '');
            $displayText = in_array($type, ['number','document','editor'], true)
                ? ($payload !== '' ? $payload : (string) $option->title)
                : (string) $option->title;
            $attributes[$id]['values'][] = [
                'text' => $displayText,
                'hex' => preg_match('/^#[0-9a-fA-F]{6}$/D', $hex) ? $hex : '',
                'image' => self::safeImage((string) ($option->sample_image ?? '')),
            ];
        }

        // Ordered, manually entered multiple values (including numeric data).
        if (array_filter($attributes, static fn(array $attr): bool =>
            $attr['source'] === 'manual')) {
        $q = $db->getQuery(true)
            ->select(['attribute_id', 'value_text'])
            ->from($db->quoteName('#__minimarket_product_attribute_free_values'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->where($db->quoteName('attribute_id') . ' IN (' . $attrSql . ')')
            ->order('attribute_id ASC, sort_order ASC, id ASC');
        $db->setQuery($q);
        foreach ((array) $db->loadObjectList() as $choice) {
            $id = (int) $choice->attribute_id;
            if (!isset($attributes[$id]) || $attributes[$id]['source'] !== 'manual') continue;
            $text = (string) $choice->value_text;
            if ($text === '') continue;
            $attributes[$id]['values'][] = [
                'text' => $text,
                'hex' => $attributes[$id]['type'] === 'color'
                    && preg_match('/^#[a-fA-F0-9]{6}$/D', $text) ? $text : '',
                'image' => '',
            ];
        }
        }
        // Saved scalar "0" means No and must not be discarded by empty().
        return array_filter($attributes, static fn (array $item): bool => $item['values'] !== []);
    }

    /** Only site-local image previews; media paths never include Joomla adapters. */
    public static function safeImage(string $path): string
    {
        return strlen($path) <= 1024 && !str_contains($path, '..')
            && !str_contains($path, '//') && preg_match(
                '~^images/[a-zA-Z0-9_./ -]+\.(?:jpg|jpeg|png|gif|webp|avif)$~iD', $path
            ) ? $path : '';
    }

    /** Restrict document links to local, explicitly supported extensions. */
    public static function safeDocument(string $path): string
    {
        return strlen($path) <= 1024 && !str_contains($path, '..')
            && !str_contains($path, '//') && preg_match(
                '~^(?:images|files|media)/[a-zA-Z0-9_./ -]+\.(?:pdf|doc|docx|xls|xlsx|txt|md|csv|zip|jpg|jpeg|png|webp)$~iD',
                $path
            ) ? $path : '';
    }

    public static function safeUrl(string $url): string
    {
        if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) return '';
        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? $url : '';
    }
}
