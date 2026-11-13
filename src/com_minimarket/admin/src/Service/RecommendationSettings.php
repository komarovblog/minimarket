<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/** One persistence contract for the standalone editor and product-card tabs. */
final class RecommendationSettings
{
    public static function normalize(
        string $similarMode,string $togetherMode,string $similarIds,string $togetherIds,int $source
    ):array {
        if($source<1||!in_array($similarMode,['inherit','auto','manual','hybrid'],true)
          ||!in_array($togetherMode,['inherit','auto','manual','hybrid'],true))
            throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
        $parse=static function(string $text)use($source):array {
            $text=trim($text);
            if($text==='')return [];
            if(strlen($text)>340||!preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*){0,29}$/D',$text))
                throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
            $ids=array_map('intval',explode(',',$text));
            if(count($ids)!==count(array_unique($ids))||in_array(0,$ids,true)
              ||in_array($source,$ids,true)||count($ids)>30)
                throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
            return $ids;
        };
        return ['modes'=>['similar'=>$similarMode,'together'=>$togetherMode],
            'similar'=>$parse($similarIds),'together'=>$parse($togetherIds)];
    }

    /** Caller owns transaction; all target IDs rechecked server-side. */
    public static function save(DatabaseInterface $db,int $source,array $data):void
    {
        if($source<1)throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
        $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_products')
            .' WHERE id='.$source.' FOR UPDATE');
        if((int)$db->loadResult()!==$source)
            throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
        $ids=array_values(array_unique(array_merge($data['similar'],$data['together'])));
        if($ids){
            $db->setQuery('SELECT COUNT(DISTINCT id) FROM '
                .$db->quoteName('#__minimarket_products')
                .' WHERE id IN ('.implode(',',$ids).')');
            if((int)$db->loadResult()!==count($ids))
                throw new \DomainException('COM_MINIMARKET_RECOMMENDATIONS_INVALID');
        }
        $db->setQuery('INSERT INTO '.$db->quoteName('#__minimarket_recommendation_settings')
            .' (product_id,similar_mode,together_mode,updated_at) VALUES ('.$source.','
            .$db->quote($data['modes']['similar']).','.$db->quote($data['modes']['together'])
            .',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE similar_mode=VALUES(similar_mode),'
            .' together_mode=VALUES(together_mode),updated_at=VALUES(updated_at)');
        $db->execute();
        $db->setQuery('DELETE FROM '.$db->quoteName('#__minimarket_recommendation_links')
            .' WHERE source_product_id='.$source);
        $db->execute();
        foreach(['similar','together'] as $kind){
            foreach($data[$kind] as $position=>$target){
                $db->setQuery('INSERT INTO '.$db->quoteName('#__minimarket_recommendation_links')
                    .' (source_product_id,target_product_id,kind,sort_order) VALUES ('.$source.','
                    .(int)$target.','.$db->quote($kind).','.(int)$position.')');
                $db->execute();
            }
        }
    }
}
