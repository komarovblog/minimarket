<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Photos attached once to a local option value (e.g. Red) and shared by all
 * sizes of that color. Files are only referenced; Joomla Media is not copied.
 */
final class VariantValueGallery
{
    public const MAX_PHOTOS = 20;

    public static function normalize(mixed $input): array
    {
        if ($input === null || $input === '') return [];
        if (!is_array($input) || count($input) > self::MAX_PHOTOS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_VALUE_PHOTOS_INVALID');
        }
        $result = [];
        $seen = [];
        foreach ($input as $row) {
            if (!is_array($row) || !is_scalar($row['path'] ?? null)
                || !is_scalar($row['alt'] ?? '')) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_VALUE_PHOTOS_INVALID');
            }
            $path = MediaPath::normalize((string)$row['path']);
            $alt = trim((string) ($row['alt'] ?? ''));
            if ($path === null || $path === '' || isset($seen[$path])
                || mb_strlen($alt) > 255 || preg_match('/[\x00-\x1f<>]/u', $alt)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_VALUE_PHOTOS_INVALID');
            }
            $seen[$path] = true;
            $result[] = ['path'=>$path,'alt'=>$alt];
        }
        return $result;
    }

    /** @return array<int,array<int,array{path:string,alt:string}>> */
    public static function load(DatabaseInterface $db, array $valueIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $valueIds))));
        if (!$ids) return [];
        $q=$db->getQuery(true)->select(['value_id','path','alt'])
            ->from($db->quoteName('#__minimarket_variant_value_images'))
            ->where($db->quoteName('value_id').' IN ('.implode(',',$ids).')')
            ->order('value_id ASC,sort_order ASC,id ASC');
        $db->setQuery($q);
        $result=[];
        foreach ((array)$db->loadAssocList() as $row) {
            $result[(int)$row['value_id']][]=[
                'path'=>(string)$row['path'],'alt'=>(string)$row['alt']
            ];
        }
        return $result;
    }

    public static function replace(DatabaseInterface $db, int $valueId, array $photos): void
    {
        if ($valueId < 1) throw new \InvalidArgumentException('COM_MINIMARKET_VARIANT_VALUE_PHOTOS_INVALID');
        $q=$db->getQuery(true)->delete($db->quoteName('#__minimarket_variant_value_images'))
            ->where($db->quoteName('value_id').' = '.$valueId);
        $db->setQuery($q);$db->execute();
        if (!$photos) return;
        $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_variant_value_images'))
            ->columns($db->quoteName(['value_id','path','alt','sort_order']));
        foreach ($photos as $i=>$photo) {
            $q->values(implode(',', [$valueId,$db->quote($photo['path']),$db->quote($photo['alt']),(int)$i]));
        }
        $db->setQuery($q);$db->execute();
    }
}
