<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Read valid, published SKU combinations without introducing variant URLs. */
final class VariantCatalog
{
    /** @param int[] $ancestors published category chain */
    public static function load(DatabaseInterface $db, int $productId, array $ancestors): array
    {
        $empty = ['axes' => [], 'items' => []];
        if ($productId < 1 || !$ancestors) return $empty;
        // Most products have no variants: skip all metadata reads in this case.
        $q = $db->getQuery(true)->select(['id','selection_json','sku','price','stock','image','image_alt',
                'description_mode','introtext_override','description_override','video_mode'])
            ->from($db->quoteName('#__minimarket_product_variants'))
            ->where($db->quoteName('product_id') . ' = ' . $productId)
            ->where($db->quoteName('state') . ' = 1')
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $variantRows = (array) $db->loadAssocList();
        if (!$variantRows) return $empty;
        return self::loadLocal($db,$productId,$variantRows);
    }

    /**
     * SKU selections use only the product's own order options.
     * No category-wide descriptive attributes can define a SKU.
     */
    private static function loadLocal(DatabaseInterface $db,int $productId,array $variantRows): array
    {
        $empty=['axes'=>[],'items'=>[]];
        $q=$db->getQuery(true)->select(['id','code','title'])
            ->from($db->quoteName('#__minimarket_variant_dimensions'))
            ->where($db->quoteName('product_id').' = '.$productId)
            ->order('sort_order ASC,id ASC');
        $db->setQuery($q);
        $dimensions=(array)$db->loadAssocList();
        if (!$dimensions) return $empty;
        if (count($dimensions)>4) return $empty;
        $axes=[];$axisIds=[];$valueMap=[];
        foreach ($dimensions as $d) {
            $code=(string)$d['code'];$id=(int)$d['id'];
            $axes[$code]=['title'=>(string)$d['title'],'options'=>[]];
            $axisIds[$id]=$code;
        }
        $q=$db->getQuery(true)->select(['id','dimension_id','code','title'])
            ->from($db->quoteName('#__minimarket_variant_dimension_values'))
            ->where($db->quoteName('dimension_id').' IN ('.implode(',',array_keys($axisIds)).')')
            ->order('sort_order ASC,id ASC');
        $db->setQuery($q);
        foreach ((array)$db->loadAssocList() as $value) {
            $axisCode=$axisIds[(int)$value['dimension_id']]??null;
            if ($axisCode===null)continue;
            $vcode=(string)$value['code'];$id=(int)$value['id'];
            $axes[$axisCode]['options'][]=['value'=>'v:'.$vcode,'title'=>(string)$value['title']];
            $valueMap[$id]=[$axisCode,'v:'.$vcode];
        }
        foreach ($axes as $axis) if (!$axis['options'])return $empty;
        $valueImages=[];
        if ($valueMap) {
            $q=$db->getQuery(true)->select(['value_id','path','alt'])
                ->from($db->quoteName('#__minimarket_variant_value_images'))
                ->where($db->quoteName('value_id').' IN ('.implode(',',array_keys($valueMap)).')')
                ->order('value_id ASC,sort_order ASC,id ASC');
            $db->setQuery($q);
            foreach ((array)$db->loadAssocList() as $photo) {
                $path=self::safeImage((string)$photo['path']);
                if ($path !== '') $valueImages[(int)$photo['value_id']][]=[
                    'path'=>$path,'alt'=>(string)$photo['alt']
                ];
            }
        }
        $ids=array_map('intval',array_column($variantRows,'id'));
        $q=$db->getQuery(true)->select(['variant_id','dimension_id','value_id'])
            ->from($db->quoteName('#__minimarket_variant_value_assignments'))
            ->where($db->quoteName('variant_id').' IN ('.implode(',',$ids).')');
        $db->setQuery($q);
        $assigned=[];$assignedImages=[];
        foreach ((array)$db->loadAssocList() as $row) {
            $variantId=(int)$row['variant_id'];
            $dimId=(int)$row['dimension_id'];$valueId=(int)$row['value_id'];
            if (isset($axisIds[$dimId],$valueMap[$valueId])
                && $valueMap[$valueId][0]===$axisIds[$dimId]) {
                $assigned[$variantId][$axisIds[$dimId]]=$valueMap[$valueId][1];
                if (isset($valueImages[$valueId])) {
                    $assignedImages[$variantId][$axisIds[$dimId]]=$valueImages[$valueId];
                }
            }
        }
        $items=[];
        foreach ($variantRows as $r) {
            $id=(int)$r['id'];
            $selection=$assigned[$id]??[];
            if (count($selection)!==count($axes))continue;
            $json=json_decode((string)$r['selection_json'],true);
            if (!is_array($json)||$json!=$selection)continue;
            $items[]=[
                'id'=>$id,'selection'=>$selection,'sku'=>(string)$r['sku'],
                'price'=>(string)$r['price'],'stock'=>(int)$r['stock'],
                'image'=>self::safeImage((string)($r['image']??'')),
                'image_alt'=>(string)($r['image_alt']??''),'photos'=>[],
                'description_mode'=>(int)($r['description_mode']??0),
                'introtext_override'=>self::safeHtml($r['introtext_override']??null),
                'description_override'=>self::safeHtml($r['description_override']??null),
                'video_mode'=>(int)($r['video_mode']??0),
                'videos'=>[]
            ];
        }
        if (!$items) return $empty;
        $items=self::withPhotos($db,$items);
        foreach ($items as &$item) {
            if ($item['image']!=='' || $item['photos']) continue;
            foreach ($assignedImages[$item['id']]??[] as $colorPhotos) {
                if (!$colorPhotos) continue;
                $first=array_shift($colorPhotos);
                $item['image']=$first['path'];
                $item['image_alt']=$first['alt'];
                $item['photos']=$colorPhotos;
                break;
            }
        }
        unset($item);
        return ['axes'=>$axes,'items'=>$items];
    }

