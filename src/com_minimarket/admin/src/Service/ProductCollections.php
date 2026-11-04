<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Non-SKU merchandising: one brand on product row and many collections
 * in a pivot table. Never alters gallery, variants, price or stock.
 */
final class ProductCollections
{
    public static function selected(DatabaseInterface $db, int $productId): array
    {
        if ($productId < 1) return [];
        $query = $db->getQuery(true)
            ->select('collection_id')
            ->from($db->quoteName('#__minimarket_product_collections'))
            ->where('product_id = ' . $productId)
            ->order('sort_order ASC, collection_id ASC');
        $db->setQuery($query);
        return array_map('intval', (array) $db->loadColumn());
    }

    public static function allowed(DatabaseInterface $db): array
    {
        $query = $db->getQuery(true)->select('id')
            ->from($db->quoteName('#__minimarket_collections'));
        $db->setQuery($query);
        return array_fill_keys(array_map('intval', (array) $db->loadColumn()), true);
    }

    /** Never accept arbitrary pivot IDs or an unpublished-but-existing shortcut. */
    public static function normalize(mixed $raw, array $allowed): array
    {
        if ($raw === null || $raw === '') return [];
        if (!is_array($raw) || !array_is_list($raw) || count($raw) > 250) {
            throw new \InvalidArgumentException('COM_MINIMARKET_COLLECTION_ASSIGNMENT_INVALID');
        }
        $ids = [];
        foreach ($raw as $item) {
            if (!is_scalar($item) || !ctype_digit((string) $item) ||
                (int)$item < 1 || !isset($allowed[(int)$item]) ||
                isset($ids[(int)$item])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_COLLECTION_ASSIGNMENT_INVALID');
            }
            $ids[(int)$item]=(int)$item;
        }
        return array_values($ids);
    }

    /** Caller owns the surrounding product transaction. */
    public static function replace(DatabaseInterface $db, int $productId, array $ids): void
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_COLLECTION_ASSIGNMENT_INVALID');
        }
        $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_product_collections'))
            ->where('product_id = '. $productId);
        $db->setQuery($q);
        $db->execute();
        foreach ($ids as $position=>$id) {
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_product_collections'))
                ->columns($db->quoteName(['product_id','collection_id','sort_order']))
                ->values($productId . ',' . (int)$id . ',' . $position);
            $db->setQuery($q);
            $db->execute();
        }
    }
}
