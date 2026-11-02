<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Inline Joomla repeatable values, preserving existing dictionary value IDs. */
final class AttributeValueRows
{
    public const MAX_ROWS = 1000;

    /** @return array<int,array{id:int,title:string,code:string,state:int,sort_order:int}> */
    public static function load(DatabaseInterface $db, int $attributeId): array
    {
        if ($attributeId < 1) {
            return [];
        }
        $q = $db->getQuery(true)
            ->select(['id', 'title', 'code', 'color_hex', 'state', 'sort_order'])
            ->from($db->quoteName('#__minimarket_attribute_values'))
            ->where($db->quoteName('attribute_id') . ' = ' . $attributeId)
            ->order($db->quoteName('sort_order') . ' ASC, ' . $db->quoteName('id') . ' ASC');
        $db->setQuery($q);
        $result = [];
        foreach ((array) $db->loadAssocList() as $item) {
            $result[(int) $item['id']] = [
                'id' => (int) $item['id'],
                'title' => (string) $item['title'],
                'code' => (string) $item['code'],
                'color_hex' => (string) ($item['color_hex'] ?? ''),
                'state' => (int) $item['state'],
                'sort_order' => (int) $item['sort_order'],
            ];
        }
        return $result;
    }

    /** @return array<string,array{id:int,title:string,code:string,state:int}> */
    public static function formRows(DatabaseInterface $db, int $attributeId): array
    {
        $rows = [];
        foreach (self::load($db, $attributeId) as $value) {
            $rows['row' . count($rows)] = [
                'id' => $value['id'],
                'title' => $value['title'],
                'code' => $value['code'],
                'color_hex' => $value['color_hex'],
                'state' => $value['state'],
            ];
        }
        return $rows;
    }

    /**
     * @param mixed $input Joomla Subform (row0 => field data)
     * @param array<int,array{id:int,title:string,code:string,state:int,sort_order:int}> $existing
     * @return array<int,array{id:int,title:string,code:string,state:int,sort_order:int}>
     */
    public static function normalize(mixed $input, array $existing): array
    {
        if ($input === null || $input === '') {
            return [];
        }
        if (is_string($input)) {
            $input = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
        }
        if (!is_array($input) || count($input) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ROWS_INVALID');
        }

        $result = [];
        $seenIds = [];
        $seenCodes = [];
        foreach ($input as $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }
            if (!is_array($row) || !is_scalar($row['id'] ?? 0)
                || !ctype_digit((string) ($row['id'] ?? 0))
                || !is_scalar($row['title'] ?? '')
                || !is_scalar($row['code'] ?? '')
                || !is_scalar($row['state'] ?? 1)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ROWS_INVALID');
            }
            $id = (int) ($row['id'] ?? 0);
            $title = trim((string) ($row['title'] ?? ''));
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            $state = (string) ($row['state'] ?? '1');
            $hex = trim((string) ($row['color_hex'] ?? ($id > 0
                ? ($existing[$id]['color_hex'] ?? '') : '')));
            if ($id > 0 && (!isset($existing[$id]) || isset($seenIds[$id]))) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ROWS_INVALID');
            }
            if ($title === '' || mb_strlen($title) > 255
                || preg_match('/[\x00-\x1f<>]/u', $title)
                || !in_array($state, ['0','1'], true)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ROWS_INVALID');
            }

