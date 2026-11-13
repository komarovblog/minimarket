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
        if($productId<1)return ['similar'=>'inherit','together'=>'inherit'];
        $db->setQuery($db->getQuery(true)->select(['similar_mode','together_mode'])
            ->from($db->quoteName('#__minimarket_recommendation_settings'))
            ->where('product_id='.$productId));
        $row=$db->loadAssoc();
        $valid=static fn($value)=>in_array($value,['inherit','auto','manual','hybrid'],true)?$value:'inherit';
        return ['similar'=>$valid((string)($row['similar_mode']??'inherit')),
            'together'=>$valid((string)($row['together_mode']??'inherit'))];
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

    /** Weight genuine selected attribute matches; use no personal/customer data. */
    private static function attributeScores(
        DatabaseInterface $db,int $productId,array $candidateIds,array $attributeIds
    ):array {
        $candidateIds=array_values(array_unique(array_filter(array_map('intval',$candidateIds),
            static fn($id)=>$id>0&&$id!==$productId)));
        $attributeIds=array_values(array_unique(array_filter(array_map('intval',$attributeIds),
            static fn($id)=>$id>0)));
        if(!$candidateIds||!$attributeIds)return [];
        $list=implode(',',$candidateIds);
        $attrs=implode(',',$attributeIds);
        $scores=[];
        // A saved option value must be identical. Text/numeric/boolean scalars
        // match by both type and value; absent fields do not count as matches.
        $tables=[
            ['product_attribute_options',
             'p.value_id=s.value_id'],
            ['product_attribute_scalars',
             '((p.value_text IS NOT NULL AND s.value_text IS NOT NULL AND p.value_text=s.value_text)'
             .' OR (p.value_number IS NOT NULL AND s.value_number IS NOT NULL AND p.value_number=s.value_number)'
             .' OR (p.value_boolean IS NOT NULL AND s.value_boolean IS NOT NULL AND p.value_boolean=s.value_boolean))'],
            ['product_attribute_free_values',
             'p.value_text=s.value_text']
        ];
        foreach($tables as [$table,$comparison]){
            $name=$db->quoteName('#__minimarket_'.$table);
            $db->setQuery('SELECT p.product_id,COUNT(*) AS hits FROM '.$name.' AS p'
                .' INNER JOIN '.$name.' AS s ON s.attribute_id=p.attribute_id'
                .' AND s.product_id='.$productId.' AND '.$comparison
                .' WHERE p.product_id IN ('.$list.') AND p.attribute_id IN ('.$attrs.')'
                .' GROUP BY p.product_id');
            foreach((array)$db->loadAssocList() as $row){
                $id=(int)$row['product_id'];
                $scores[$id]=($scores[$id]??0)+(int)$row['hits'];
            }
        }
        return $scores;
    }

    public static function similar(
        CatalogRepository $catalog,int $productId,int $categoryId,int $limit=4,
        ?DatabaseInterface $db=null
    ):array {
        if($productId<1||$categoryId<1)return [];
        $mode=$db?self::modes($db,$productId)['similar']:'auto';
        $ids=in_array($mode,['manual','hybrid'],true)
            ?self::manualIds($db,$productId,'similar'):[];
        $manual=$catalog->getVisibleRecommendationsByIds($ids,$limit);
        if($mode==='manual'||count($manual)>=min(12,max(1,$limit)))return $manual;
        $rule=$db?CategoryRecommendationRules::effective($db,$categoryId,'similar')
            :['categories'=>[$categoryId],'attributes'=>[]];
        $categoryIds=$rule['categories'];
        $auto=[];
        foreach($categoryIds as $sourceCategory){
            foreach($catalog->getProductsForCategory((int)$sourceCategory,true,true,false,false) as $item)
                $auto[(int)$item->id]=$item;
        }
        $auto=array_values($auto);
        if($db&&!empty($rule['attributes'])){
            $candidateIds=array_slice(array_map(static fn($p)=>(int)$p->id,$auto),0,500);
            $scores=self::attributeScores($db,$productId,$candidateIds,$rule['attributes']);
            usort($auto,static fn($a,$b)=>($scores[(int)$b->id]??0)<=>($scores[(int)$a->id]??0)
                ?:((int)$b->id<=>(int)$a->id));
        }
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
        $manual=in_array($mode,['manual','hybrid'],true)?$catalog->getVisibleRecommendationsByIds(
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
        // Scope applies to automatic co-purchase candidates, not the product's
        // explicitly selected manual overrides.
        $db->setQuery('SELECT category_id FROM '.$db->quoteName('#__minimarket_products')
            .' WHERE id='.$productId);
        $sourceCategory=(int)$db->loadResult();
        $rule=CategoryRecommendationRules::effective($db,$sourceCategory,'together');
        $allowed=array_fill_keys(array_map('intval',$rule['categories']),true);
        if($ids&&$allowed){
            $db->setQuery('SELECT id,category_id FROM '.$db->quoteName('#__minimarket_products')
                .' WHERE id IN ('.implode(',',$ids).')');
            $matched=[];
            foreach((array)$db->loadAssocList() as $entry)
                if(isset($allowed[(int)$entry['category_id']]))
                    $matched[(int)$entry['id']]=true;
            $ids=array_values(array_filter($ids,static fn($id)=>isset($matched[$id])));
        }else $ids=[];
        $manualIds=array_map(static fn($item)=>(int)$item->id,$manual);
        return $catalog->getVisibleRecommendationsByIds(
            array_values(array_unique(array_merge($manualIds,$ids))),$limit);
    }
}
