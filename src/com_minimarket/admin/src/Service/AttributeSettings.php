<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

/** Canonical type/source/validation contract for all product attributes. */
final class AttributeSettings
{
    public const TYPES = ['text','number','editor','boolean','document','color'];
    // Visual presentation, not selection cardinality. Legacy aliases remain
    // accepted only to permit editing older saved attributes before migration.
    public const FILTERS = [
        'text' => ['none','dropdown','checkboxes','values','text'],
        'number' => ['none','range','slider','dropdown','checkboxes'],
        'editor' => ['none'],
        'boolean' => ['none','dropdown','checkboxes','boolean'],
        'document' => ['none'],
        'color' => ['none','dropdown','checkboxes','swatches','radio'],
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
        $mode = (string) ($attribute->filter_mode ?? 'none');
        $mode = match($mode) {
            'values','swatches','radio','boolean' => 'checkboxes',
            'text' => $type === 'text' ? 'dropdown' : 'text',
            default => $mode,
        };
        $attribute->filter_mode = $mode;
        $attribute->filter_multiple = (int) ($attribute->filter_multiple ?? 0);
        $attribute->filter_search = (int) ($attribute->filter_search ?? 0);
        if (!in_array($mode, self::FILTERS[$type], true)
            || !in_array($attribute->filter_multiple, [0,1], true)
            || !in_array($attribute->filter_search, [0,1], true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
        }
        $attribute->is_filterable = $mode === 'none' ? 0 : 1;
        if ($mode === 'none' || in_array($mode, ['range','slider'], true)
            || $type === 'boolean') {
            $attribute->filter_multiple = 0;
        }
        // Search is a separate presentation option for textual choices.
        if (!in_array($mode, ['checkboxes','dropdown'], true)
            || !in_array($type, ['text','color'], true)) {
            $attribute->filter_search = 0;
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
