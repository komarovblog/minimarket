<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Stock analytics by real inventory key, never by a bundle/variant display SKU.
 * Available stock is already net of holds. ABC is gross direct-line revenue,
 * not profit, and excludes bundle revenue (no reliable historic allocation).
 */
final class StockReport
{
    public static function load(DatabaseInterface $db,int $days=30):array
    {
        if(!in_array($days,[30,90],true))throw new \InvalidArgumentException('Invalid stock analysis period');
        $q=$db->getQuery(true)->select([
            'p.id','p.title','p.sku','p.stock','p.state','p.option_stock_mode'
        ])->from($db->quoteName('#__minimarket_products','p'))
          ->where("p.product_type='simple'")->where("p.delivery_type='physical'")
          ->order('p.title ASC,p.id ASC');
        $db->setQuery($q);
        $products=(array)$db->loadAssocList();
        $q=$db->getQuery(true)->select(['id','product_id','sku','stock','state'])
            ->from($db->quoteName('#__minimarket_product_variants'))->order('product_id ASC,id ASC');
        $db->setQuery($q);
        $variants=[];
        foreach((array)$db->loadAssocList() as $v)$variants[(int)$v['product_id']][]=$v;
        $rows=[];
        foreach($products as $p){
            $id=(int)$p['id'];
            if((int)$p['option_stock_mode']===1 && !empty($variants[$id])){
                foreach($variants[$id] as $v){
                    $key='variant:'.(int)$v['id'];
                    $rows[$key]=[
                        'key'=>$key,'product_id'=>$id,'variant_id'=>(int)$v['id'],
                        'title'=>(string)$p['title'],'sku'=>(string)$v['sku'],
                        'available'=>(int)$v['stock'],'held'=>0,'sold'=>0,'revenue_cents'=>0,
                        'published'=>(int)$p['state']===1&&(int)$v['state']===1
                    ];
                }
            }else{
                $key='product:'.$id;
                $rows[$key]=[
                    'key'=>$key,'product_id'=>$id,'variant_id'=>0,
                    'title'=>(string)$p['title'],'sku'=>(string)$p['sku'],
                    'available'=>(int)$p['stock'],'held'=>0,'sold'=>0,'revenue_cents'=>0,
                    'published'=>(int)$p['state']===1
                ];
            }
        }
        $db->setQuery('SELECT source_kind,source_id,SUM(quantity) AS units FROM '
            .$db->quoteName('#__minimarket_stock_allocations')
            ." WHERE status='held' GROUP BY source_kind,source_id");
        foreach((array)$db->loadAssocList() as $a){
            $key=(string)$a['source_kind'].':'.(int)$a['source_id'];
            if(isset($rows[$key]))$rows[$key]['held']=(int)$a['units'];
        }
        $where="a.status='fulfilled' AND o.payment_status='paid'"
            ." AND o.fulfillment_status='delivered' AND o.status<>'cancelled'"
            .' AND o.deleted_at IS NULL AND COALESCE(o.fulfilled_at,o.created_at)>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$days.' DAY)';
        $db->setQuery('SELECT a.source_kind,a.source_id,SUM(a.quantity) AS units'
            .' FROM '.$db->quoteName('#__minimarket_stock_allocations','a')
            .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o').' ON o.id=a.order_id'
            .' WHERE '.$where.' GROUP BY a.source_kind,a.source_id');
        foreach((array)$db->loadAssocList() as $a){
            $key=(string)$a['source_kind'].':'.(int)$a['source_id'];
            if(isset($rows[$key]))$rows[$key]['sold']=(int)$a['units'];
        }
        // XYZ: three consecutive non-overlapping 30-day windows, always 90 days.
        // Counts come from the same real fulfilled allocations as the sales metric;
        // no fabricated movement ledger, no prices or financial assumptions.
        $time='COALESCE(o.fulfilled_at,o.created_at)';
        $parts=[];
        foreach([[30,0],[60,30],[90,60]] as $idx=>[$older,$newer]){
            $after='DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$older.' DAY)';
            $before=$newer===0?'UTC_TIMESTAMP()'
                :'DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$newer.' DAY)';
            $parts[]='SUM(CASE WHEN '.$time.' > '.$after.' AND '.$time
                .' <= '.$before.' THEN a.quantity ELSE 0 END) AS b'.$idx;
        }
        $db->setQuery('SELECT a.source_kind,a.source_id,'.implode(',',$parts)
            .' FROM '.$db->quoteName('#__minimarket_stock_allocations','a')
            .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o').' ON o.id=a.order_id'
            ." WHERE a.status='fulfilled' AND o.payment_status='paid'"
            ." AND o.fulfillment_status='delivered' AND o.status<>'cancelled'"
            .' AND o.deleted_at IS NULL AND '.$time
            .' > DATE_SUB(UTC_TIMESTAMP(),INTERVAL 90 DAY)'
            .' AND '.$time.' <= UTC_TIMESTAMP()'
            .' GROUP BY a.source_kind,a.source_id');
        foreach((array)$db->loadAssocList() as $bucket){
            $key=(string)$bucket['source_kind'].':'.(int)$bucket['source_id'];
            if(isset($rows[$key]))$rows[$key]['xyz_buckets']=[
                (int)$bucket['b0'],(int)$bucket['b1'],(int)$bucket['b2']
            ];
        }
        // Only directly billed simple products can be assigned reliable revenue.
        // Bundle components have consumption, but no per-component sale price.
        $revenueKey="CASE WHEN oi.variant_id>0 AND p.option_stock_mode=1 THEN 'variant' ELSE 'product' END";
        $revenueId="CASE WHEN oi.variant_id>0 AND p.option_stock_mode=1 THEN oi.variant_id ELSE oi.product_id END";
        $db->setQuery('SELECT '.$revenueKey.' AS source_kind,'.$revenueId.' AS source_id,'
            .' SUM(oi.line_cents) AS revenue_cents'
            .' FROM '.$db->quoteName('#__minimarket_order_items','oi')
            .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o').' ON o.id=oi.order_id'
            .' INNER JOIN '.$db->quoteName('#__minimarket_products','p').' ON p.id=oi.product_id'
            ." WHERE oi.delivery_type='physical' AND p.product_type='simple'"
            ." AND o.payment_status='paid' AND o.fulfillment_status='delivered'"
            ." AND o.status<>'cancelled' AND o.deleted_at IS NULL"
            .' AND COALESCE(o.fulfilled_at,o.created_at)>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$days.' DAY)'
            .' GROUP BY source_kind,source_id');
        foreach((array)$db->loadAssocList() as $a){
            $key=(string)$a['source_kind'].':'.(int)$a['source_id'];
            if(isset($rows[$key]))$rows[$key]['revenue_cents']=(int)$a['revenue_cents'];
        }
        return self::analyze(array_values($rows),$days);
    }

    /**
     * Variability of quantity demanded across 3 x 30-day windows (90 days).
     * X <=10% CV, Y <=25%, Z >25%. No demand stays unclassified.
     * Not a forecast; excludes bundles and stock adjustment proxies.
     */
    public static function xyz(array $buckets):string
    {
        if(count($buckets)!==3)return '';
        foreach($buckets as $n)if(!is_int($n)||$n<0)return '';
        $sum=array_sum($buckets);
        if($sum===0)return '';
        $mean=$sum/3;
        $variance=0.0;
        foreach($buckets as $n)$variance+=($n-$mean)**2;
        $cv=sqrt($variance/3)/$mean;
        return $cv<=0.10+1e-12?'X':($cv<=0.25+1e-12?'Y':'Z');
    }

    /** Pure function for deterministic ABC and days-of-cover tests. */
    public static function analyze(array $rows,int $days):array
    {
        if($days<=0)throw new \InvalidArgumentException('Invalid days');
        $revenue=0;
        foreach($rows as &$row){
            $row['available']=max(0,(int)$row['available']);
            $row['held']=max(0,(int)$row['held']);
            $row['sold']=max(0,(int)$row['sold']);
            $row['revenue_cents']=max(0,(int)$row['revenue_cents']);
            $row['total']=$row['available']+$row['held'];
            $row['cover_days']=$row['sold']>0
                ?round($row['available']*$days/$row['sold'],1):null;
            $row['abc']='';
            $row['xyz']=self::xyz($row['xyz_buckets']??[0,0,0]);
            $revenue+=$row['revenue_cents'];
        }
        unset($row);
        $rank=array_keys($rows);
        usort($rank,static fn($a,$b)=>($rows[$b]['revenue_cents']<=>$rows[$a]['revenue_cents'])
            ?:strcmp((string)$rows[$a]['key'],(string)$rows[$b]['key']));
        $running=0;
        foreach($rank as $index){
            $value=$rows[$index]['revenue_cents'];
            if($value<=0||$revenue<=0)continue;
            $before=$running/$revenue;
            $rows[$index]['abc']=$before<.80?'A':($before<.95?'B':'C');
            $running+=$value;
        }
        return $rows;
    }
}
