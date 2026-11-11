<?php
declare(strict_types=1);
define('_JEXEC',1);
require_once dirname(__DIR__).'/src/com_minimarket/admin/src/Service/MethodSettings.php';
use MiniMarket\Component\Minimarket\Administrator\Service\MethodSettings;

function expectMethod(bool $condition,string $name):void {
    if(!$condition)throw new RuntimeException('METHOD_SETTINGS_TEST_FAIL: '.$name);
}
function rejectMethod(callable $work,string $name):void {
    try {$work();}catch(DomainException $e){return;}
    throw new RuntimeException('METHOD_SETTINGS_REJECT_FAIL: '.$name);
}

$pickup=MethodSettings::normalize('delivery','pickup',[
    'enabled'=>'1','address'=>"Ленина, 1\nВход со двора"
]);
expectMethod($pickup['delivery_pickup_enabled']===1,'pickup enabled');
expectMethod(str_contains($pickup['delivery_pickup_address'],'Вход'),'pickup address');
$courier=MethodSettings::normalize('delivery','courier',[
    'enabled'=>'1','rate_source'=>'directory',
    'city_rates'=>"Москва|350|5000\nСанкт-Петербург|400|6000"
]);
expectMethod($courier['delivery_rate_source']==='directory','local courier');
expectMethod(str_contains($courier['delivery_city_rates'],'Москва|350'),'city rates');
$zones=MethodSettings::normalize('delivery','courier',[
    'enabled'=>'1','rate_source'=>'zones','city_rates'=>''
]);
expectMethod($zones['delivery_rate_source']==='zones','zone tariffs');
$bank=MethodSettings::normalize('payment','bank_transfer',[
    'enabled'=>'1','instructions'=>'Счёт для оплаты: 123'
]);
expectMethod($bank['payment_bank_transfer_enabled']===1,'bank transfer state');
expectMethod($bank['payment_bank_instructions']==='Счёт для оплаты: 123','bank details');
$receipt=MethodSettings::normalize('payment','on_receipt',['enabled'=>'0']);
expectMethod($receipt['payment_on_receipt_enabled']===0,'receipt disabled');
rejectMethod(fn()=>MethodSettings::normalize('delivery','courier',[
    'enabled'=>'1','rate_source'=>'cdek'
]),'unavailable CDEK API');
rejectMethod(fn()=>MethodSettings::normalize('delivery','courier',[
    'enabled'=>'1','rate_source'=>'directory','city_rates'=>"Москва|350|1000\nмосква|400|2000"
]),'duplicate city');
rejectMethod(fn()=>MethodSettings::normalize('delivery','courier',[
    'enabled'=>'1','rate_source'=>'directory','city_rates'=>'Москва|-1'
]),'negative rate');
rejectMethod(fn()=>MethodSettings::normalize('payment','bank_transfer',[
    'enabled'=>'2'
]),'invalid state');
rejectMethod(fn()=>MethodSettings::normalize('payment','unsupported',['enabled'=>'1']),'unknown method');
echo "MINIMARKET_METHOD_SETTINGS_PASS\n";
