<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Resolve published SEO landing rules without loading product data again.
 * Category scope is strict. Legacy pages (conditions_json='') keep their
 * existing URLs but never hijack category filter combinations.
 */
final class SeoLandingResolver
{
    public static function candidates(DatabaseInterface $db,int $categoryId,int $exceptId=0):array
    {
        if($categoryId<1)return [];
        $q=$db->getQuery(true)->select(['id','conditions_json'])
            ->from($db->quoteName('#__minimarket_seo_filters'))
            ->where('category_id='.(int)$categoryId)
            ->where('state=1')
            ->order('id ASC');
        if($exceptId>0)$q->where('id<>'.(int)$exceptId);
        $db->setQuery($q,0,5000);
        $out=[];
        foreach((array)$db->loadAssocList() as $row){
            $raw=(string)($row['conditions_json']??'');
            if($raw===''||$raw==='{}')continue; // legacy page
            $parsed=json_decode($raw,true,8);
            if(!is_array($parsed))continue;
            try{$rules=SeoLandingRules::normalize($parsed);}
            catch(\DomainException){continue;}
            $out[]=['id'=>(int)$row['id'],'rules'=>$rules];
        }
        return $out;
    }

    public static function resolve(DatabaseInterface $db,int $categoryId,array $selected):?int
    {
        $winner=SeoLandingRules::pick(self::candidates($db,$categoryId),$selected);
        return $winner===null?null:(int)$winner['id'];
    }

    /**
     * Publication rejects equal-strength overlapping rules, including an
     * exact+any cross-over. Higher specificity is always deterministic.
     */
    public static function assertUnambiguous(
        DatabaseInterface $db,int $categoryId,array $rules,int $exceptId=0
    ):void {
        if(!$rules)return;
        $rank=SeoLandingRules::specificity($rules);
        foreach(self::candidates($db,$categoryId,$exceptId) as $row){
            $other=$row['rules'];
            if(SeoLandingRules::specificity($other)===$rank
                &&SeoLandingRules::overlap($rules,$other))
                throw new \DomainException('COM_MINIMARKET_SEOFILTER_CONFLICT');
        }
    }
}
