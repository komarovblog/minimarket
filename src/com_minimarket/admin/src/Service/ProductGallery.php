<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * One product gallery with per-SKU visibility and optional SKU cover overrides.
 * Image files are never copied, renamed or removed. Variant targeting is bound
 * to stable physical variant IDs after ProductVariants::replace in one transaction.
 */
final class ProductGallery
{
    public const MAX_IMAGES = 30;

    private static function keys(mixed $input): array
    {
        if ($input === null || $input === '') return [];
        if (is_string($input)) {
            try { $input = json_decode($input, true, 512, JSON_THROW_ON_ERROR); }
            catch (\JsonException $e) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }
        }
        if (!is_array($input) || !array_is_list($input) || count($input) > 200) {
            throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
        }
        $unique = [];
        foreach ($input as $key) {
            if (!is_string($key) || strlen($key) > 1600 ||
                !is_array(json_decode($key, true))) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }
            $unique[$key] = true;
        }
        return array_keys($unique);
    }

    /** @return array<int,array<string,mixed>> */
    public static function normalizeRows(mixed $input, string $cover = ''): array
    {
        if ($input === null || $input === '') return [];
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
        $rows = [];
        $seen = [];
        foreach ($input as $item) {
            if (is_object($item)) $item = (array) $item;
            if (!is_array($item)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }
            $path = MediaPath::normalize((string) ($item['path'] ?? ''));
            $alt = trim((string) ($item['alt'] ?? ''));
            $visibility = (string) ($item['visibility_mode'] ?? '0');
            if ($path === null || $path === '' ||
                mb_strlen($alt) > 255 || preg_match('/[\x00-\x1f<>]/', $alt)
                || isset($seen[$path]) || $path === $cover ||
                !in_array($visibility, ['0','1'], true)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }
            $show = self::keys($item['show_keys'] ?? []);
            $main = self::keys($item['cover_keys'] ?? []);
            if ($visibility === '1' && (!$show || array_diff($main, $show))) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }
            $seen[$path] = true;
            $rows[] = [
                'path'=>$path, 'alt'=>$alt, 'sort_order'=>count($rows),
                'visibility_mode'=>(int)$visibility,
                'show_keys'=>$show, 'cover_keys'=>$main,
            ];
        }
        return $rows;
    }

    /** @return array<string,array<string,mixed>> */
    public static function loadFormRows(DatabaseInterface $db, int $productId): array
    {
        if ($productId < 1) return [];
        $q = $db->getQuery(true)
            ->select(['id','path','alt','visibility_mode'])
            ->from($db->quoteName('#__minimarket_product_images'))
            ->where($db->quoteName('product_id').' = '.$productId)
            ->order($db->quoteName('sort_order').' ASC, '.$db->quoteName('id').' ASC');
        $db->setQuery($q);
        $images = (array)$db->loadAssocList();
        $imageIds = array_values(array_filter(array_map('intval',array_column($images,'id'))));
        $associations = [];
        if ($imageIds) {
            $q = $db->getQuery(true)
                ->select(['image_id','variant_id','is_cover'])
                ->from($db->quoteName('#__minimarket_product_image_variants'))
                ->where('image_id IN ('.implode(',',$imageIds).')');
            $db->setQuery($q);
            $associations = (array)$db->loadAssocList();
        }
        $keys = [];
        $variantIds = array_values(array_unique(array_map(
            'intval',array_column($associations,'variant_id')
        )));
        if ($variantIds) {
            $q = $db->getQuery(true)->select(['id','selection_json'])
                ->from($db->quoteName('#__minimarket_product_variants'))
                ->where('id IN ('.implode(',',$variantIds).')');
            $db->setQuery($q);
            foreach ((array)$db->loadAssocList() as $variant) {
                $selection = json_decode((string)$variant['selection_json'],true);
                if (is_array($selection)) {
                    $keys[(int)$variant['id']] = ProductSkuMatrix::key($selection);
                }
            }
        }
        $links = [];
        foreach ($associations as $r) {
            $key = $keys[(int)$r['variant_id']] ?? null;
            if ($key === null) continue;
            $imageId = (int)$r['image_id'];
            $links[$imageId]['show'][] = $key;
            if ((int)$r['is_cover'] === 1) $links[$imageId]['cover'][] = $key;
        }
        $result = [];
        foreach ($images as $i => $image) {
            $id = (int)($image['id']??0);
            $result['row'.$i] = [
                'path'=>(string)$image['path'], 'alt'=>(string)$image['alt'],
                'visibility_mode'=>(int)($image['visibility_mode']??0),
                'show_keys'=>json_encode($links[$id]['show']??[], JSON_UNESCAPED_UNICODE),
                'cover_keys'=>json_encode($links[$id]['cover']??[], JSON_UNESCAPED_UNICODE),
            ];
        }
        return $result;
    }

    /**
     * Rewrite only image relationships. New SKU variants are already inserted;
     * their normalized selection keys can be resolved to stable variant_id here.
     */
    public static function replaceRows(
        DatabaseInterface $db, int $productId, array $rows,
        array $variantRows = [], array $variantIds = []
    ): void {
        if ($productId < 1 || count($variantRows) !== count($variantIds)) {
            throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
        }
        $keyToId = [];
        foreach ($variantRows as $i=>$row) {
            $selection = json_decode((string)$row['selection_json'],true);
            if (!is_array($selection) || (int)$variantIds[$i] < 1) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }
            $keyToId[ProductSkuMatrix::key($selection)] = (int)$variantIds[$i];
        }
        // Validate the full relation set before deleting even a single row.
        $wanted = [];
        $covers = [];
        foreach ($rows as $row) {
            $selected = (int)($row['visibility_mode']??0) === 1
                ? $row['show_keys'] : $row['cover_keys'];
            foreach ($selected as $key) {
                if (!isset($keyToId[$key])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
                }
                $id = $keyToId[$key];
                $isCover = in_array($key, $row['cover_keys'], true);
                if ($isCover && isset($covers[$id])) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
                }
                if ($isCover) $covers[$id] = true;
                $wanted[$row['path']][$id] = $isCover ? 1 : 0;
            }
        }
        $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_product_images'))
            ->where($db->quoteName('product_id').' = '.$productId);
        $db->setQuery($q);$db->execute();
        if (!$rows) return;
        $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_product_images'))
            ->columns($db->quoteName(['product_id','path','alt','sort_order','visibility_mode']));
        foreach ($rows as $i=>$row) {
            $q->values(implode(',',[
                $productId,$db->quote($row['path']),$db->quote($row['alt']),
                $i,(int)($row['visibility_mode']??0)
            ]));
        }
        $db->setQuery($q);$db->execute();
        if (!$wanted) return;

        $q=$db->getQuery(true)->select(['id','path'])
            ->from($db->quoteName('#__minimarket_product_images'))
            ->where('product_id = '.$productId);
        $db->setQuery($q);
        $imageIds = [];
        foreach ((array)$db->loadAssocList() as $image) {
            $imageIds[(string)$image['path']] = (int)$image['id'];
        }
        $q=$db->getQuery(true)
            ->insert($db->quoteName('#__minimarket_product_image_variants'))
            ->columns($db->quoteName(['image_id','variant_id','is_cover']));
        foreach ($wanted as $path=>$variantLinks) {
            if (empty($imageIds[$path])) {
                throw new \InvalidArgumentException('COM_MINIMARKET_GALLERY_INVALID');
            }
            foreach ($variantLinks as $variantId=>$isCover) {
                $q->values($imageIds[$path].','.$variantId.','.$isCover);
            }
        }
        $db->setQuery($q);$db->execute();
    }
}