            if ($code === '') {
                // Stable code based on title. Joomla's transliteration is
                // language-aware; fallback for unsupported scripts stays ASCII.
                $candidate = \Joomla\CMS\Filter\OutputFilter::stringURLSafe($title);
                $code = strtolower((string) $candidate);
                if (!preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $code)) {
                    $code = 'value-' . substr(hash('sha256', mb_strtolower($title)), 0, 12);
                }
            }
            if (!preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $code)
                || isset($seenCodes[$code])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ROWS_INVALID');
            }
            if ($hex !== '' && !preg_match('/^#[a-fA-F0-9]{6}$/D', $hex)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_COLOR_INVALID');
            }
            if ($id > 0) {
                $seenIds[$id] = true;
            }
            $seenCodes[$code] = true;
            $result[] = [
                'id' => $id, 'title' => $title, 'code' => $code,
                'state' => (int) $state, 'color_hex' => strtoupper($hex), 'sort_order' => count($result),
            ];
        }
        return $result;
    }

    /** Existing value IDs missing from subform; never delete a value used by a product. */
    public static function deletedIds(array $existing, array $rows): array
    {
        $kept = [];
        foreach ($rows as $row) {
            if ($row['id'] > 0) {
                $kept[$row['id']] = true;
            }
        }
        return array_values(array_diff(array_keys($existing), array_keys($kept)));
    }

    /** Called inside AttributeModel's transaction; never changes another attribute. */
    public static function apply(DatabaseInterface $db, int $attributeId, array $existing, array $rows): void
    {
        if ($attributeId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ROWS_INVALID');
        }
        $removed = self::deletedIds($existing, $rows);
        if ($removed) {
            $in = implode(',', array_map('intval', $removed));
            $q = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_product_attribute_options'))
                ->where($db->quoteName('value_id') . ' IN (' . $in . ')');
            $db->setQuery($q);
            if ((int) $db->loadResult() !== 0) {
                throw new \RuntimeException('COM_MINIMARKET_VALUE_ROWS_IN_USE');
            }
            $q = $db->getQuery(true)
                ->delete($db->quoteName('#__minimarket_attribute_values'))
                ->where($db->quoteName('attribute_id') . ' = ' . $attributeId)
                ->where($db->quoteName('id') . ' IN (' . $in . ')');
            $db->setQuery($q);
            $db->execute();
        }

        // Free old codes before updates to permit swaps, e.g. red ↔ yellow.
        // This intermediate change is transaction-protected and never visible
        // outside the transaction.
        $stages = [];
        foreach ($rows as $row) {
            if ($row['id'] > 0 && $existing[$row['id']]['code'] !== $row['code']) {
                $stages[] = [
                    'id' => $row['id'],
                    'temporary' => '__mm_stage_' . $row['id'],
                ];
            }
        }
        foreach ($stages as $stage) {
            $q = $db->getQuery(true)
                ->update($db->quoteName('#__minimarket_attribute_values'))
                ->set($db->quoteName('code') . ' = ' . $db->quote($stage['temporary']))
                ->where($db->quoteName('attribute_id') . ' = ' . $attributeId)
                ->where($db->quoteName('id') . ' = ' . $stage['id']);
            $db->setQuery($q);
            $db->execute();
        }

        foreach ($rows as $row) {
            if ($row['id'] > 0) {
                $q = $db->getQuery(true)
                    ->update($db->quoteName('#__minimarket_attribute_values'))
                    ->set([
                        $db->quoteName('title') . ' = ' . $db->quote($row['title']),
                        $db->quoteName('code') . ' = ' . $db->quote($row['code']),
                        $db->quoteName('color_hex') . ' = ' . $db->quote($row['color_hex']),
                        $db->quoteName('state') . ' = ' . (int) $row['state'],
                        $db->quoteName('sort_order') . ' = ' . (int) $row['sort_order'],
                    ])
                    ->where($db->quoteName('attribute_id') . ' = ' . $attributeId)
                    ->where($db->quoteName('id') . ' = ' . $row['id']);
            } else {
                $q = $db->getQuery(true)
                    ->insert($db->quoteName('#__minimarket_attribute_values'))
                    ->columns($db->quoteName([
                        'attribute_id','title','code','color_hex','state','sort_order'
                    ]))
                    ->values(implode(',', [
                        (int) $attributeId,
                        $db->quote($row['title']),
                        $db->quote($row['code']),
                        $db->quote($row['color_hex']),
                        (int) $row['state'],
                        (int) $row['sort_order'],
                    ]));
            }
            $db->setQuery($q);
            $db->execute();
        }
    }
}
