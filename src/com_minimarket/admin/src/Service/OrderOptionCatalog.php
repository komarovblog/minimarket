<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Global reusable order-option values: never part of informational attributes. */
final class OrderOptionCatalog
{
    public const MAX_VALUES = 250;

    public static function values(DatabaseInterface $db, int $optionId): array
    {
        if ($optionId < 1) return [];
        $q = $db->getQuery(true)->select(['id','title','code','color_hex','state','sort_order'])
            ->from($db->quoteName('#__minimarket_order_option_values'))
            ->where('option_id = ' . $optionId)->order('sort_order ASC,id ASC');
        $db->setQuery($q);
        $rows = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $rows[(int) $row['id']] = $row;
        }
        return $rows;
    }

    public static function formRows(DatabaseInterface $db, int $optionId): array
    {
        $result = [];
        foreach (self::values($db, $optionId) as $row) {
            $result['row' . count($result)] = [
                'id' => (int) $row['id'], 'title' => (string) $row['title'],
                'code' => (string) $row['code'], 'color_hex' => (string) $row['color_hex'],
                'state' => (int) $row['state'],
            ];
        }
        return $result;
    }

    /** Joomla native Subform rows, preserving immutable used value IDs. */
    public static function normalize(mixed $input, array $existing = []): array
    {
        if ($input === null || $input === '') return [];
        if (!is_array($input) || count($input) > self::MAX_VALUES) {
            throw new \InvalidArgumentException('COM_MINIMARKET_ORDER_OPTION_VALUES_INVALID');
        }
        $result = [];
        $seenCodes = [];
        $seenIds = [];
        foreach ($input as $raw) {
            if (is_object($raw)) $raw = (array) $raw;
            if (!is_array($raw)) throw new \InvalidArgumentException('COM_MINIMARKET_ORDER_OPTION_VALUES_INVALID');
            foreach (['id','title','code','color_hex','state'] as $key) {
                if (isset($raw[$key]) && !is_scalar($raw[$key])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_ORDER_OPTION_VALUES_INVALID');
                }
            }
            $idText = (string) ($raw['id'] ?? '0');
            $title = trim((string) ($raw['title'] ?? ''));
            $code = strtolower(trim((string) ($raw['code'] ?? '')));
            $hex = strtoupper(trim((string) ($raw['color_hex'] ?? '')));
            $state = (string) ($raw['state'] ?? '1');
            if (!ctype_digit($idText) || strlen($idText) > 10
                || (float) $idText > 4294967295
                || $title === '' || mb_strlen($title) > 255
                || preg_match('/[\x00-\x1f<>]/u', $title)
                || !in_array($state, ['0','1'], true)
                || ($hex !== '' && !preg_match('/^#[0-9A-F]{6}$/D', $hex))) {
                throw new \InvalidArgumentException('COM_MINIMARKET_ORDER_OPTION_VALUES_INVALID');
            }
            $id = (int) $idText;
            if ($id > 0 && (!isset($existing[$id]) || isset($seenIds[$id]))) {
                throw new \InvalidArgumentException('COM_MINIMARKET_ORDER_OPTION_VALUES_INVALID');
            }
            if ($code === '') {
                // Reuse an existing stable code on edit, including after a title rename.
                $code = $id > 0 ? (string) ($existing[$id]['code'] ?? '') : '';
                if ($code === '') {
                    $code = 'value-' . substr(hash('sha256', mb_strtolower($title)), 0, 16);
                }
            }
            if (!preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $code)
                || isset($seenCodes[$code])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_ORDER_OPTION_VALUES_INVALID');
            }
            $seenCodes[$code] = true;
            if ($id > 0) $seenIds[$id] = true;
            $result[] = [
                'id' => $id, 'title' => $title, 'code' => $code,
                'color_hex' => $hex, 'state' => (int) $state,
                'sort_order' => count($result),
            ];
        }
        return $result;
    }

    /** Within the outer AdminModel transaction; never affects another option. */
    public static function replace(DatabaseInterface $db, int $optionId, array $old, array $rows): void
    {
        if ($optionId < 1) throw new \InvalidArgumentException('COM_MINIMARKET_ORDER_OPTION_VALUES_INVALID');
        $kept = array_filter(array_column($rows, 'id'), static fn($x) => $x > 0);
        $removed = array_diff(array_keys($old), $kept);
        if ($removed) {
            $q = $db->getQuery(true)->delete($db->quoteName('#__minimarket_order_option_values'))
                ->where('option_id = ' . $optionId)
                ->where('id IN (' . implode(',', array_map('intval', $removed)) . ')');
            $db->setQuery($q);
            $db->execute(); // FK prevents removal when value becomes referenced by product SKUs.
        }
        // Free unique code keys before swaps, preserving row IDs.
        foreach ($rows as $row) {
            if ($row['id'] > 0 && (string) $old[$row['id']]['code'] !== $row['code']) {
                $q = $db->getQuery(true)->update($db->quoteName('#__minimarket_order_option_values'))
                    ->set($db->quoteName('code') . ' = ' . $db->quote('mm-stage-' . $row['id']))
                    ->where('option_id = ' . $optionId)->where('id = ' . $row['id']);
                $db->setQuery($q);
                $db->execute();
            }
        }
        foreach ($rows as $row) {
            $set = [
                'title' => $db->quote($row['title']),
                'code' => $db->quote($row['code']),
                'color_hex' => $db->quote($row['color_hex']),
                'state' => (string) $row['state'],
                'sort_order' => (string) $row['sort_order'],
            ];
            if ($row['id'] > 0) {
                $q = $db->getQuery(true)->update($db->quoteName('#__minimarket_order_option_values'));
                foreach ($set as $key => $value) {
                    $q->set($db->quoteName($key) . ' = ' . $value);
                }
                $q->where('option_id = ' . $optionId)->where('id = ' . $row['id']);
            } else {
                $q = $db->getQuery(true)->insert($db->quoteName('#__minimarket_order_option_values'))
                    ->columns($db->quoteName(array_merge(['option_id'], array_keys($set))))
                    ->values(implode(',', array_merge([(string) $optionId], array_values($set))));
            }
            $db->setQuery($q);
            $db->execute();
        }
    }
}
