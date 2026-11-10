<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\CartState;
use MiniMarket\Component\Minimarket\Site\Service\CartStock;

/** Manual and future imported orders always use actual catalog snapshots. */
final class ManualOrderCatalog
{
    /** @return array<int,array{key:string,label:string}> */
    public static function choices(DatabaseInterface $db): array
    {
        $q=$db->getQuery(true)->select([
            'p.id AS product_id','p.title','p.sku','p.product_type',
            'v.id AS variant_id','v.sku AS variant_sku'
        ])->from($db->quoteName('#__minimarket_products','p'))
          ->join('INNER',$db->quoteName('#__minimarket_categories','c').' ON c.id=p.category_id')
          ->join('LEFT',$db->quoteName('#__minimarket_product_variants','v')
              .' ON v.product_id=p.id AND v.state=1')
          ->where('p.state=1')->where('c.state=1')
          ->where("p.delivery_type='physical'")
          ->order('p.title ASC, v.sort_order ASC, v.id ASC');
        $db->setQuery($q,0,5000);
        // BundleStock runs further database queries; consume the result first.
        $rows=(array)$db->loadAssocList();
        $options=[];
        $snapshot=BundleStock::snapshot($db);
        foreach($rows as $r) {
            $pid=(int)$r['product_id'];
            if (!empty($snapshot['hasVariants'][$pid]) && !$r['variant_id']) continue;
            $vid=(int)($r['variant_id']??0);
            if (($r['product_type']??'simple')==='bundle' && $vid!==0) continue;
            $sku=$vid>0?(string)$r['variant_sku']:(string)$r['sku'];
            $options[]=['key'=>$pid.':'.$vid,
                'label'=>(string)$r['title'].' — '.($sku?:'#'.$pid)
                    .($vid>0?' (SKU #'.$vid.')':'')];
        }
        return $options;
    }

    /** Published physical products and their valid variants, no user prices. */
    public static function quote(DatabaseInterface $db,array $raw,bool $inventoryEnabled): array
    {
        if (!$raw || count($raw)>20) {
            throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
        }
        $cart=[];
        foreach($raw as $row) {
            if (!is_array($row)) throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
            $key=(string)($row['key']??'');
            $qty=(string)($row['quantity']??'');
            if ($key==='')continue;
            if (!preg_match('/^([1-9][0-9]{0,9}):(0|[1-9][0-9]{0,9})$/D',$key,$part)
                || !preg_match('/^[1-9][0-9]{0,1}$/D',$qty)) {
                throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
            }
            $qty=(int)$qty;
            $cart[$key]=($cart[$key]??0)+$qty;
            if ($cart[$key]>CartState::MAX_QUANTITY) {
                throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
            }
        }
        if (!$cart || count($cart)>20 || array_sum($cart)>CartState::MAX_UNITS) {
            throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
        }
        $ids=[];$variantIds=[];$rows=[];
        foreach($cart as $key=>$qty) {
            [$pid,$vid]=array_map('intval',explode(':',$key,2));
            $ids[$pid]=$pid;
            if ($vid>0)$variantIds[$vid]=$vid;
            $rows[]=['product_id'=>$pid,'variant_id'=>$vid,'quantity'=>$qty];
        }
        $snap=BundleStock::snapshot($db);
        CartStock::assertAvailable($rows,$snap,$inventoryEnabled);
        $q=$db->getQuery(true)->select(['id','title','sku','price','product_type','delivery_type'])
            ->from($db->quoteName('#__minimarket_products'))
            ->where('id IN ('.implode(',',$ids).')');
        $db->setQuery($q);
        $products=[];
        foreach((array)$db->loadAssocList() as $product)$products[(int)$product['id']]=$product;
        $variants=[];
        if ($variantIds) {
            $q=$db->getQuery(true)->select(['id','product_id','sku','state'])
                ->from($db->quoteName('#__minimarket_product_variants'))
                ->where('id IN ('.implode(',',$variantIds).')');
            $db->setQuery($q);
            foreach((array)$db->loadAssocList() as $v)$variants[(int)$v['id']]=$v;
        }
        $bundlePrices=BundlePrice::snapshot($db);
        $lines=[];$total=0;
        foreach($cart as $key=>$qty) {
            [$pid,$vid]=array_map('intval',explode(':',$key,2));
            $p=$products[$pid]??null;
            if (!$p || ($p['delivery_type']??'physical')!=='physical') {
                throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
            }
            if ($vid>0) {
                $variant=$variants[$vid]??null;
                if (!$variant || (int)$variant['product_id']!==$pid || (int)$variant['state']!==1) {
                    throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
                }
                $sku=(string)$variant['sku'];
            } else $sku=(string)$p['sku'];
            $price=(string)$p['price'];
            if (($p['product_type']??'simple')==='bundle') {
                if ($vid!==0)throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
                $calculated=BundlePrice::calculate($pid,$bundlePrices);
                if (!$calculated['valid'])throw new \DomainException('COM_MINIMARKET_MANUAL_ORDER_ITEMS_INVALID');
                $price=$calculated['price'];
            }
            $cents=CartState::cents($price);
            $total+=$cents*$qty;
            $lines[]=['product_id'=>$pid,'variant_id'=>$vid,'title'=>(string)$p['title'],
                'sku'=>$sku,'type'=>(string)$p['product_type'],'valid'=>true,
                'quantity'=>$qty,'unit_cents'=>$cents,'line_cents'=>$cents*$qty];
        }
        return ['lines'=>$lines,'total_cents'=>$total,'units'=>array_sum($cart),
            'available'=>true,'error'=>''];
    }
}
