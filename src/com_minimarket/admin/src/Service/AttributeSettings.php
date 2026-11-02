<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/** Shared contract for catalog attribute metadata and numeric presets. */
final class AttributeSettings
{
    // Legacy kinds remain supported for existing stored records only.
    public const TYPES = ['text','number','editor','boolean','document','color','list','textarea','date','url'];
    public const NEW_TYPES = ['text','number','editor','boolean','document','color'];
    public const INPUTS = [
        'list' => ['auto','dropdown','radio','checkboxes'],
        'text' => ['auto','presets'],
        'editor' => ['auto'],
        'document' => ['auto'],
        'color' => ['auto','dropdown','radio','checkboxes'],
        'number' => ['auto','number','presets'],
        'textarea' => ['auto'],
        'date' => ['auto'],
        'boolean' => ['auto'],
        'url' => ['auto'],
    ];
    public const FILTERS = [
        'list' => ['none','checkboxes','radio','dropdown'],
        'text' => ['none','text','values','checkboxes','dropdown'],
        'editor' => ['none'],
        'document' => ['none'],
        'color' => ['none','swatches','checkboxes','radio','dropdown'],
        'number' => ['none','range','slider','checkboxes','dropdown'],
        'textarea' => ['none'],
        'date' => ['none','date_range'],
        'boolean' => ['none','boolean'],
        'url' => ['none'],
    ];

    /** @return string[] Normalized decimals (max 4 fractional digits). */
    public static function numericOptions(string $csv): array
    {
        if (mb_strlen($csv) > 1024) {
            throw new \InvalidArgumentException('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID');
        }
        $result = [];
        foreach (preg_split('/[,;\r\n]+/', trim($csv)) as $number) {
            $number = trim($number);
            if ($number === '') {
                continue;
            }
            if (!self::decimal($number)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID');
            }
            $canonical = self::canonicalDecimal($number);
            if (isset($result[$canonical])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID');
            }
            $result[$canonical] = $canonical;
        }
        return array_values($result);
    }

    public static function decimal(string $value): bool
    {
        return (bool) preg_match('/^-?(?:0|[1-9]\d{0,13})(?:\.\d{1,4})?$/D', $value);
    }

    public static function canonicalDecimal(string $s): string
    {
        $s = ltrim($s, '+');
        $parts = explode('.', $s, 2);
        $int = $parts[0];
        $fraction = isset($parts[1]) ? rtrim($parts[1], '0') : '';
        if ((float) $s === 0.0) {
            return '0';
        }
        return $fraction === '' ? $int : $int . '.' . $fraction;
    }

    /**
     * Validate and normalize metadata on the Joomla Table before insert/update.
     * @return null|string Translation key of an error.
     */
    public static function normalize(object $a): ?string
    {
        $type = (string) ($a->input_type ?? '');
        if (!isset(self::INPUTS[$type])) {
            return 'COM_MINIMARKET_ATTRIBUTE_TYPE_INVALID';
        }
        $a->input_mode = (string) ($a->input_mode ?? 'auto');
        $a->filter_mode = (string) ($a->filter_mode ?? 'none');
        $a->filter_multiple = (int) ($a->filter_multiple ?? 0);
        if (!in_array($a->input_mode, self::INPUTS[$type], true)
            || !in_array($a->filter_mode, self::FILTERS[$type], true)
            || !in_array($a->filter_multiple, [0,1], true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
        }
        if (in_array($type, ['list','color'], true)) {
            if (($a->input_mode === 'radio' && (int) $a->is_multiple !== 0)
                || ($a->input_mode === 'checkboxes' && (int) $a->is_multiple !== 1)) {
                return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
            }
        }
        if ((int) ($a->is_filterable ?? 0) === 1
            && in_array($type, ['editor','document','textarea','url'], true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
        }
        if ((int) ($a->is_filterable ?? 0) === 0) {
            $a->filter_mode = 'none';
            $a->filter_multiple = 0;
        } elseif (in_array($a->filter_mode, ['checkboxes','swatches'], true)) {
            $a->filter_multiple = 1;
        } elseif (!in_array($a->filter_mode, ['values','dropdown'], true)) {
            $a->filter_multiple = 0;
        }

        $a->text_options = trim((string) ($a->text_options ?? ''));
        if ($type === 'text') {
            try {
                $textPresets = TextPresetRows::values($a->text_options);
            } catch (\InvalidArgumentException $error) {
                return 'COM_MINIMARKET_TEXT_PRESETS_INVALID';
            }
            if ($a->input_mode === 'presets' && !$textPresets) {
                return 'COM_MINIMARKET_TEXT_PRESETS_INVALID';
            }
            $a->text_options = json_encode($textPresets, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } else {
            // Keep previously configured text values when changing a legacy
            // input type back and forth, but never apply them to other types.
            $a->text_options = $a->text_options === '' ? null : $a->text_options;
        }

        $a->number_unit = trim((string) ($a->number_unit ?? ''));
        $a->number_options = trim((string) ($a->number_options ?? ''));
        $a->number_min = self::optionalDecimal($a->number_min ?? null);
        $a->number_max = self::optionalDecimal($a->number_max ?? null);
        $a->number_step = self::optionalDecimal($a->number_step ?? null);
        if ($type !== 'number') {
            $a->number_unit = '';
            $a->number_options = '';
            $a->number_min = null;
            $a->number_max = null;
            $a->number_step = null;
            return null;
        }
        if (mb_strlen($a->number_unit) > 32 || preg_match('/[<>\x00-\x1f]/', $a->number_unit)) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        foreach (['number_min','number_max','number_step'] as $name) {
            if ($a->$name !== null && !self::decimal((string) $a->$name)) {
                return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
            }
        }
        if ($a->number_min !== null && $a->number_max !== null
            && (float) $a->number_min > (float) $a->number_max) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        if ($a->number_step !== null && (float) $a->number_step <= 0) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        try {
            $numbers = self::numericOptions($a->number_options);
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
        if ($a->input_mode === 'presets' && $numbers === []) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        foreach ($numbers as $n) {
            if (!self::inRange($n, $a->number_min, $a->number_max)) {
                return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
            }
        }
        $a->number_options = implode(',', $numbers);
        return null;
    }

    private static function optionalDecimal(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === ''
            ? null : trim((string) $value);
    }

    public static function inRange(string $number, ?string $min, ?string $max): bool
    {
        return ($min === null || (float) $number >= (float) $min)
            && ($max === null || (float) $number <= (float) $max);
    }
}
