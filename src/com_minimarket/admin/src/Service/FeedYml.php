<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * XML/YML feed: product ads and simple Yandex/VK product catalog structure.
 * Provider validation remains separate; do not promise marketplace acceptance.
 */
final class FeedYml
{
    private static function escape(mixed $text): string
    {
        $value=preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u','',(string)$text)??'';
        return htmlspecialchars($value,ENT_XML1|ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    }

    private static function el(string $tag,mixed $text): string
    {
        return '<'.$tag.'>'.self::escape($text).'</'.$tag.'>';
    }

    public static function render(
        array $feed, array $categories, array $offers, string $baseUrl,
        ?\DateTimeInterface $now=null, bool $inventoryEnabled=true
    ): string {
        if (!isset(FeedConfig::PROVIDERS[(string)($feed['provider']??'')])) {
            throw new \InvalidArgumentException('COM_MINIMARKET_FEED_INVALID');
        }
        $now??=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));
        $date=$feed['provider']==='yandex_direct'
            ?$now->format('Y-m-d H:i')
            :$now->format('Y-m-d\TH:i:sP');
        $url=rtrim($baseUrl,'/').'/';
        $shopName=(string)$feed['title'];
        $company=(string)($feed['company']?:$shopName);
        $xml='<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml.='<yml_catalog date="'.self::escape($date).'"><shop>'
            .self::el('name',$shopName).self::el('company',$company)
            .self::el('url',$url)
            .'<currencies><currency id="RUR" rate="1"/></currencies><categories>';
        foreach($categories as $category) {
            $item=(array)$category;
            $id=(int)($item['id']??0);
            if($id<1)continue;
            $parent=(int)($item['parent_id']??0);
            $xml.='<category id="'.$id.'"'.($parent>0?' parentId="'.$parent.'"':'').'>'
                .self::escape($item['title']??'').'</category>';
        }
        $xml.='</categories><offers>';
        foreach($offers as $offer) {
            $o=(array)$offer;
            $id=(string)($o['id']??'');
            $category=(int)($o['category_id']??0);
            $price=(float)($o['price']??0);
            $link=(string)($o['url']??'');
            $picture=(string)($o['image']??'');
            if(!preg_match('/^(?:p|sku)-[1-9][0-9]*$/D',$id)
                || $category<1 || $price<=0 || !is_finite($price)
                || !str_starts_with($link,$url)
                || !str_starts_with($picture,$url))continue;
            $xml.='<offer id="'.self::escape($id).'" available="'
                .(!$inventoryEnabled || (int)($o['stock']??0)>0?'true':'false').'">';
            $xml.=self::el('url',$link).self::el('price',number_format($price,2,'.',''));
            $old=(float)($o['old_price']??0);
            if($old>$price)$xml.=self::el('oldprice',number_format($old,2,'.',''));
            $xml.=self::el('currencyId','RUR').self::el('categoryId',$category)
                .self::el('picture',$picture)
                .self::el('name',$o['title']??'');
            if(trim((string)($o['vendor']??''))!=='')$xml.=self::el('vendor',$o['vendor']);
            if(trim((string)($o['sku']??''))!=='')$xml.=self::el('vendorCode',$o['sku']);
            if(trim((string)($o['description']??''))!=='')$xml.=self::el('description',$o['description']);
            $xml.='</offer>';
        }
        return $xml.'</offers></shop></yml_catalog>'."\n";
    }
}
