<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/**
 * One best applicable automatic offer, never stacked. All money is integer
 * cents and percent basis points. Rules are re-read inside the order transaction.
 */
final class AutoDiscount
{
    public static function quote(DatabaseInterface $db,array $lines,int $subtotal):?array
    {
        if($subtotal<=0||!$lines)return null;
        $now=gmdate('Y-m-d H:i:s');
        $q=$db->getQuery(true)->select('*')
            ->from($db->quoteName('#__minimarket_auto_discounts'))
            ->where('state=1')
            ->where('(starts_at IS NULL OR starts_at<='.$db->quote($now).')')
            ->where('(ends_at IS NULL OR ends_at>='.$db->quote($now).')')
            ->order('id ASC');
        $db->setQuery($q,0,501);
        $rules=(array)$db->loadAssocList();
        if(count($rules)>500)throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_CONFIG_INVALID');
        if(!$rules)return null;

        $ids=array_values(array_unique(array_filter(array_map(
            static fn($line)=>(int)($line['product_id']??0),$lines),
            static fn($id)=>$id>0)));
        if(!$ids)return null;
        $q=$db->getQuery(true)->select(['id','category_id','brand_id'])
            ->from($db->quoteName('#__minimarket_products'))
            ->where('id IN ('.implode(',',$ids).')');
        $db->setQuery($q);
        $products=[];
        foreach((array)$db->loadAssocList() as $row)$products[(int)$row['id']]=$row;

        $best=null;
        foreach($rules as $rule){
            $scope=(string)$rule['applies_to'];
            if(!in_array($scope,['all','product','category','brand'],true))
                throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_CONFIG_INVALID');
            $targets=json_decode((string)($rule['target_ids_json']??'[]'),true);
            if(!is_array($targets)||count($targets)>100)
                throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_CONFIG_INVALID');
            $targets=array_values(array_unique(array_filter(array_map('intval',$targets),
                static fn($id)=>$id>0)));
            if($scope!=='all'&&!$targets)continue;
            $eligible=0;
            foreach($lines as $line){
                $pid=(int)($line['product_id']??0);
                $amount=(int)($line['line_cents']??-1);
                if($amount<0||$amount>$subtotal)
                    throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_CONFIG_INVALID');
                $product=$products[$pid]??null;
                if(($scope==='all')
                    ||($scope==='product'&&in_array($pid,$targets,true))
                    ||($scope==='category'&&$product!==null&&in_array((int)$product['category_id'],$targets,true))
                    ||($scope==='brand'&&$product!==null&&in_array((int)$product['brand_id'],$targets,true)))
                    $eligible+=$amount;
            }
            if($eligible<=0||$eligible>$subtotal)continue;
            $kind=(string)$rule['discount_type'];
            if($kind==='percent'){
                $basis=(int)$rule['basis_points'];
                if($basis<1||$basis>10000)
                    throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_CONFIG_INVALID');
                $amount=intdiv($eligible*$basis,10000);
            }elseif($kind==='fixed'){
                $fixed=(int)$rule['amount_cents'];
                if($fixed<1||$fixed>999999999999)
                    throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_CONFIG_INVALID');
                $amount=min($eligible,$fixed);
            }else throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_CONFIG_INVALID');
            if($amount<=0)continue;
            if($best===null||$amount>$best['discount_cents'])
                $best=['id'=>(int)$rule['id'],'title'=>(string)$rule['title'],
                    'discount_cents'=>$amount];
        }
        return $best;
    }

    /** Strict policy input; a draft may be saved but it never affects orders. */
    public static function normalize(array $raw):array
    {
        $title=trim(strip_tags((string)($raw['title']??'')));
        $type=(string)($raw['discount_type']??'percent');
        $scope=(string)($raw['applies_to']??'all');
        $state=(int)($raw['state']??0);
        if($title===''||mb_strlen($title)>160||preg_match('/[\x00-\x1f<>]/u',$title)
            ||!in_array($type,['fixed','percent'],true)
            ||!in_array($scope,['all','product','category','brand'],true)
            ||!in_array($state,[0,1],true))
            throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
        $money=static function(string $value):int{
            if(!preg_match('/^(0|[1-9][0-9]{0,10})(?:\.([0-9]{1,2}))?$/D',$value,$m))
                throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
            return (int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
        };
        $amount=$type==='fixed'?$money(trim((string)($raw['amount']??''))):0;
        $basis=0;
        if($type==='percent'){
            $pct=trim((string)($raw['percent']??''));
            if(!preg_match('/^(?:[1-9][0-9]?|100|0)(?:\.([0-9]{1,2}))?$/D',$pct))
                throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
            $parts=explode('.',$pct,2);
            $basis=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
        }
        if(($type==='fixed'&&$amount<1)||($type==='percent'&&($basis<1||$basis>10000)))
            throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
        $selected=trim((string)($raw['target_ids']??''));
        if($scope==='all')$ids=[];
        else{
            if($selected===''||!preg_match('/^[1-9][0-9]*(?:\s*,\s*[1-9][0-9]*){0,99}$/D',$selected))
                throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
            $ids=array_map('intval',preg_split('/\s*,\s*/',$selected));
            if(count($ids)!==count(array_unique($ids))||in_array(0,$ids,true))
                throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
        }
        $dates=[];
        foreach(['starts_at','ends_at'] as $key){
            $value=trim((string)($raw[$key]??''));
            if($value===''){$dates[$key]=null;continue;}
            $dt=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new \DateTimeZone('UTC'));
            if(!$dt||$dt->format('Y-m-d\TH:i')!==$value)
                throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
            $dates[$key]=$dt->format('Y-m-d H:i:s');
        }
        if($dates['starts_at']!==null&&$dates['ends_at']!==null
            &&$dates['starts_at']>=$dates['ends_at'])
            throw new \DomainException('COM_MINIMARKET_AUTO_DISCOUNT_INVALID');
        return ['title'=>$title,'state'=>$state,'discount_type'=>$type,
            'amount_cents'=>$amount,'basis_points'=>$basis,'applies_to'=>$scope,
            'target_ids_json'=>json_encode($ids,JSON_THROW_ON_ERROR),
            'starts_at'=>$dates['starts_at'],'ends_at'=>$dates['ends_at']];
    }
}
