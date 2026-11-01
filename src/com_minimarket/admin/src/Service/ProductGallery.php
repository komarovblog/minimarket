<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Secondary product photos, stored as references to Joomla Media files.
 * The existing products.image + image_alt remain the single primary cover.
 *
 * Every operation uses the DB transaction started by ProductModel::save().
 * No action in this service touches, moves, copies, or deletes image files.
 */
final class ProductGallery
{
    public const MAX_IMAGES = 30;

    /**
     * Validate repeatable Joomla subform rows in the order chosen by the editor.
     * Missing path in a row is an error, never an implicit delete of the whole gallery.
     *
     * @return array<int, array{path:string,alt:string,sort_order:int}>
     */
    public static function normalizeRows(mixed $input, string $cover = ''): array
    {
        if ($input === null || $input === '') {
            return [];
        }
        if (is_string($input)) {
            $input = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
        }
        if (!is_array($input)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
        }
        if (count($input) > self::MAX_IMAGES) {
            throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_LIMIT');
        }

        $cover = MediaPath::normalize($cover);
        if ($cover === null) {
            throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_MEDIA_INVALID');
        }

        $result = [];
        $seen = [];
        foreach ($input as $item) {
            if (is_object($item)) {
                $item = (array) $item;
            }
            if (!is_array($item)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }

            $path = MediaPath::normalize((string) ($item['path'] ?? ''));
            $alt = trim((string) ($item['alt'] ?? ''));

            if ($path === null || $path === ''
                || mb_strlen($alt) > 255
                || preg_match('/[\x00-\x1f<>]/', $alt)
                || isset($seen[$path])
                || $path === $cover) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }

            $seen[$path] = true;
            $result[] = ['path' => $path, 'alt' => $alt, 'sort_order' => count($result)];
        }

        return $result;
    }

    /** @return array<string, array{path:string,alt:string}> */
    public static function loadFormRows(DatabaseInterface $db, int $productId): array
    {
        if ($productId < 1) {
            return [];
        }
        $q = $db->getQuery(true)
            ->select(['path', 'alt'])
            ->from($db->quoteName('#__minimarket_product_images'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->order($db->quoteName('sort_order') . ' ASC, ' . $db->quoteName('id') . ' ASC');
        $db->setQuery($q);

        $formRows = [];
        foreach ((array) $db->loadAssocList() as $i => $row) {
            $formRows['row' . $i] = [
                'path' => (string) $row['path'],
                'alt' => (string) $row['alt'],
            ];
        }

        return $formRows;
    }

    /**
     * Replace *relationships* atomically; physical Joomla Media files remain.
     * The caller must hold a transaction spanning parent Product::save().
     */
    public static function replaceRows(DatabaseInterface $db, int $productId, array $rows): void
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
        }
        $delete = $db->getQuery(true)->delete($db->quoteName('#__minimarket_product_images'))
            ->where($db->quoteName('product_id') . ' = ' . $productId);
        $db->setQuery($delete);
        $db->execute();

        if (!$rows) {
            return;
        }

        $insert = $db->getQuery(true)
            ->insert($db->quoteName('#__minimarket_product_images'))
            ->columns($db->quoteName(['product_id', 'path', 'alt', 'sort_order']));
        foreach ($rows as $i => $row) {
            $insert->values(implode(',', [
                (string) $productId,
                $db->quote($row['path']),
                $db->quote($row['alt']),
                (string) $i,
            ]));
        }

        $db->setQuery($insert);
        $db->execute();
    }
}
