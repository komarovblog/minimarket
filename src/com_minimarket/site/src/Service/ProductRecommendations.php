<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Product recommendations use only published catalog items. Co-purchases
 * aggregate completed/confirmed orders; no buyer details leave the server.
 */
final class ProductRecommendations
{
    public static function similar(
        CatalogRepository $catalog, int $productId, int $categoryId, int $limit=4
    ): array {
        if($productId<1||$categoryId<1)return [];
        $rows=$catalog->getProductsForCategory($categoryId,true,true,false,false);
        $result=[];
        foreach($rows as $row){
            if((int)$row->id===$productId)continue;
            $result[]=$row;
            if(count($result)>=max(1,min(12,$limit)))break;
        }
        return $result;
    }

    public static function boughtTogether(
        DatabaseInterface $db,CatalogRepository $catalog,int $productId,int $limit=4
    ): array {
        if($productId<1)return [];
        $q=$db->getQuery(true)->select('b.product_id')
            ->from($db->quoteName('#__minimarket_order_items','a'))
            ->join('INNER',$db->quoteName('#__minimarket_order_items','b')
                .' ON b.order_id=a.order_id AND b.product_id<>a.product_id')
            ->join('INNER',$db->quoteName('#__minimarket_orders','o')
                .' ON o.id=a.order_id')
            ->where('a.product_id='.(int)$productId)
            ->where("o.status IN ('confirmed','completed')")
            ->where('o.deleted_at IS NULL')
            ->group('b.product_id')
            ->order('COUNT(DISTINCT o.id) DESC, b.product_id DESC');
        $db->setQuery($q,0,30);
        $ids=array_map('intval',(array)$db->loadColumn());
        return $catalog->getVisibleRecommendationsByIds($ids,$limit);
    }
}
