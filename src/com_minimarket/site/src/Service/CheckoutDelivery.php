<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Local deterministic tariffs only. The CDEK selection remains fail-closed
 * until a real authenticated rate provider has been integrated and tested.
 */
final class CheckoutDelivery
{
    /** @return array<string,array{city:string,fee_cents:int,free_from_cents:int}> normalized city => tariff */
    public static function rates(object $params): array
    {
        $source=trim((string)$params->get('delivery_city_rates',''));
        if ($source==='') return [];
        $lines=preg_split('/\r\n|\n|\r/',$source) ?: [];
        if (count($lines)>300) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
        }
        $rates=[];
        foreach ($lines as $line) {
            if (trim($line)==='') continue;
            $parts=explode('|',$line);
            if (count($parts)<2 || count($parts)>3) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            }
            $city=trim($parts[0]);
            $price=trim($parts[1]);
            $freeFrom=trim($parts[2]??'');
            if ($city==='' || mb_strlen($city)>120 ||
                !preg_match('/^[\p{L}\p{N} .\-()]+$/uD',$city) ||
                !preg_match('/^[0-9]{1,8}(?:[.,][0-9]{1,2})?$/D',$price)
                || ($freeFrom!==''&&!preg_match('/^[0-9]{1,8}(?:[.,][0-9]{1,2})?$/D',$freeFrom))) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            }
            $key=mb_strtolower($city,'UTF-8');
            if (isset($rates[$key])) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            }
            $decimal=str_replace(',','.',$price);
            $split=explode('.',$decimal,2);
            $cents=((int)$split[0]*100)+(int)str_pad($split[1]??'',2,'0');
            $partsFree=explode('.',str_replace(',','.',$freeFrom),2);
            $freeCents=$freeFrom===''?0:((int)$partsFree[0]*100)+(int)str_pad($partsFree[1]??'',2,'0');
            $rates[$key]=['city'=>$city,'fee_cents'=>$cents,'free_from_cents'=>$freeCents];
        }
        return $rates;
    }

    /** @return array<string,array<string,mixed>> */
    public static function methods(object $params,bool $needsPhysical=true): array
    {
        if(!$needsPhysical)return ['digital'=>['fee_cents'=>0]];
        $methods=[];
        if ((int)$params->get('delivery_pickup_enabled',1)===1) {
            $fee=(int)$params->get('delivery_pickup_fee_cents',0);
            if($fee<0||$fee>99999999999)
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            $methods['pickup']=['fee_cents'=>$fee,
                'address'=>trim((string)$params->get('delivery_pickup_address','')),
                'schedule'=>trim((string)$params->get('delivery_pickup_schedule','')),
                'map_url'=>trim((string)$params->get('delivery_pickup_map_url',''))];
        }
        if ((int)$params->get('delivery_courier_enabled',0)===1) {
            $source=(string)$params->get('delivery_rate_source','directory');
            if($source==='directory'){
                $rates=self::rates($params);
                if($rates)$methods['courier']=['rates'=>$rates,'source'=>'directory'];
            }elseif($source==='zones'){
                $db=Factory::getContainer()->get(DatabaseInterface::class);
                if(DeliveryZones::all($db))$methods['courier']=['source'=>'zones'];
            }
            // External carrier plugins may register their own rates later.
        }
        return $methods;
    }

    /** @return array<string,string> */
    public static function payments(object $params,bool $digitalOnly=false): array
    {
        $methods=[];
        if (!$digitalOnly && (int)$params->get('payment_on_receipt_enabled',1)===1) {
            $methods['on_receipt']='COM_MINIMARKET_PAYMENT_ON_RECEIPT';
        }
        if ((int)$params->get('payment_bank_transfer_enabled',0)===1) {
            $methods['bank_transfer']='COM_MINIMARKET_PAYMENT_BANK_TRANSFER';
        }
        return $methods;
    }

    /**
     * The only payment × delivery compatibility matrix for builtin methods.
     * Missing option means historical default (backwards compatible).
     */
    public static function allowedForPayment(object $params,string $payment):array
    {
        $defaults=match($payment){
            'on_receipt'=>['pickup','courier'],
            'bank_transfer'=>['pickup','courier','digital'],
            default=>[]
        };
        if(!$defaults)return [];
        $raw=$params->get('payment_'.$payment.'_allowed_deliveries',null);
        if($raw===null)return $defaults;
        if(!is_string($raw)||strlen($raw)>40)
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
        $selected=$raw===''?[]:explode(',',$raw);
        if(count($selected)!==count(array_unique($selected))
            ||array_diff($selected,$defaults))
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
        return array_values(array_intersect($defaults,$selected));
    }

    public static function compatible(object $params,string $payment,string $delivery):bool
    {
        if($payment==='pending'||$delivery==='pending')return true;
        return in_array($delivery,self::allowedForPayment($params,$payment),true);
    }

    /**
     * Explicitly skipped choices become "pending", not a fabricated
     * courier/pickup or paid status. Shipping remains 0 until staff resolves it.
     * The admin has intentionally accepted missing checkout information.
     */
    public static function checkoutQuote(
        object $params,array $input,int $itemsTotalCents=0,
        int $weightGrams=0,int $volumeCm3=0,bool $needsPhysical=true
    ):array {
        $deliveryEnabled=CheckoutSettings::step($params,'delivery');
        $paymentEnabled=CheckoutSettings::step($params,'payment');
        $payments=$paymentEnabled?self::payments($params,!$needsPhysical):[];
        if($paymentEnabled){
            $selected=(string)($input['payment_method']??'');
            $payment=count($payments)===1?(string)array_key_first($payments):$selected;
            if(!array_key_exists($payment,$payments))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_UNAVAILABLE');
        }else{
            $payment='pending';
        }
        if(!$deliveryEnabled){
            return ['method'=>$needsPhysical?'pending':'digital',
                'city'=>'','address'=>'','country'=>'RU','region'=>'','postcode'=>'',
                'zone_id'=>null,'fee_cents'=>0,'rate_source'=>'none',
                'payment_method'=>$payment];
        }
        $methods=self::methods($params,$needsPhysical);
        $method=(string)($input['delivery_method']??'');
        if(count($methods)===1
            &&(isset($methods['pickup'])||isset($methods['digital'])))
            $method=(string)array_key_first($methods);
        $input['delivery_method']=$method;
        $input['payment_method']=$payment;
        return self::quote($params,$input,$itemsTotalCents,$weightGrams,$volumeCm3,
            $needsPhysical,!$paymentEnabled);
    }

    /** Server-validated order snapshot; never accept any browser-supplied fee. */
    public static function quote(object $params,array $input,int $itemsTotalCents=0,int $weightGrams=0,int $volumeCm3=0,bool $needsPhysical=true,bool $allowPendingPayment=false): array
    {
        $methods=self::methods($params,$needsPhysical);
        $payments=self::payments($params,!$needsPhysical);
        $method=(string)($input['delivery_method']??'');
        $payment=(string)($input['payment_method']??'');
        if (!$methods || !array_key_exists($method,$methods)
            || ($allowPendingPayment && $payment==='pending'
                ? false : (!$payments || !array_key_exists($payment,$payments)))
            || !self::compatible($params,$payment,$method)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_UNAVAILABLE');
        }
        if ($method==='digital') {
            return ['method'=>'digital','city'=>'','address'=>'','country'=>'RU',
                'region'=>'','postcode'=>'','zone_id'=>null,'fee_cents'=>0,
                'rate_source'=>'none','payment_method'=>$payment];
        }
        if ($method==='pickup') {
            $address=$methods['pickup']['address'];
            if (mb_strlen($address)>512) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            }
            return ['method'=>'pickup','city'=>'','address'=>$address,
                'country'=>'RU','region'=>'','postcode'=>'','zone_id'=>null,
                'fee_cents'=>$methods['pickup']['fee_cents'],'rate_source'=>'none','payment_method'=>$payment];
        }

        $city=trim((string)($input['delivery_city']??''));
        $address=trim(strip_tags((string)($input['delivery_address']??'')));
        if ($city==='' || mb_strlen($city)>120 || mb_strlen($address)<5
            || mb_strlen($address)>512 || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f<>]/u',$address)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_ADDRESS_INVALID');
        }
        if(($methods['courier']['source']??'')==='zones'){
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $zone=DeliveryZones::quote($db,$input,$itemsTotalCents,$weightGrams,$volumeCm3);
            return ['method'=>'courier','city'=>$zone['city'],
                'country'=>$zone['country'],'region'=>$zone['region'],
                'postcode'=>$zone['postcode'],'zone_id'=>$zone['zone_id'],
                'address'=>$address,'fee_cents'=>$zone['fee_cents'],
                'rate_source'=>'zones','payment_method'=>$payment];
        }
        $key=mb_strtolower($city,'UTF-8');
        if (!isset($methods['courier']['rates'][$key])) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CITY_INVALID');
        }
        $selected=$methods['courier']['rates'][$key];
        if($itemsTotalCents<0)throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
        $fee=$selected['fee_cents'];
        if($selected['free_from_cents']>0 && $itemsTotalCents>=$selected['free_from_cents'])$fee=0;
        return ['method'=>'courier','city'=>$selected['city'],'address'=>$address,
            'country'=>'RU','region'=>'','postcode'=>'','zone_id'=>null,
            'fee_cents'=>$fee,'rate_source'=>'directory',
            'payment_method'=>$payment];
    }
}
