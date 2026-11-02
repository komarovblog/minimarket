<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/** Optional predefined TEXT values; store strings, never dictionary value IDs. */
final class TextPresetRows
{
    public const MAX_ROWS = 200;

    /** @return string[] */
    public static function values(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
        }
        return self::normalize($decoded);
    }

    /** @return string[] */
    public static function normalize(mixed $input): array
    {
        if ($input === '' || $input === null) {
            return [];
        }
        if (is_string($input)) {
            return self::values($input);
        }
        if (!is_array($input) || count($input) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
        }
        $results = [];
        $seen = [];
        foreach ($input as $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }
            // Accept both data representations: field rows or decoded strings.
            $value = is_array($row) ? ($row['value'] ?? null) : $row;
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
            }
            $value = trim((string) $value);
            if ($value === '' || mb_strlen($value) > 255
                || preg_match('/[\x00-\x1f<>]/u', $value)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
            }
            $key = mb_strtolower($value);
            if (isset($seen[$key])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
            }
            $seen[$key] = true;
            $results[] = $value;
        }
        return $results;
    }

    public static function encode(mixed $rows): string
    {
        if (is_string($rows)) {
            try {
                $rows = json_decode($rows, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $error) {
                throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
            }
        }
        return json_encode(self::normalize($rows ?? []),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string,array{value:string}> */
    public static function formRows(string $json): array
    {
        $rows = [];
        foreach (self::values($json) as $value) {
            $rows['row' . count($rows)] = ['value' => $value];
        }
        return $rows;
    }
}
