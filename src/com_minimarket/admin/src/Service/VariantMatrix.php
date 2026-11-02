<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Product-local SKU option matrix. No global attribute/category prerequisite.
 * Pure planner: never writes a SKU, a product or files to the database.
 *
 * The editor/importer can use this to preview combinations before committing.
 * Stable internal IDs belong to the relational dimensions/value tables.
 */
final class VariantMatrix
{
    public const MAX_AXES = 4;
    public const MAX_COMBINATIONS = 200;

    /**
     * @return array<int,array{code:string,title:string,values:array<int,array{code:string,title:string}>}>
     */
    public static function normalize(mixed $input): array
    {
        if ($input === null || $input === [] || $input === '{}') {
            return [];
        }
        if (!is_array($input) || count($input) > self::MAX_AXES) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
        }
        $axes = [];
        $usedCodes = [];
        $combinationCount = 1;
        foreach ($input as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
            }
            $title = trim((string) ($row['title'] ?? ''));
            $code = self::code((string) ($row['code'] ?? ''), $title);
            if ($title === '' || mb_strlen($title) > 255
                || preg_match('/[\x00-\x1f<>]/u', $title)
                || isset($usedCodes[$code])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
            }
            $usedCodes[$code] = true;
            $choices = $row['values'] ?? null;
            if (!is_array($choices) || $choices === [] || count($choices) > self::MAX_COMBINATIONS) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
            }
            $values = [];
            $usedValues = [];
            foreach ($choices as $value) {
                if (is_string($value)) {
                    $value = ['title' => $value];
                }
                if (!is_array($value)) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
                }
                $valueTitle = trim((string) ($value['title'] ?? ''));
                $valueCode = self::code((string) ($value['code'] ?? ''), $valueTitle);
                if ($valueTitle === '' || mb_strlen($valueTitle) > 255
                    || preg_match('/[\x00-\x1f<>]/u', $valueTitle)
                    || isset($usedValues[$valueCode])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
                }
                $usedValues[$valueCode] = true;
                $values[] = ['code' => $valueCode, 'title' => $valueTitle];
            }
            $combinationCount *= count($values);
            if ($combinationCount > self::MAX_COMBINATIONS) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_LIMIT');
            }
            $axes[] = ['code' => $code, 'title' => $title, 'values' => $values];
        }
        return $axes;
    }

    /**
     * Preview in product-local terms, without creating orphan SKU records.
     * Changing UI order does not change signature of an individual combination.
     * @return array<int,array{title:string,selection:array<string,string>,signature:string}>
     */
    public static function preview(array $axes): array
    {
        if ($axes === []) {
            return [];
        }
        if (count($axes) > self::MAX_AXES) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_LIMIT');
        }
        $rows = [['title' => '', 'selection' => []]];
        foreach ($axes as $axis) {
            $next = [];
            foreach ($rows as $current) {
                foreach ($axis['values'] as $value) {
                    $selection = $current['selection'];
                    $selection[$axis['code']] = $value['code'];
                    $next[] = [
                        'title' => $current['title'] === '' ? $value['title']
                            : $current['title'] . ' / ' . $value['title'],
                        'selection' => $selection,
                    ];
                    if (count($next) > self::MAX_COMBINATIONS) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_LIMIT');
                    }
                }
            }
            $rows = $next;
        }
        foreach ($rows as &$row) {
            $row['signature'] = self::signature($row['selection']);
        }
        unset($row);
        return $rows;
    }

    /** A signature is independent of ordering of characteristics on screen. */
    public static function signature(array $selection): string
    {
        if ($selection === []) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
        }
        ksort($selection, SORT_STRING);
        foreach ($selection as $code => $value) {
            if (!is_string($code) || !preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $code)
                || !is_string($value) || !preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $value)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
            }
        }
        return hash('sha256', json_encode($selection, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * Empty code is auto-derived once for human-friendly UI. Caller persists
     * the returned code with its stable ID, never re-derives on every rename.
     */
    private static function code(string $code, string $title): string
    {
        $code = strtolower(trim($code));
        if ($code === '') {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title)) ?? '';
            $slug = trim($slug, '-');
            $code = preg_match('/^[a-z][a-z0-9-]{0,99}$/D', $slug)
                ? $slug
                : 'v-' . substr(hash('sha256', mb_strtolower($title)), 0, 16);
        }
        if (!preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $code)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_MATRIX_INVALID');
        }
        return $code;
    }
}
