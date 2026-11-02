<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * URL references only; no binary uploads or edits to the user's media files.
 * Local MP4/WebM files are selected/uploaded in Joomla Media and linked here.
 */
final class ProductVideos
{
    public const MAX_VIDEOS = 20;

    public static function normalizeSource(string $source): ?string
    {
        $source = trim($source);
        if ($source === '' || strlen($source) > 1024
            || preg_match('/[\x00-\x1f<>"\'\\\]/', $source)) {
            return null;
        }
        if (preg_match('~^(images|media)/[a-zA-Z0-9_./ -]+\.(?:mp4|webm)$~iD', $source)
            && !str_contains($source, '..') && !str_contains($source, '//')) {
            return $source;
        }
        $parts = parse_url($source);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || isset($parts['fragment'])) {
            return null;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (in_array($host, ['youtu.be','youtube.com','www.youtube.com','m.youtube.com'], true)) {
            $id = '';
            if ($host === 'youtu.be') {
                $id = trim((string) ($parts['path'] ?? ''), '/');
            } elseif (preg_match('~^/(?:embed|shorts)/([a-zA-Z0-9_-]{11})$~D', (string) ($parts['path'] ?? ''), $m)) {
                $id = $m[1];
            } elseif (($parts['path'] ?? '') === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = is_string($query['v'] ?? null) ? $query['v'] : '';
            }
            if (preg_match('/^[a-zA-Z0-9_-]{11}$/D', $id)) {
                return 'https://www.youtube.com/watch?v=' . $id;
            }
        }
        if (in_array($host, ['rutube.ru','www.rutube.ru'], true)
            && preg_match('~^/video/([a-zA-Z0-9]{12,50})/?$~D', (string) ($parts['path'] ?? ''), $m)) {
            return 'https://rutube.ru/video/' . $m[1] . '/';
        }
        return null;
    }

    /** @return array<int,array{title:string,source:string,sort_order:int}> */
    public static function normalize(mixed $input): array
    {
        if ($input === null || $input === '') {
            return [];
        }
        if (is_string($input)) {
            try {
                $input = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VIDEO_INVALID');
            }
        }
        if (!is_array($input) || count($input) > self::MAX_VIDEOS) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VIDEO_INVALID');
        }
        $rows = [];
        $seen = [];
        foreach ($input as $row) {
            $row = is_object($row) ? (array) $row : $row;
            if (!is_array($row) || !is_scalar($row['title'] ?? '')
                || !is_scalar($row['source'] ?? null)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VIDEO_INVALID');
            }
            $title = trim((string) ($row['title'] ?? ''));
            $source = self::normalizeSource((string) $row['source']);
            if ($source === null || isset($seen[$source]) || mb_strlen($title) > 255
                || preg_match('/[\x00-\x1f<>]/u', $title)) {
                throw new \InvalidArgumentException('COM_MINIMARKET_VIDEO_INVALID');
            }
            $seen[$source] = true;
            $rows[] = ['title'=>$title,'source'=>$source,'sort_order'=>count($rows)];
        }
        return $rows;
    }

    /** @return array<string,array{title:string,source:string}> */
    public static function loadFormRows(DatabaseInterface $db, int $productId): array
    {
        if ($productId < 1) return [];
        $q = $db->getQuery(true)
            ->select(['title','source'])
            ->from($db->quoteName('#__minimarket_product_videos'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $result = [];
        foreach ((array) $db->loadAssocList() as $row) {
            $result['row' . count($result)] = [
                'title'=>(string)$row['title'], 'source'=>(string)$row['source']
            ];
        }
        return $result;
    }

    public static function replace(DatabaseInterface $db, int $productId, array $rows): void
    {
        if ($productId < 1) {
            throw new \InvalidArgumentException('COM_MINIMARKET_VIDEO_INVALID');
        }
        $q = $db->getQuery(true)
            ->delete($db->quoteName('#__minimarket_product_videos'))
            ->where($db->quoteName('product_id') . ' = ' . $productId);
        $db->setQuery($q);
        $db->execute();
        if (!$rows) return;
        $q = $db->getQuery(true)
            ->insert($db->quoteName('#__minimarket_product_videos'))
            ->columns($db->quoteName(['product_id','title','source','sort_order']));
        foreach ($rows as $row) {
            $q->values(implode(',', [
                (string) $productId,
                $db->quote($row['title']),
                $db->quote($row['source']),
                (string) $row['sort_order'],
            ]));
        }
        $db->setQuery($q);
        $db->execute();
    }

    /** @return array{kind:string,url:string} */
    public static function player(string $source): array
    {
        $canonical = self::normalizeSource($source);
        if ($canonical === null) return ['kind'=>'invalid','url'=>''];
        if (str_starts_with($canonical,'images/') || str_starts_with($canonical,'media/')) {
            return ['kind'=>'file','url'=>$canonical];
        }
        if (preg_match('~^https://www\.youtube\.com/watch\?v=([a-zA-Z0-9_-]{11})$~D', $canonical, $m)) {
            return ['kind'=>'iframe','url'=>'https://www.youtube-nocookie.com/embed/' . $m[1]];
        }
        if (preg_match('~^https://rutube\.ru/video/([a-zA-Z0-9]{12,50})/$~D', $canonical, $m)) {
            return ['kind'=>'iframe','url'=>'https://rutube.ru/play/embed/' . $m[1]];
        }
        return ['kind'=>'invalid','url'=>''];
    }
}
