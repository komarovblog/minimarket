<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/**
 * Local deterministic tariffs only. The CDEK selection remains fail-closed
 * until a real authenticated rate provider has been integrated and tested.
 */
final class CheckoutDelivery
{
    /** @return array<string,array{city:string,fee_cents:int}> normalized city => tariff */
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
            if (count($parts)!==2) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            }
            $city=trim($parts[0]);
            $price=trim($parts[1]);
            if ($city==='' || mb_strlen($city)>120 ||
                !preg_match('/^[\p{L}\p{N} .\-()]+$/uD',$city) ||
                !preg_match('/^[0-9]{1,8}(?:[.,][0-9]{1,2})?$/D',$price)) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            }
            $key=mb_strtolower($city,'UTF-8');
            if (isset($rates[$key])) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            }
            $decimal=str_replace(',','.',$price);
            $split=explode('.',$decimal,2);
            $cents=((int)$split[0]*100)+(int)str_pad($split[1]??'',2,'0');
            $rates[$key]=['city'=>$city,'fee_cents'=>$cents];
        }
        return $rates;
    }

    /** @return array<string,array<string,mixed>> */
    public static function methods(object $params): array
    {
        $methods=[];
        if ((int)$params->get('delivery_pickup_enabled',1)===1) {
            $methods['pickup']=['fee_cents'=>0,
                'address'=>trim((string)$params->get('delivery_pickup_address',''))];
        }
        if ((int)$params->get('delivery_courier_enabled',0)===1
            && (string)$params->get('delivery_rate_source','directory')==='directory') {
            $rates=self::rates($params);
            if ($rates) $methods['courier']=['rates'=>$rates];
        }
        return $methods;
    }

    /** @return array<string,string> */
    public static function payments(object $params): array
    {
        $methods=[];
        if ((int)$params->get('payment_on_receipt_enabled',1)===1) {
            $methods['on_receipt']='COM_MINIMARKET_PAYMENT_ON_RECEIPT';
        }
        if ((int)$params->get('payment_bank_transfer_enabled',0)===1) {
            $methods['bank_transfer']='COM_MINIMARKET_PAYMENT_BANK_TRANSFER';
        }
        return $methods;
    }

    /** Server-validated order snapshot; never accept any browser-supplied fee. */
    public static function quote(object $params,array $input): array
    {
        $methods=self::methods($params);
        $payments=self::payments($params);
        $method=(string)($input['delivery_method']??'');
        $payment=(string)($input['payment_method']??'');
        if (!$methods || !$payments || !array_key_exists($method,$methods)
            || !array_key_exists($payment,$payments)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_UNAVAILABLE');
        }
        if ($method==='pickup') {
            $address=$methods['pickup']['address'];
            if (mb_strlen($address)>512) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CONFIG_INVALID');
            }
            return ['method'=>'pickup','city'=>'','address'=>$address,
                'fee_cents'=>0,'rate_source'=>'none','payment_method'=>$payment];
        }

        $city=trim((string)($input['delivery_city']??''));
        $address=trim(strip_tags((string)($input['delivery_address']??'')));
        if ($city==='' || mb_strlen($city)>120 || mb_strlen($address)<5
            || mb_strlen($address)>512 || preg_match('/[\x00-\x1f<>]/u',$address)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_ADDRESS_INVALID');
        }
        $key=mb_strtolower($city,'UTF-8');
        if (!isset($methods['courier']['rates'][$key])) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_CITY_INVALID');
        }
        $selected=$methods['courier']['rates'][$key];
        return ['method'=>'courier','city'=>$selected['city'],'address'=>$address,
            'fee_cents'=>$selected['fee_cents'],'rate_source'=>'directory',
            'payment_method'=>$payment];
    }
}
