<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Built-in delivery/payment method settings, stored in the existing Joomla
 * component options. No duplicate method tables and no core modifications.
 */
final class MethodSettings
{
    public static function normalize(string $scope,string $method,array $input): array
    {
        $enabled=(string)($input['enabled']??'0');
        if(!in_array($enabled,['0','1'],true)) {
            throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
        }
        if($scope==='delivery' && $method==='pickup'){
            $address=self::cleanText((string)($input['address']??''),512);
            $price=self::amountCents((string)($input['fee']??'0'));
            $schedule=self::cleanText((string)($input['schedule']??''),2000);
            $map=trim((string)($input['map_url']??''));
            if($map!=='' && (strlen($map)>1024 || !filter_var($map,FILTER_VALIDATE_URL)
                || strtolower((string)parse_url($map,PHP_URL_SCHEME))!=='https'
                || parse_url($map,PHP_URL_USER)!==null
                || parse_url($map,PHP_URL_PASS)!==null))
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            return ['delivery_pickup_enabled'=>(int)$enabled,
                'delivery_pickup_address'=>$address,
                'delivery_pickup_fee_cents'=>$price,
                'delivery_pickup_schedule'=>$schedule,
                'delivery_pickup_map_url'=>$map];
        }
        if($scope==='delivery' && $method==='courier'){
            $source=(string)($input['rate_source']??'directory');
            if(!in_array($source,['directory','zones'],true)) {
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            }
            $rates=trim(str_replace(["\r\n","\r"],"\n",(string)($input['city_rates']??'')));
            if(mb_strlen($rates)>15000) {
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            }
            self::checkCityRates($rates);
            return ['delivery_courier_enabled'=>(int)$enabled,
                'delivery_rate_source'=>$source,'delivery_city_rates'=>$rates];
        }
        if($scope==='registration' && $method==='email'){
            $primary=(string)($input['primary']??'0');
            if(!in_array($primary,['0','1'],true))
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            return ['checkout_verification_method'=>$primary==='1'?'email':''];
        }
        if($scope==='payment' && in_array($method,['on_receipt','bank_transfer'],true)){
            $result=['payment_'.$method.'_enabled'=>(int)$enabled];
            if($method==='bank_transfer')
                $result['payment_bank_instructions']=self::cleanText((string)($input['instructions']??''),2000);
            if(array_key_exists('allowed_deliveries',$input)){
                $choices=$input['allowed_deliveries'];
                if(!is_array($choices)||count($choices)>3)
                    throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
                $available=$method==='on_receipt'?['pickup','courier']:['pickup','courier','digital'];
                foreach($choices as $choice)
                    if(!is_string($choice))
                        throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
                if(count(array_unique($choices))!==count($choices)
                    ||array_diff($choices,$available))
                    throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
                $result['payment_'.$method.'_allowed_deliveries']=implode(',',array_values(array_intersect($available,$choices)));
            }
            return $result;
        }
        throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
    }

    public static function save(DatabaseInterface $db,array $changes): void
    {
        $allowed=[
            'delivery_pickup_enabled','delivery_pickup_address',
            'delivery_pickup_fee_cents','delivery_pickup_schedule','delivery_pickup_map_url',
            'delivery_courier_enabled','delivery_rate_source','delivery_city_rates',
            'payment_on_receipt_enabled','payment_bank_transfer_enabled',
            'payment_on_receipt_allowed_deliveries','payment_bank_transfer_allowed_deliveries',
            'payment_bank_instructions','checkout_verification_method',
            'notification_email_enabled','notification_email_events',
            'notification_plugin_events'
        ];
        foreach($changes as $key=>$value){
            if(!in_array($key,$allowed,true))throw new \LogicException('Unrecognized method option');
        }
        $db->transactionStart();
        try {
            $db->setQuery('SELECT extension_id, params FROM '
                .$db->quoteName('#__extensions')
                .' WHERE type='.$db->quote('component')
                .' AND element='.$db->quote('com_minimarket').' FOR UPDATE');
            $row=$db->loadAssoc();
            if(!$row)throw new \RuntimeException('MiniMarket extension missing');
            $params=json_decode((string)$row['params'],true);
            if(!is_array($params))$params=[];
            foreach($changes as $key=>$value)$params[$key]=$value;
            $json=json_encode($params,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
            $query=$db->getQuery(true)->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params').'='.$db->quote($json))
                ->where($db->quoteName('extension_id').'='.(int)$row['extension_id']);
            $db->setQuery($query);$db->execute();
            $db->transactionCommit();
        } catch(\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
    }

    private static function amountCents(string $raw): int
    {
        $raw=trim($raw);
        if(!preg_match('/^(0|[1-9][0-9]{0,8})(?:[.,][0-9]{1,2})?$/D',$raw))
            throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
        $parts=explode('.',str_replace(',','.',$raw),2);
        $cents=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
        if($cents<0||$cents>99999999999)
            throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
        return $cents;
    }

    private static function cleanText(string $text,int $limit): string
    {
        $text=trim(strip_tags($text));
        if(mb_strlen($text)>$limit || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u',$text)){
            throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
        }
        return $text;
    }

    private static function checkCityRates(string $rates): void
    {
        if($rates==='')return;
        $lines=explode("\n",$rates);
        if(count($lines)>300)throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
        $seen=[];
        foreach($lines as $line){
            if(trim($line)==='')continue;
            $parts=explode('|',$line);
            if(count($parts)<2||count($parts)>3)throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            $city=trim($parts[0]);
            $price=trim($parts[1]);
            $free=trim($parts[2]??'');
            if($city===''||mb_strlen($city)>120
                ||!preg_match('/^[\p{L}\p{N} .\-()]+$/uD',$city)
                ||!preg_match('/^[0-9]{1,8}(?:[.,][0-9]{1,2})?$/D',$price)
                ||($free!==''&&!preg_match('/^[0-9]{1,8}(?:[.,][0-9]{1,2})?$/D',$free))){
                throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            }
            $key=mb_strtolower($city);
            if(isset($seen[$key]))throw new \DomainException('COM_MINIMARKET_METHOD_SETTINGS_INVALID');
            $seen[$key]=true;
        }
    }
}
