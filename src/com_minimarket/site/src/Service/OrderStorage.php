<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Atomically persist an uncharged order request with immutable SKU/title/price
 * snapshots. New physical orders atomically allocate the actual stock once.
 * External shipping and gateway payment providers are not active.
 */
final class OrderStorage
{
    public static function create(
        DatabaseInterface $db,
        array $contact,
        array $quote,
        string $checkoutKey,
        int $userId,
        array $delivery=[],
        string $source='site',
        string $promoCode=''
    ): int {
        if (!in_array($source,['site','manual','integration'],true)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_INVALID');
        }
        // Contact-only orders may have user_id=0 during UX development.
        if ($userId<0) throw new \DomainException('COM_MINIMARKET_CHECKOUT_INVALID');
        if (!preg_match('/^[a-f0-9]{32}$/D',$checkoutKey)
            || empty($quote['available']) || empty($quote['lines'])
            || count($quote['lines'])>CartState::MAX_LINES) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_CART_INVALID');
        }
        $delivery=array_merge([
            'method'=>'pickup','city'=>'','address'=>'','fee_cents'=>0,
            'rate_source'=>'none','payment_method'=>'on_receipt','zone_id'=>null,
            'country'=>'RU','region'=>'','postcode'=>'','pickup_point_id'=>null
        ],$delivery);
        if (!in_array($delivery['method'],['pickup','courier','digital','pending'],true)
            || !in_array($delivery['payment_method'],['on_receipt','bank_transfer','pending'],true)
            || !in_array($delivery['rate_source'],['none','directory','zones'],true)
            || !is_int($delivery['fee_cents']) || $delivery['fee_cents']<0
            || $delivery['fee_cents']>99999999999
            || ($delivery['pickup_point_id']!==null
                &&(!is_int($delivery['pickup_point_id'])||$delivery['pickup_point_id']<1))
            || ($delivery['method']!=='pickup'&&$delivery['pickup_point_id']!==null)
            || !is_string($delivery['city']) || !is_string($delivery['address'])
            || mb_strlen($delivery['city'])>120
            || mb_strlen($delivery['address'])>512
            || !preg_match('/^[A-Z]{2}$/D',(string)$delivery['country'])
            || mb_strlen((string)$delivery['region'])>120
            || strlen((string)$delivery['postcode'])>16
            || (in_array($delivery['method'],['pickup','pending'],true)
                && $delivery['rate_source']!=='none')
            || ($delivery['method']==='pending'
                && ($delivery['fee_cents']!==0 || $delivery['zone_id']!==null
                    ||$delivery['city']!=='' || $delivery['address']!==''))) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_UNAVAILABLE');
        }
        $recipient=(string)($contact['digital_recipient_email']??'');
        if($recipient!==''&&(strlen($recipient)>254
            ||filter_var($recipient,FILTER_VALIDATE_EMAIL)===false))
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_EMAIL_INVALID');
        $total=0;$units=0;$lines=[];
        foreach ($quote['lines'] as $line) {
            $qty=(int)($line['quantity']??0);
            $price=(int)($line['unit_cents']??-1);
            $pid=(int)($line['product_id']??0);
            $vid=(int)($line['variant_id']??0);
            if (empty($line['valid']) || $pid<1 || $vid<0
                || $qty<1 || $qty>CartState::MAX_QUANTITY
                || $price<0 || $price>999999999999
                || !in_array($line['type']??'', ['simple','bundle'],true)) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_CART_INVALID');
            }
            $kind=(string)($line['delivery_type']??'physical');
            $asset=(string)($line['digital_asset_key']??'');
            $filename=(string)($line['digital_filename']??'');
            $digitalMode=(string)($line['digital_delivery_mode']??'file');
            $digitalNote=trim((string)($line['digital_delivery_note']??''));
            if(!in_array($kind,['physical','digital'],true)
                ||!in_array($digitalMode,['file','license','cloud','manual'],true)
                ||mb_strlen($digitalNote)>2000
                ||($kind==='digital'&&$digitalMode==='file'
                    &&(!preg_match('/^[a-f0-9]{48}\\.(?:pdf|zip|epub|mp3|mp4|txt)$/D',$asset)
                        ||$filename===''||mb_strlen($filename)>255))
                ||($kind==='digital'&&$digitalMode!=='file'&&($asset!==''||$filename!==''))
                ||($kind==='physical'&&($asset!==''||$filename!=='')))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_CART_INVALID');
            $sku=(string)($line['sku']??'');
            $title=(string)($line['title']??'');
            if ($title==='' || mb_strlen($title)>255 || mb_strlen($sku)>100) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_CART_INVALID');
            }
            $total += $qty*$price;
            $units += $qty;
            $lines[]=['product_id'=>$pid,'variant_id'=>$vid,'quantity'=>$qty,
                'unit_cents'=>$price,'line_cents'=>$qty*$price,'sku'=>$sku,
                'title'=>$title,'delivery_type'=>$kind,
                'digital_asset_key'=>$asset,'digital_filename'=>$filename,
                'digital_delivery_mode'=>$digitalMode,'digital_delivery_note'=>$digitalNote];
        }
        $anyPhysical=count(array_filter($lines,static fn($r)=>$r['delivery_type']==='physical'))>0;
        if(($delivery['method']==='digital' && $anyPhysical)
           ||($delivery['method']!=='digital' && !$anyPhysical)
           ||($delivery['method']==='digital'
               && !in_array($delivery['payment_method'],['bank_transfer','pending'],true)))
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_UNAVAILABLE');
        if ($total!==(int)($quote['total_cents']??-1)
            || $units!==(int)($quote['units']??-1)
            || $units>CartState::MAX_UNITS) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_CART_INVALID');
        }

        if(!class_exists(PurchaseCost::class))require_once __DIR__.'/PurchaseCost.php';
        $db->transactionStart();
        try {
            // Procurement costs are historical snapshots. Unknown means NULL.
            // Bundle cost derives from the cost of its current components.
            $costs=\MiniMarket\Component\Minimarket\Site\Service\PurchaseCost::snapshot($db,$lines);
            // Row lock serializes promotion usage-limit checks across checkouts.
            $promo=PromotionService::quote($db,$promoCode,$lines,$total,$userId,$contact,true,$checkoutKey);
            $discount=(int)($promo['discount_cents']??0);
            if($discount>$total)throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
            $columns=['checkout_key','user_id','customer_name','phone','email',
                'customer_comment','delivery_method','pickup_point_id','delivery_city','delivery_country',
                'delivery_region','delivery_postcode','delivery_address',
                'delivery_rate_source','delivery_zone_id','delivery_fee_cents','payment_method',
                'digital_recipient_email','order_source','status','currency','items_total_cents','promo_code_id',
                'promo_code','discount_cents','total_cents','item_count','created_at'];
            $values=[
                $db->quote($checkoutKey),(string)$userId,
                $db->quote($contact['name']),$db->quote($contact['phone']),
                $db->quote($contact['email']),$db->quote($contact['comment']),
                $db->quote($delivery['method']),
                $delivery['pickup_point_id']===null?'NULL':(string)$delivery['pickup_point_id'],
                $db->quote($delivery['city']),
                $db->quote($delivery['country']),$db->quote($delivery['region']),
                $db->quote($delivery['postcode']),$db->quote($delivery['address']),$db->quote($delivery['rate_source']),
                $delivery['zone_id']===null?'NULL':(string)(int)$delivery['zone_id'],
                (string)$delivery['fee_cents'],$db->quote($delivery['payment_method']),
                $db->quote($recipient),$db->quote($source),$db->quote('new'),$db->quote('RUB'),(string)$total,
                $promo===null?'NULL':(string)$promo['id'],$db->quote($promo['code']??''),
                (string)$discount,(string)($total+$delivery['fee_cents']-$discount),
                (string)$units,$db->quote(gmdate('Y-m-d H:i:s'))
            ];
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_orders'))
                ->columns($db->quoteName($columns))->values(implode(',',$values));
            $db->setQuery($q);$db->execute();
            $orderId=(int)$db->insertid();
            if ($orderId<1) throw new \RuntimeException('Order insert did not create ID');
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_order_items'))
                ->columns($db->quoteName(['order_id','product_id','variant_id',
                    'sku','title','unit_cents','quantity','line_cents',
                    'unit_cost_cents','line_cost_cents',
                    'delivery_type','digital_asset_key','digital_filename',
                    'digital_delivery_mode','digital_delivery_note']));
            foreach ($lines as $line) {
                $q->values(implode(',',[
                    (string)$orderId,(string)$line['product_id'],(string)$line['variant_id'],
                    $db->quote($line['sku']),$db->quote($line['title']),
                    (string)$line['unit_cents'],(string)$line['quantity'],
                    (string)$line['line_cents'],
                    $costs[$line['product_id']]===null?'NULL':(string)$costs[$line['product_id']],
                    $costs[$line['product_id']]===null?'NULL':(string)($costs[$line['product_id']]*$line['quantity']),
                    $db->quote($line['delivery_type']),
                    $db->quote($line['digital_asset_key']),$db->quote($line['digital_filename']),
                    $db->quote($line['digital_delivery_mode']),$db->quote($line['digital_delivery_note'])
                ]));
            }
            $db->setQuery($q);$db->execute();
            foreach(($contact['custom_fields']??[]) as $field) {
                $insert=$db->getQuery(true)->insert($db->quoteName('#__minimarket_order_custom_values'))
                    ->columns($db->quoteName(['order_id','field_code','field_title','field_value']))
                    ->values(implode(',',[(string)$orderId,$db->quote($field['code']),
                        $db->quote($field['title']),$db->quote($field['value'])]));
                $db->setQuery($insert);$db->execute();
            }
            if($promo!==null)PromotionService::redeem($db,$promo,$orderId);
            // Allocation is part of the SAME transaction as the order and all
            // line snapshots. A concurrent checkout cannot sell the final SKU twice.
            if($anyPhysical) {
                StockReservation::allocate($db,$orderId,$lines,
                    $delivery['method']==='pickup');
            }
            // The first entry in the order timeline exists even if the order has
            // not yet been paid or shipped. No external service is invoked.
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_order_events'))
                ->columns($db->quoteName(['order_id','actor_user_id','event_type',
                    'old_value','new_value','created_at']))
                ->values(implode(',',[(string)$orderId,'0',$db->quote('order_created'),
                    $db->quote(''),$db->quote($source),'UTC_TIMESTAMP()']));
            $db->setQuery($q);$db->execute();
            $db->transactionCommit();
            NotificationSender::emit($orderId,'order_created');
            return $orderId;
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
    }
}
