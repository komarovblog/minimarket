<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Private commercial order summary ("invoice"), never a fiscal receipt.
 * Uses only already stored order/line snapshots; no dynamic catalog prices.
 */
final class OrderDocuments
{
    public static function snapshot(array $order, array $items): array
    {
        $id=(int)($order['id']??0);
        $currency=(string)($order['currency']??'');
        if($id<1||!preg_match('/^[A-Z]{3}$/D',$currency)||count($items)<1)
            throw new \DomainException('COM_MINIMARKET_DOCUMENT_INVALID');
        $lines=[];$sum=0;
        foreach($items as $item){
            $quantity=(int)($item['quantity']??0);
            $unit=(int)($item['unit_cents']??-1);
            $amount=(int)($item['line_cents']??-1);
            if($quantity<1||$unit<0||$amount<0||$quantity*$unit!==$amount
              ||trim((string)($item['title']??''))==='')
                throw new \DomainException('COM_MINIMARKET_DOCUMENT_INVALID');
            $sum+=$amount;
            $lines[]=[
                'sku'=>(string)($item['sku']??''),
                'title'=>(string)$item['title'],
                'quantity'=>$quantity,
                'unit_cents'=>$unit,
                'line_cents'=>$amount,
            ];
        }
        $itemsTotal=(int)($order['items_total_cents']??-1);
        $discount=(int)($order['discount_cents']??-1);
        $delivery=(int)($order['delivery_fee_cents']??-1);
        $total=(int)($order['total_cents']??-1);
        if($sum!==$itemsTotal||$discount<0||$discount>$itemsTotal
          ||$delivery<0||$total<0||$total!==$itemsTotal-$discount+$delivery)
            throw new \DomainException('COM_MINIMARKET_DOCUMENT_INVALID');
        return [
            'order_id'=>$id,
            'order_created_at'=>(string)($order['created_at']??''),
            'buyer_name'=>(string)($order['customer_name']??''),
            'buyer_email'=>(string)($order['email']??''),
            'buyer_phone'=>(string)($order['phone']??''),
            'delivery_method'=>(string)($order['delivery_method']??''),
            'delivery_address'=>(string)($order['delivery_address']??''),
            'currency'=>$currency,
            'lines'=>$lines,
            'items_total_cents'=>$itemsTotal,
            'discount_cents'=>$discount,
            'delivery_fee_cents'=>$delivery,
            'total_cents'=>$total,
        ];
    }

    /** Reject a tampered stored document rather than printing altered values. */
    public static function decode(string $json,string $sha256):array
    {
        if(!hash_equals(hash('sha256',$json),$sha256))
            throw new \RuntimeException('Document snapshot integrity check failed');
        $snapshot=json_decode($json,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($snapshot)||!isset($snapshot['lines'],$snapshot['total_cents']))
            throw new \RuntimeException('Invalid document snapshot');
        return $snapshot;
    }

    /** Idempotent, atomic manual issue; the order remains financially unchanged. */
    public static function create(DatabaseInterface $db,int $orderId,int $actor):int
    {
        if($orderId<1||$actor<1)
            throw new \DomainException('COM_MINIMARKET_DOCUMENT_INVALID');
        $orders=$db->quoteName('#__minimarket_orders');
        $docs=$db->quoteName('#__minimarket_order_documents');
        $db->transactionStart();
        try{
            $db->setQuery('SELECT * FROM '.$orders.' WHERE id='.$orderId.' FOR UPDATE');
            $order=$db->loadAssoc();
            if(!$order)throw new \DomainException('COM_MINIMARKET_DOCUMENT_INVALID');
            $db->setQuery('SELECT id FROM '.$docs
                .' WHERE order_id='.$orderId." AND document_type='invoice' FOR UPDATE");
            $existing=(int)$db->loadResult();
            if($existing>0){
                $db->transactionCommit();
                return $existing;
            }
            if($order['deleted_at']!==null||$order['status']==='cancelled')
                throw new \DomainException('COM_MINIMARKET_DOCUMENT_INVALID');
            $db->setQuery('SELECT sku,title,unit_cents,quantity,line_cents FROM '
                .$db->quoteName('#__minimarket_order_items')
                .' WHERE order_id='.$orderId.' ORDER BY id ASC');
            $items=(array)$db->loadAssocList();
            $snapshot=self::snapshot($order,$items);
            $json=json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $sha=hash('sha256',$json);
            $number='MM-INV-'.$orderId;
            $db->setQuery('INSERT INTO '.$docs
                .' (order_id,document_type,document_number,snapshot_json,snapshot_sha256,total_cents,currency,created_by,created_at) VALUES ('
                .$orderId.",'invoice',".$db->quote($number).','.$db->quote($json)
                .','.$db->quote($sha).','.(int)$snapshot['total_cents'].','
                .$db->quote($snapshot['currency']).','.$actor.',UTC_TIMESTAMP())');
            $db->execute();
            $docId=(int)$db->insertid();
            if($docId<1)throw new \RuntimeException('Document insert failed');
            $db->transactionCommit();
            return $docId;
        }catch(\Throwable $e){
            $db->transactionRollback();
            throw $e;
        }
    }
}
