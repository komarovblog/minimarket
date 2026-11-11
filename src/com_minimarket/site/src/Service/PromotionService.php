<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Promotion is always priced against fresh server-side cart snapshots.
 * Lock promo row while checking usage and inserting order/redemption.
 */
final class PromotionService
{
    public static function customerKey(int $userId,array $contact): string
    {
        if($userId>0)return hash('sha256','account:'.$userId);
        $phone=preg_replace('/\D/','',(string)($contact['phone']??''));
        $mail=mb_strtolower(trim((string)($contact['email']??'')));
        if(strlen($phone)>=7)return hash('sha256','phone:'.$phone);
        if($mail!=='')return hash('sha256','email:'.$mail);
        throw new \DomainException('COM_MINIMARKET_PROMO_CUSTOMER_INVALID');
    }

    /**
     * @return array{id:int,code:string,discount_cents:int,customer_key:string}|null
     */
    public static function quote(
        DatabaseInterface $db,string $code,array $lines,int $subtotal,
        int $userId,array $contact,bool $lock=false,string $checkoutKey=''
    ): ?array {
        $code=strtoupper(trim($code));
        if($code==='')return null;
        if(!preg_match('/^[A-Z0-9-]{3,64}$/D',$code)||$subtotal<0)
            throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
        $q='SELECT * FROM '.$db->quoteName('#__minimarket_promo_codes')
            .' WHERE code='.$db->quote($code).' AND state=1'
            .($lock?' FOR UPDATE':'');
        $db->setQuery($q);
        $promo=$db->loadAssoc();
        if(!$promo)throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
        $now=gmdate('Y-m-d H:i:s');
        if(($promo['starts_at']!==null && $promo['starts_at']>$now)
            ||($promo['ends_at']!==null && $promo['ends_at']<$now))
            throw new \DomainException('COM_MINIMARKET_PROMO_EXPIRED');
        if($subtotal<(int)$promo['min_cart_cents'])
            throw new \DomainException('COM_MINIMARKET_PROMO_MINIMUM');
        if($promo['usage_limit']!==null && (int)$promo['used_count']>=(int)$promo['usage_limit'])
            throw new \DomainException('COM_MINIMARKET_PROMO_EXHAUSTED');

        // The cart may preview a code without customer details. A code with
        // a per-customer limit still requires a stable customer identity.
        // For a guest order with no such limit, the single-use checkout key
        // gives the transaction a non-personal redemption identifier.
        try {
            $identity=self::customerKey($userId,$contact);
        } catch (\DomainException $e) {
            if ($promo['per_customer_limit']!==null)throw $e;
            if (!$lock)$identity='';
            elseif(preg_match('/^[a-f0-9]{32}$/D',$checkoutKey))
                $identity=hash('sha256','checkout:'.$checkoutKey);
            else throw $e;
        }
        if($promo['per_customer_limit']!==null){
            $q=$db->getQuery(true)->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_promo_redemptions'))
                ->where('promo_code_id='.(int)$promo['id'])
                ->where('customer_key='.$db->quote($identity));
            $db->setQuery($q);
            if((int)$db->loadResult()>=(int)$promo['per_customer_limit'])
                throw new \DomainException('COM_MINIMARKET_PROMO_CUSTOMER_LIMIT');
        }

        $scope=(string)$promo['applies_to'];
        if(!in_array($scope,['all','product','category'],true))
            throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
        $targets=json_decode((string)($promo['target_ids_json']??'[]'),true);
        if(!is_array($targets))throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
        $targetIds=array_values(array_unique(array_filter(array_map('intval',$targets),static fn($n)=>$n>0)));
        $categories=[];
        if($scope==='category') {
            if(!$targetIds)throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
            $productIds=array_values(array_unique(array_filter(array_map(
                static fn($line)=>(int)$line['product_id'],$lines),static fn($id)=>$id>0)));
            if($productIds) {
                $q=$db->getQuery(true)->select(['id','category_id'])
                    ->from($db->quoteName('#__minimarket_products'))
                    ->where('id IN ('.implode(',',$productIds).')');
                $db->setQuery($q);
                foreach((array)$db->loadAssocList() as $row){
                    $categories[(int)$row['id']]=(int)$row['category_id'];
                }
            }
        }
        $eligible=0;
        foreach($lines as $line){
            $pid=(int)($line['product_id']??0);
            $amount=(int)($line['line_cents']??0);
            if($amount<0)throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
            if($scope==='all'
                ||($scope==='product'&&in_array($pid,$targetIds,true))
                ||($scope==='category'&&in_array($categories[$pid]??0,$targetIds,true))) {
                $eligible+=$amount;
            }
        }
        if($eligible===0 || $eligible>$subtotal)
            throw new \DomainException('COM_MINIMARKET_PROMO_NOT_ELIGIBLE');
        $kind=(string)$promo['discount_type'];
        if($kind==='fixed'){
            $discount=min($eligible,(int)$promo['amount_cents']);
        }elseif($kind==='percent'){
            $basis=(int)round((float)$promo['percent_value']*100);
            if($basis<1||$basis>10000)throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
            $discount=intdiv($eligible*$basis,10000);
        }else throw new \DomainException('COM_MINIMARKET_PROMO_INVALID');
        if($discount<1)throw new \DomainException('COM_MINIMARKET_PROMO_NOT_ELIGIBLE');
        return ['id'=>(int)$promo['id'],'code'=>$code,'discount_cents'=>$discount,
            'customer_key'=>$identity];
    }

    /** Call only inside the same open transaction that writes the order. */
    public static function redeem(DatabaseInterface $db,array $promo,int $orderId): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/D',(string)($promo['customer_key']??''))) {
            throw new \DomainException('COM_MINIMARKET_PROMO_CUSTOMER_INVALID');
        }
        $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_promo_redemptions'))
            ->columns($db->quoteName(['promo_code_id','order_id','customer_key','discount_cents','created_at']))
            ->values(implode(',',[(int)$promo['id'],$orderId,
                $db->quote($promo['customer_key']),(int)$promo['discount_cents'],'UTC_TIMESTAMP()']));
        $db->setQuery($q);$db->execute();
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_promo_codes'))
            ->set('used_count=used_count+1')->where('id='.(int)$promo['id']);
        $db->setQuery($q);$db->execute();
    }
}
