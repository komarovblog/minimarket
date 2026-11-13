<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/**
 * Independent recommendation edges, not variant groups. Modes auto/manual/hybrid.
 * No unpublished or unavailable product can be shown: CatalogRepository filters.
 */
final class ProductRecommendations
{
    public static function modes(DatabaseInterface $db,int $productId):array
    {
        if($productId<1)return ['similar'=>'auto','together'=>'auto'];
        $db->setQuery($db->getQuery(true)->select(['similar_mode','together_mode'])
            ->from($db->quoteName('#__minimarket_recommendation_settings'))
            ->where('product_id='.$productId));
        $row=$db->loadAssoc();
        $valid=static fn($value)=>in_array($value,['auto','manual','hybrid'],true)?$value:'auto';
        return ['similar'=>$valid((string)($row['similar_mode']??'auto')),
            'together'=>$valid((string)($row['together_mode']??'auto'))];
    }

    /** @return int[] */
    public static function manualIds(DatabaseInterface $db,int $productId,string $kind):array
    {
        if($productId<1||!in_array($kind,['similar','together'],true))return [];
        $q=$db->getQuery(true)->select('target_product_id')
            ->from($db->quoteName('#__minimarket_recommendation_links'))
            ->where('source_product_id='.$productId)
            ->where('kind='.$db->quote($kind))
            ->order('sort_order ASC,id ASC');
        $db->setQuery($q,0,50);
        return array_values(array_filter(array_map('intval',(array)$db->loadColumn()),
            static fn($id)=>$id>0&&$id!==$productId));
    }

    public static function similar(
        CatalogRepository $catalog,int $productId,int $categoryId,int $limit=4,
        ?DatabaseInterface $db=null
    ):array {
        if($productId<1||$categoryId<1)return [];
        $mode=$db?self::modes($db,$productId)['similar']:'auto';
        $ids=$mode==='auto'?[]:self::manualIds($db,$productId,'similar');
        $manual=$catalog->getVisibleRecommendationsByIds($ids,$limit);
        if($mode==='manual'||count($manual)>=min(12,max(1,$limit)))return $manual;
        $auto=$catalog->getProductsForCategory($categoryId,true,true,false,false);
        $result=$manual;$seen=[$productId=>true];
        foreach($manual as $product)$seen[(int)$product->id]=true;
        foreach($auto as $product){
            $id=(int)$product->id;
            if($id<1||isset($seen[$id]))continue;
            $seen[$id]=true;$result[]=$product;
            if(count($result)>=max(1,min(12,$limit)))break;
        }
        return $result;
    }

    public static function boughtTogether(
        DatabaseInterface $db,CatalogRepository $catalog,int $productId,int $limit=4
    ):array {
        if($productId<1)return [];
        $mode=self::modes($db,$productId)['together'];
        $manual=$mode==='auto'?[]:$catalog->getVisibleRecommendationsByIds(
            self::manualIds($db,$productId,'together'),$limit);
        if($mode==='manual'||count($manual)>=min(12,max(1,$limit)))return $manual;
        // Only paid and fulfilled orders; returned paired item is not evidence
        // for a successful cross-sale. No identifying customer data is read.
        $q=$db->getQuery(true)->select('b.product_id')
            ->from($db->quoteName('#__minimarket_order_items','a'))
            ->join('INNER',$db->quoteName('#__minimarket_order_items','b')
                .' ON b.order_id=a.order_id AND b.product_id<>a.product_id')
            ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=a.order_id')
            ->where('a.product_id='.$productId)
            ->where("o.payment_status='paid'")
            ->where("o.fulfillment_status='delivered'")
            ->where('o.deleted_at IS NULL')
            ->where('NOT EXISTS (SELECT 1 FROM '.$db->quoteName('#__minimarket_returns','r')
                .' INNER JOIN '.$db->quoteName('#__minimarket_return_items','ri')
                .' ON ri.return_id=r.id WHERE r.order_id=o.id AND ri.order_item_id=b.id'
                ." AND r.status IN ('received','closed'))")
            ->group('b.product_id')
            ->order('COUNT(DISTINCT o.id) DESC,b.product_id DESC');
        $db->setQuery($q,0,40);
        $ids=array_values(array_filter(array_map('intval',(array)$db->loadColumn()),
            static fn($id)=>$id>0&&$id!==$productId));
        $manualIds=array_map(static fn($item)=>(int)$item->id,$manual);
        return $catalog->getVisibleRecommendationsByIds(
            array_values(array_unique(array_merge($manualIds,$ids))),$limit);
    }
}
