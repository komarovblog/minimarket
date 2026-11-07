<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * One canonical attribute contract. Values are either free text/numeric
 * scalars (and ordered free multi-values) or stable dictionary value IDs.
 */
final class AttributeSettings
{
    public const TYPES = ['text','number','editor','boolean','document','color'];
    public const NEW_TYPES = self::TYPES;
    public const FILTERS = [
        'text' => ['none','text','values','checkboxes','radio','dropdown'],
        'number' => ['none','range','slider','values','checkboxes','radio','dropdown'],
        'editor' => ['none'],
        'document' => ['none'],
        'boolean' => ['none','boolean'],
        'color' => ['none','swatches','checkboxes','radio','dropdown','values']
    ];

    public static function decimal(string $value): bool
    {
        return (bool) preg_match('/^-?(?:0|[1-9]\d{0,13})(?:\.\d{1,4})?$/D', $value);
    }

    public static function canonicalDecimal(string $s): string
    {
        $s=ltrim($s,'+');$parts=explode('.',$s,2);
        $fraction=isset($parts[1])?rtrim($parts[1],'0'):'';
        if ((float)$s===0.0) return '0';
        return $fraction===''?$parts[0]:$parts[0].'.'.$fraction;
    }

    private static function optionalDecimal(mixed $value): ?string
    {
        return $value===null || trim((string)$value)===''
            ? null : trim((string)$value);
    }

    public static function inRange(string $number, ?string $min, ?string $max): bool
    {
        return self::decimal($number)
            && ($min===null || (float)$number >= (float)$min)
            && ($max===null || (float)$number <= (float)$max);
    }

    /** Validate metadata before Joomla inserts/updates the attribute. */
    public static function normalize(object $a): ?string
    {
        $type=(string)($a->input_type??'');
        if (!in_array($type,self::TYPES,true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_TYPE_INVALID';
        }
        $a->value_source=(string)($a->value_source??'manual');
        $a->color_display=(string)($a->color_display??'both');
        $a->is_multiple=(int)($a->is_multiple??0);
        $a->filter_multiple=(int)($a->filter_multiple??0);
        $a->is_filterable=(int)($a->is_filterable??0);
        $a->filter_mode=(string)($a->filter_mode??'none');
        if (!in_array($a->value_source,['manual','preset'],true)
            || !in_array($a->color_display,['both','swatch'],true)
            || !in_array($a->is_multiple,[0,1],true)
            || !in_array($a->filter_multiple,[0,1],true)
            || !in_array($a->is_filterable,[0,1],true)) {
            return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
        }
        if ($type==='boolean') {
            $a->value_source='manual';
            $a->is_multiple=0;
        }
        if (!in_array($a->filter_mode,self::FILTERS[$type],true)
            || ($a->is_filterable===1 && in_array($type,['editor','document'],true))) {
            return 'COM_MINIMARKET_ATTRIBUTE_PRESENTATION_INVALID';
        }
        if ($a->is_filterable===0) {
            $a->filter_mode='none';
            $a->filter_multiple=0;
        } elseif (in_array($a->filter_mode,['checkboxes','swatches'],true)) {
            $a->filter_multiple=1;
        } elseif (!in_array($a->filter_mode,['dropdown','values'],true)) {
            $a->filter_multiple=0;
        }
        $a->number_unit=trim((string)($a->number_unit??''));
        $a->number_min=self::optionalDecimal($a->number_min??null);
        $a->number_max=self::optionalDecimal($a->number_max??null);
        $a->number_step=self::optionalDecimal($a->number_step??null);
        if ($type!=='number') {
            $a->number_unit='';
            $a->number_min=null;
            $a->number_max=null;
            $a->number_step=null;
            return null;
        }
        if (mb_strlen($a->number_unit)>32
            || preg_match('/[<>\x00-\x1f]/',$a->number_unit)) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        foreach (['number_min','number_max','number_step'] as $key) {
            if ($a->$key!==null && !self::decimal((string)$a->$key)) {
                return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
            }
        }
        if ($a->number_min!==null && $a->number_max!==null
            && (float)$a->number_min>(float)$a->number_max) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        if ($a->number_step!==null && (float)$a->number_step<=0) {
            return 'COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID';
        }
        return null;
    }
}
