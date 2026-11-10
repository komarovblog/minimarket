<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Atomically persist an uncharged order request with immutable SKU/title/price
 * snapshots. NEVER reserve or deduct stock. Shipping/payment are not active.
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
        string $source='site'
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
            'rate_source'=>'none','payment_method'=>'on_receipt'
        ],$delivery);
        if (!in_array($delivery['method'],['pickup','courier'],true)
            || !in_array($delivery['payment_method'],['on_receipt','bank_transfer'],true)
            || !in_array($delivery['rate_source'],['none','directory'],true)
            || !is_int($delivery['fee_cents']) || $delivery['fee_cents']<0
            || $delivery['fee_cents']>99999999999
            || !is_string($delivery['city']) || !is_string($delivery['address'])
            || mb_strlen($delivery['city'])>120
            || mb_strlen($delivery['address'])>512
            || ($delivery['method']==='pickup' && $delivery['fee_cents']!==0)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_DELIVERY_UNAVAILABLE');
        }
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
            $sku=(string)($line['sku']??'');
            $title=(string)($line['title']??'');
            if ($title==='' || mb_strlen($title)>255 || mb_strlen($sku)>100) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_CART_INVALID');
            }
            $total += $qty*$price;
            $units += $qty;
            $lines[]=['product_id'=>$pid,'variant_id'=>$vid,'quantity'=>$qty,
                'unit_cents'=>$price,'line_cents'=>$qty*$price,'sku'=>$sku,
                'title'=>$title];
        }
        if ($total!==(int)($quote['total_cents']??-1)
            || $units!==(int)($quote['units']??-1)
            || $units>CartState::MAX_UNITS) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_CART_INVALID');
        }

        $db->transactionStart();
        try {
            $columns=['checkout_key','user_id','customer_name','phone','email',
                'customer_comment','delivery_method','delivery_city','delivery_address',
                'delivery_rate_source','delivery_fee_cents','payment_method',
                'order_source','status','currency','items_total_cents','total_cents','item_count','created_at'];
            $values=[
                $db->quote($checkoutKey),(string)$userId,
                $db->quote($contact['name']),$db->quote($contact['phone']),
                $db->quote($contact['email']),$db->quote($contact['comment']),
                $db->quote($delivery['method']),$db->quote($delivery['city']),
                $db->quote($delivery['address']),$db->quote($delivery['rate_source']),
                (string)$delivery['fee_cents'],$db->quote($delivery['payment_method']),
                $db->quote($source),$db->quote('new'),$db->quote('RUB'),(string)$total,
                (string)($total+$delivery['fee_cents']),
                (string)$units,$db->quote(gmdate('Y-m-d H:i:s'))
            ];
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_orders'))
                ->columns($db->quoteName($columns))->values(implode(',',$values));
            $db->setQuery($q);$db->execute();
            $orderId=(int)$db->insertid();
            if ($orderId<1) throw new \RuntimeException('Order insert did not create ID');
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_order_items'))
                ->columns($db->quoteName(['order_id','product_id','variant_id',
                    'sku','title','unit_cents','quantity','line_cents']));
            foreach ($lines as $line) {
                $q->values(implode(',',[
                    (string)$orderId,(string)$line['product_id'],(string)$line['variant_id'],
                    $db->quote($line['sku']),$db->quote($line['title']),
                    (string)$line['unit_cents'],(string)$line['quantity'],
                    (string)$line['line_cents']
                ]));
            }
            $db->setQuery($q);$db->execute();
            $db->transactionCommit();
            return $orderId;
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
    }
}
