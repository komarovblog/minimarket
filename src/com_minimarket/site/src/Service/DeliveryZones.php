<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Published delivery zones, deterministic priority and server-side pricing. */
final class DeliveryZones
{
    public static function all(DatabaseInterface $db,bool $publishedOnly=true): array
    {
        $query=$db->getQuery(true)->select('*')
            ->from($db->quoteName('#__minimarket_delivery_zones'))
            ->order('priority DESC,id ASC');
        if($publishedOnly)$query->where('state=1');
        $db->setQuery($query,0,2000);
        return (array)$db->loadAssocList();
    }

    public static function cities(DatabaseInterface $db): array
    {
        $cities=[];
        foreach(self::all($db) as $zone){
            foreach(preg_split('/[\r\n;,]+/u',(string)$zone['cities']) ?: [] as $name){
                $name=trim($name);
                if($name!==''&&$name!=='*')$cities[mb_strtolower($name)]=$name;
            }
        }
        return array_values($cities);
    }

    /** Exact zone match, postal prefix, subtotal, quantity, weight and volume bounds. */
    public static function quote(
        DatabaseInterface $db,array $input,int $subtotal,int $grams,int $cm3
    ): array {
        $city=trim((string)($input['delivery_city']??''));
        $region=trim((string)($input['delivery_region']??''));
        $postcode=trim((string)($input['delivery_postcode']??''));
        $country=strtoupper(trim((string)($input['delivery_country']??'RU')));
        if($city===''||mb_strlen($city)>120||mb_strlen($region)>120||
            strlen($postcode)>16||!preg_match('/^[A-Z]{2}$/D',$country)||
            ($postcode!==''&&!preg_match('/^[A-Z0-9 -]+$/iD',$postcode))||
            $subtotal<0||$grams<0||$cm3<0)
            throw new \DomainException('COM_MINIMARKET_DELIVERY_ZONE_INVALID');
        foreach(self::all($db) as $zone){
            if((string)$zone['country_code']!==$country)continue;
            if(trim((string)$zone['region'])!==''
                && mb_strtolower(trim((string)$zone['region']))!==mb_strtolower($region))continue;
            $matchingCities=array_filter(array_map('trim',preg_split('/[\r\n;,]+/u',(string)$zone['cities'])?:[]));
            $cityFound=false;
            foreach($matchingCities as $candidate){
                if($candidate==='*'||mb_strtolower($candidate)===mb_strtolower($city)){
                    $cityFound=true;break;
                }
            }
            if(!$cityFound)continue;
            $prefixes=array_filter(array_map('trim',preg_split('/[\r\n;,]+/u',(string)$zone['postcode_prefixes'])?:[]));
            if($prefixes){
                $matches=false;
                foreach($prefixes as $prefix){
                    if($postcode!==''&&str_starts_with(strtoupper($postcode),strtoupper($prefix))){
                        $matches=true;break;
                    }
                }
                if(!$matches)continue;
            }
            if($subtotal<(int)$zone['min_cart_cents'])continue;
            if((int)$zone['max_weight_grams']>0 && $grams>(int)$zone['max_weight_grams'])continue;
            if((int)$zone['max_volume_cm3']>0 && $cm3>(int)$zone['max_volume_cm3'])continue;
            $mode=(string)$zone['rate_mode'];
            $fee=(int)$zone['base_fee_cents'];
            if($mode==='per_kg'){
                if($grams<1)throw new \DomainException('COM_MINIMARKET_DELIVERY_WEIGHT_REQUIRED');
                $fee+=intdiv($grams+999,1000)*(int)$zone['rate_per_unit_cents'];
            }elseif($mode==='per_liter'){
                if($cm3<1)throw new \DomainException('COM_MINIMARKET_DELIVERY_VOLUME_REQUIRED');
                $fee+=intdiv($cm3+999,1000)*(int)$zone['rate_per_unit_cents'];
            }elseif($mode!=='fixed'){
                throw new \DomainException('COM_MINIMARKET_DELIVERY_ZONE_INVALID');
            }
            if($zone['free_from_cents']>0&&$subtotal>=(int)$zone['free_from_cents'])$fee=0;
            if($fee<0||$fee>99999999999)
                throw new \DomainException('COM_MINIMARKET_DELIVERY_ZONE_INVALID');
            return ['zone_id'=>(int)$zone['id'],'fee_cents'=>$fee,
                'city'=>$city,'country'=>$country,'region'=>$region,'postcode'=>$postcode,
                'days_min'=>(int)$zone['estimated_days_min'],
                'days_max'=>(int)$zone['estimated_days_max']];
        }
        throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CITY_INVALID');
    }
}