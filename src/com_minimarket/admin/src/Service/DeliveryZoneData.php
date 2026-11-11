<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

/** Validate all fields of a native courier zone, with no implicit defaults for money. */
final class DeliveryZoneData
{
    private static function cents(mixed $value): int
    {
        $raw=trim((string)$value);
        if(!preg_match('/^(0|[1-9][0-9]{0,8})(?:[.,][0-9]{1,2})?$/D',$raw))
            throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
        $p=explode('.',str_replace(',','.',$raw),2);
        return (int)$p[0]*100+(int)str_pad($p[1]??'',2,'0');
    }

    private static function num(mixed $value,int $max): int
    {
        $raw=trim((string)$value);
        if(!preg_match('/^(0|[1-9][0-9]{0,9})$/D',$raw)|| (float)$raw>$max)
            throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
        return (int)$raw;
    }

    public static function normalize(array $input): array
    {
        $title=trim(strip_tags((string)($input['title']??'')));
        $country=strtoupper(trim((string)($input['country_code']??'RU')));
        $region=trim(strip_tags((string)($input['region']??'')));
        $cities=trim((string)($input['cities']??''));
        $postcodes=trim((string)($input['postcode_prefixes']??''));
        $mode=(string)($input['rate_mode']??'fixed');
        if(mb_strlen($title)<2||mb_strlen($title)>160
            ||!preg_match('/^[A-Z]{2}$/D',$country)||mb_strlen($region)>120
            ||!in_array($mode,['fixed','per_kg','per_liter'],true)
            ||mb_strlen($cities)>4000||mb_strlen($postcodes)>2000
            ||$cities==='')throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
        $names=array_filter(array_map('trim',preg_split('/[\r\n;,]+/u',$cities)?:[]));
        if(!$names||count($names)>300)throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
        foreach($names as $name){
            if($name!=='*'&&(!preg_match('/^[\p{L}\p{N} .\-()]+$/uD',$name)||mb_strlen($name)>120))
                throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
        }
        $prefixes=array_filter(array_map('trim',preg_split('/[\r\n;,]+/u',$postcodes)?:[]));
        if(count($prefixes)>100)throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
        foreach($prefixes as $prefix){
            if(!preg_match('/^[A-Z0-9 -]{1,16}$/iD',$prefix))
                throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
        }
        $first=self::num($input['estimated_days_min']??'0',365);
        $last=self::num($input['estimated_days_max']??'0',365);
        if($last>0&&$first>$last)throw new \DomainException('COM_MINIMARKET_ZONE_INVALID');
        return [
            'title'=>$title,'country_code'=>$country,'region'=>$region,
            'cities'=>implode("\n",$names),'postcode_prefixes'=>implode("\n",$prefixes),
            'rate_mode'=>$mode,
            'base_fee_cents'=>self::cents($input['base_fee']??'0'),
            'rate_per_unit_cents'=>self::cents($input['rate_per_unit']??'0'),
            'free_from_cents'=>self::cents($input['free_from']??'0'),
            'min_cart_cents'=>self::cents($input['min_cart']??'0'),
            'max_weight_grams'=>self::num($input['max_weight_grams']??'0',4294967295),
            'max_volume_cm3'=>self::num($input['max_volume_cm3']??'0',4294967295),
            'estimated_days_min'=>$first,'estimated_days_max'=>$last,
            'priority'=>self::num($input['priority']??'0',65535),
            'state'=>!empty($input['state'])?1:0
        ];
    }
}