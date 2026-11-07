<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

/** Canonical type/source/validation contract for all product attributes. */
final class AttributeSettings
{
    public const TYPES = ['text','number','editor','boolean','document','color'];
    public const FILTERS = [
        'text' => ['none','text','values','checkboxes','dropdown'],
        'number' => ['none','range','slider','checkboxes','dropdown'],
        'editor' => ['none'],
        'boolean' => ['none','boolean'],
        'document' => ['none'],
        'color' => ['none','swatches','checkboxes','radio','dropdown'],
    ];

    public static function decimal(string $value): bool
    {
        return (bool) preg_match('/^-?(?:0|[1-9]\d{0,13})(?:\.\d{1,4})?$/D', $value);
    }

    public static function canonicalDecimal(string $value): string
    {
        $value = ltrim($value, '+');
        $parts = explode('.', $value, 2);
        $fraction = isset($parts[1]) ? rtrim($parts[1], '0') : '';
        if ((float) $value === 0.0) return '0';
        return $fraction === '' ? $parts[0] : $parts[0] . '.' . $fraction;
    }

    public static function normalize(object $attribute): ?string
    {
        $type = (string) ($attribute->input_type ?? '');
        if (!in_array($type, self::TYPES, true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_TYPE_INVALID';
        }
        $source = (string) ($attribute->value_source ?? 'manual');
        if (!in_array($source, ['manual','preset'], true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
        }
        if ($type === 'boolean') {
            $source = 'manual';
            $attribute->is_multiple = 0;
        }
        $attribute->value_source = $source;
        $attribute->color_display = (string) ($attribute->color_display ?? 'both');
        if (!in_array($attribute->color_display, ['both','swatch'], true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
        }
        $attribute->filter_mode = (string) ($attribute->filter_mode ?? 'none');
        $attribute->filter_multiple = (int) ($attribute->filter_multiple ?? 0);
        if (!in_array($attribute->filter_mode, self::FILTERS[$type], true)
            || !in_array($attribute->filter_multiple, [0,1], true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
        }
        if ((int) ($attribute->is_filterable ?? 0) === 0) {
            $attribute->filter_mode = 'none';
            $attribute->filter_multiple = 0;
        } elseif (in_array($attribute->filter_mode, ['checkboxes','swatches'], true)) {
            $attribute->filter_multiple = 1;
        } elseif (!in_array($attribute->filter_mode, ['values','dropdown'], true)) {
            $attribute->filter_multiple = 0;
        }
        $attribute->number_unit = trim((string) ($attribute->number_unit ?? ''));
        foreach (['number_min','number_max','number_step'] as $name) {
            $raw = $attribute->$name ?? null;
            $attribute->$name = $raw === null || trim((string) $raw) === ''
                ? null : trim((string) $raw);
        }
        if ($type !== 'number') {
            $attribute->number_unit = '';
            $attribute->number_min = null;
            $attribute->number_max = null;
            $attribute->number_step = null;
            return null;
        }
        if (mb_strlen($attribute->number_unit) > 32
            || preg_match('/[<>\x00-\x1f]/', $attribute->number_unit)) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        foreach (['number_min','number_max','number_step'] as $name) {
            if ($attribute->$name !== null && !self::decimal($attribute->$name)) {
                return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
            }
        }
        if ($attribute->number_min !== null && $attribute->number_max !== null
            && (float) $attribute->number_min > (float) $attribute->number_max) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        if ($attribute->number_step !== null && (float) $attribute->number_step <= 0) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        return null;
    }

    public static function inRange(string $value, ?string $min, ?string $max): bool
    {
        return ($min === null || (float) $value >= (float) $min)
            && ($max === null || (float) $value <= (float) $max);
    }
}