    /** Add all photos with one query; no per-SKU gallery round trips. */
    private static function withPhotos(DatabaseInterface $db,array $items): array
    {
        $ids=array_column($items,'id');
        $index=array_flip($ids);
        $q=$db->getQuery(true)->select(['variant_id','path','alt'])
            ->from($db->quoteName('#__minimarket_product_variant_images'))
            ->where($db->quoteName('variant_id').' IN ('.implode(',',$ids).')')
            ->order('variant_id ASC, sort_order ASC, id ASC');
        $db->setQuery($q);
        foreach ((array)$db->loadAssocList() as $r) {
            $path=self::safeImage((string)$r['path']);
            $i=$index[(int)$r['variant_id']]??null;
            if ($i!==null && $path!=='') {
                $items[$i]['photos'][]=['path'=>$path,'alt'=>(string)$r['alt']];
            }
        }
        $needsVideos=[];
        foreach ($items as $item) {
            if (($item['video_mode']??0)===1) $needsVideos[]=(int)$item['id'];
        }
        if ($needsVideos) {
            $q=$db->getQuery(true)->select(['variant_id','title','source','poster'])
                ->from($db->quoteName('#__minimarket_product_variant_videos'))
                ->where($db->quoteName('variant_id').' IN ('.implode(',',$needsVideos).')')
                ->order('variant_id ASC,sort_order ASC,id ASC');
            $db->setQuery($q);
            foreach ((array)$db->loadAssocList() as $v) {
                $i=$index[(int)$v['variant_id']]??null;
                if ($i===null) continue;
                $player=self::safePlayer((string)$v['source']);
                if ($player===null) continue;
                $items[$i]['videos'][]=[
                    'title'=>(string)$v['title'],
                    'kind'=>$player['kind'],
                    'url'=>$player['url'],
                    'poster'=>self::safeImage((string)($v['poster']??'')),
                ];
            }
        }
        usort($items,static fn(array $a,array $b):int=>
            (($b['stock']>0)<=>(($a['stock']>0)))?:0);
        return $items;
    }

    private static function safeHtml(?string $html): ?string
    {
        if ($html === null) return null;
        return \Joomla\CMS\Filter\InputFilter::getInstance()->clean($html,'HTML');
    }

    /** Only validated player kinds and URLs are ever passed to the browser. */
    private static function safePlayer(string $source): ?array
    {
        if (preg_match('~^(images|media)/[a-zA-Z0-9_./ -]+\.(?:mp4|webm)$~iD',$source)
            && !str_contains($source,'..') && !str_contains($source,'//')) {
            return ['kind'=>'file','url'=>$source];
        }
        if (preg_match('~^https://www\.youtube\.com/watch\?v=([a-zA-Z0-9_-]{11})$~D',$source,$m)) {
            return ['kind'=>'embed','url'=>'https://www.youtube-nocookie.com/embed/'.$m[1]];
        }
        if (preg_match('~^https://rutube\.ru/video/([a-zA-Z0-9]{12,50})/$~D',$source,$m)) {
            return ['kind'=>'embed','url'=>'https://rutube.ru/play/embed/'.$m[1]];
        }
        $parts=parse_url($source);
        if (is_array($parts) && ($parts['scheme']??'')==='https'
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['fragment'])
            && !isset($parts['port']) && filter_var($parts['host']??'',FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)
            && preg_match('~\.(?:mp4|webm)$~iD',(string)($parts['path']??''))) {
            return ['kind'=>'file','url'=>$source];
        }
        return null;
    }

    private static function safeImage(string $path): string
    {
        return strlen($path) <= 1024 && str_starts_with($path,'images/')
            && !str_contains($path, '..') && !str_contains($path, '//')
            && !preg_match('~[\x00-\x1f<>?#\\\\]~', $path)
            && preg_match('~\.(?:jpe?g|png|webp|gif|avif)$~iD', $path)
            ? $path : '';
    }
}
