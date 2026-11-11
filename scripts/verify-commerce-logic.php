<?php
declare(strict_types=1);
define('_JEXEC', 1);

require_once dirname(__DIR__) . '/src/com_minimarket/admin/src/Service/DeliveryZoneData.php';
require_once dirname(__DIR__) . '/src/com_minimarket/admin/src/Service/PromoCodeData.php';

use MiniMarket\Component\Minimarket\Administrator\Service\DeliveryZoneData;
use MiniMarket\Component\Minimarket\Administrator\Service\PromoCodeData;

function ensure(bool $ok, string $case): void {
    if (!$ok) throw new RuntimeException('COMMERCE_TEST_FAIL: ' . $case);
}
function rejects(callable $call, string $case): void {
    try {
        $call();
    } catch (DomainException $e) {
        return;
    }
    throw new RuntimeException('COMMERCE_TEST_FAIL: invalid accepted: ' . $case);
}
$zone=[
    'title'=>'Санкт-Петербург', 'country_code'=>'ru',
    'region'=>'Ленинградская область','cities'=>"Санкт-Петербург\nПушкин",
    'postcode_prefixes'=>"190\n196",'rate_mode'=>'per_kg',
    'base_fee'=>'350.50','rate_per_unit'=>'49.90',
    'free_from'=>'5000','min_cart'=>'0',
    'max_weight_grams'=>'25000','max_volume_cm3'=>'100000',
    'estimated_days_min'=>'1','estimated_days_max'=>'3',
    'priority'=>'10','state'=>'1'
];
$rate=DeliveryZoneData::normalize($zone);
ensure($rate['country_code']==='RU','country uppercase');
ensure($rate['base_fee_cents']===35050,'monetary cents');
ensure($rate['rate_per_unit_cents']===4990,'weight tariff cents');
ensure($rate['free_from_cents']===500000,'free shipping threshold');
ensure($rate['max_weight_grams']===25000,'weight limit');
ensure($rate['state']===1,'publication');
rejects(fn()=>DeliveryZoneData::normalize(array_replace($zone,['rate_mode'=>'javascript'])),'invalid rate type');
rejects(fn()=>DeliveryZoneData::normalize(array_replace($zone,['estimated_days_min'=>'5'])),'invalid delivery days');
rejects(fn()=>DeliveryZoneData::normalize(array_replace($zone,['base_fee'=>'-3'])),'negative cost');
rejects(fn()=>DeliveryZoneData::normalize(array_replace($zone,['cities'=>''])),'no cities');

$promo=[
    'code'=>'WELCOME10','discount_type'=>'percent','percent_value'=>'10.25',
    'starts_at'=>'2026-11-11T09:00','ends_at'=>'2026-11-30T22:30',
    'min_cart'=>'1000','usage_limit'=>'100','per_customer_limit'=>'1',
    'applies_to'=>'category','target_ids'=>'10, 15, 10','combinable'=>0
];
$rules=PromoCodeData::normalize($promo);
ensure($rules['discount_type']==='percent','percent mode');
ensure($rules['percent_value']===10.25,'percent amount');
ensure($rules['amount_cents']===0,'no fixed discount in percent mode');
ensure($rules['starts_at']==='2026-11-11 09:00:00','start date UTC');
ensure($rules['ends_at']==='2026-11-30 22:30:00','end date UTC');
ensure($rules['min_cart_cents']===100000,'minimum cart');
ensure($rules['usage_limit']===100 && $rules['per_customer_limit']===1,'usage limits');
ensure(json_decode($rules['target_ids_json'],true)===[10,15],'unique category IDs');
rejects(fn()=>PromoCodeData::normalize(array_replace($promo,['percent_value'=>'101'])),'percent over 100');
rejects(fn()=>PromoCodeData::normalize(array_replace($promo,['ends_at'=>'2026-11-01T08:00'])),'inverted dates');
rejects(fn()=>PromoCodeData::normalize(array_replace($promo,['target_ids'=>'10,wrong'])),'invalid target');
rejects(fn()=>PromoCodeData::normalize(array_replace($promo,['usage_limit'=>'-1'])),'negative usages');
$fixed=PromoCodeData::normalize(array_replace($promo,[
    'code'=>'SALE-50','discount_type'=>'fixed','amount'=>'50.50',
    'applies_to'=>'all','target_ids'=>''
]));
ensure($fixed['amount_cents']===5050,'fixed amount');
echo "MINIMARKET_COMMERCE_NORMALIZATION_PASS: zones=8 promo=12\n";
