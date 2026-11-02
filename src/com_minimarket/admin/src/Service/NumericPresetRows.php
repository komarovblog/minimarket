<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/** Typed numeric presets rendered in a standard Joomla draggable Subform. */
final class NumericPresetRows
{
    public const MAX_ROWS = 500;

    /** @return array<string,array{number:string}> */
    public static function formRows(string $csv): array
    {
        $rows = [];
        foreach (AttributeSettings::numericOptions($csv) as $number) {
            $rows['row' . count($rows)] = ['number' => $number];
        }
        return $rows;
    }

    /** @return string Comma-separated validated decimals, in dragged order. */
    public static function normalize(mixed $rows): string
    {
        if ($rows === null || $rows === '') {
            return '';
        }
        if (is_string($rows)) {
            try {
                $rows = json_decode($rows, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \InvalidArgumentException('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID');
            }
        }
        if (!is_array($rows) || count($rows) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID');
        }
        $values = [];
        foreach ($rows as $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }
            if (!is_array($row) || !is_scalar($row['number'] ?? '')) {
                throw new \InvalidArgumentException('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID');
            }
            $number = trim((string) $row['number']);
            if ($number === '') {
                continue;
            }
            $values[] = $number;
        }
        return implode(',', AttributeSettings::numericOptions(implode(',', $values)));
    }
}
