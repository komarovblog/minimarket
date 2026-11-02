<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Attribute-scoped manual ordering. Never edits dictionary content or values
 * belonging to a different attribute.
 */
final class AttributeValueOrdering
{
    /** @return int[] */
    public static function ids(DatabaseInterface $db, int $attributeId): array
    {
        if ($attributeId < 1) {
            return [];
        }
        $q = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__minimarket_attribute_values'))
            ->where($db->quoteName('attribute_id') . ' = ' . $attributeId);
        $db->setQuery($q);
        return array_map('intval', (array) $db->loadColumn());
    }

    /**
     * @param mixed $posted ID=>nonnegative sort_order from Joomla form
     * @param int[] $knownIds All values belonging to this attribute
     * @return array<int,int>
     */
    public static function normalize(mixed $posted, array $knownIds): array
    {
        if (!is_array($posted)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ORDER_INVALID');
        }
        $allowed = array_fill_keys(array_map('intval', $knownIds), true);
        $result = [];
        foreach ($posted as $key => $number) {
            if (!ctype_digit((string) $key) || (int) $key < 1
                || !isset($allowed[(int) $key])
                || !is_scalar($number)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ORDER_INVALID');
            }
            $number = (string) $number;
            if (!preg_match('/^(0|[1-9][0-9]{0,9})$/D', $number)
                || (strlen($number) === 10 && strcmp($number, '4294967295') > 0)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ORDER_INVALID');
            }
            $result[(int) $key] = (int) $number;
        }
        $keys = array_keys($result);
        sort($keys, SORT_NUMERIC);
        $expected = array_map('intval', $knownIds);
        sort($expected, SORT_NUMERIC);
        if ($keys !== $expected) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ORDER_INVALID');
        }
        return $result;
    }

    /**
     * Batch update all positions in a single SQL statement inside the
     * attribute model's transaction; values and publication remain untouched.
     */
    public static function apply(DatabaseInterface $db, int $attributeId, array $orders): void
    {
        if ($attributeId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ORDER_INVALID');
        }
        if (!$orders) {
            return;
        }
        $cases = [];
        foreach ($orders as $valueId => $sortOrder) {
            $cases[] = 'WHEN ' . (int) $valueId . ' THEN ' . (int) $sortOrder;
        }
        $ids = implode(',', array_map('intval', array_keys($orders)));
        $q = $db->getQuery(true)
            ->update($db->quoteName('#__minimarket_attribute_values'))
            ->set($db->quoteName('sort_order') . ' = CASE ' . $db->quoteName('id')
                . ' ' . implode(' ', $cases) . ' ELSE ' . $db->quoteName('sort_order') . ' END')
            ->where($db->quoteName('attribute_id') . ' = ' . $attributeId)
            ->where($db->quoteName('id') . ' IN (' . $ids . ')');
        $db->setQuery($q);
        $db->execute();
    }
}
