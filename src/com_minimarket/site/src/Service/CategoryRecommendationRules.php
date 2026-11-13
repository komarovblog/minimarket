<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/**
 * Resolve one category rule independently for similar / bought-together.
 * Inherited settings stay out of child records; a new root uses own-category.
 * CSV is a bounded, validated storage representation, never executable SQL.
 */
final class CategoryRecommendationRules
{
    public const SCOPES=['inherit','own','selected'];

    public static function ids(string $csv):array
    {
        if($csv==='')return [];
        if(strlen($csv)>2000||!preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*){0,99}$/D',$csv))
            return [];
        $ids=array_map('intval',explode(',',$csv));
        return count($ids)===count(array_unique($ids))?$ids:[];
    }

    public static function fromIds(array $ids):string
    {
        if(count($ids)>100)throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
        $out=[];
        foreach($ids as $raw){
            if(!is_scalar($raw)||!preg_match('/^[1-9][0-9]{0,9}$/D',(string)$raw))
                throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
            $id=(int)$raw;
            if($id<1||isset($out[$id]))
                throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
            $out[$id]=$id;
        }
        $csv=implode(',',$out);
        if(strlen($csv)>2000)throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
        return $csv;
    }

    public static function defaults():array
    {
        return ['similar_scope'=>'inherit','similar_categories'=>[],
            'similar_attributes'=>[],'together_scope'=>'inherit',
            'together_categories'=>[]];
    }

    public static function raw(DatabaseInterface $db,int $categoryId):array
    {
        $default=self::defaults();
        if($categoryId<1)return $default;
        $db->setQuery('SELECT similar_scope,similar_categories,similar_attributes,'
            .'together_scope,together_categories FROM '
            .$db->quoteName('#__minimarket_category_recommendations')
            .' WHERE category_id='.$categoryId);
        $row=$db->loadAssoc();
        if(!$row)return $default;
        foreach(['similar','together'] as $kind){
            $scope=(string)($row[$kind.'_scope']??'inherit');
            $default[$kind.'_scope']=in_array($scope,self::SCOPES,true)?$scope:'inherit';
            $default[$kind.'_categories']=self::ids((string)($row[$kind.'_categories']??''));
        }
        $default['similar_attributes']=self::ids((string)($row['similar_attributes']??''));
        return $default;
    }

    /** One ancestor chain; skip broken/cyclic parent links safely. */
    public static function effective(DatabaseInterface $db,int $categoryId,string $kind):array
    {
        if($categoryId<1||!in_array($kind,['similar','together'],true))
            return ['scope'=>'own','categories'=>$categoryId>0?[$categoryId]:[],'attributes'=>[]];
        $seen=[];$cursor=$categoryId;
        for($steps=0;$cursor>0&&$steps<40;$steps++){
            if(isset($seen[$cursor]))break;
            $seen[$cursor]=true;
            $rule=self::raw($db,$cursor);
            $scope=$rule[$kind.'_scope'];
            if($scope!=='inherit'){
                return ['scope'=>$scope,'categories'=>$scope==='own'?[$categoryId]:$rule[$kind.'_categories'],
                    'attributes'=>$kind==='similar'?$rule['similar_attributes']:[],'source_category'=>$cursor];
            }
            $db->setQuery('SELECT parent_id FROM '.$db->quoteName('#__minimarket_categories')
                .' WHERE id='.$cursor);
            $parent=$db->loadResult();
            if($parent===null)break;
            $cursor=(int)$parent;
        }
        return ['scope'=>'own','categories'=>[$categoryId],'attributes'=>[],
            'source_category'=>0];
    }

    public static function normalize(array $data):array
    {
        $out=self::defaults();
        foreach(['similar','together'] as $kind){
            $scope=(string)($data[$kind.'_scope']??'inherit');
            if(!in_array($scope,self::SCOPES,true))
                throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
            $values=$data[$kind.'_categories']??[];
            if(!is_array($values))
                throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
            $csv=self::fromIds($values);
            if($scope==='selected'&&$csv==='')
                throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
            $out[$kind.'_scope']=$scope;
            $out[$kind.'_categories']=$scope==='selected'?self::ids($csv):[];
        }
        $attrs=$data['similar_attributes']??[];
        if(!is_array($attrs))throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
        $out['similar_attributes']=$out['similar_scope']!=='inherit'
            ?self::ids(self::fromIds($attrs)):[];
        return $out;
    }

    /** Called inside CategoryModel's existing transaction, AFTER category rules. */
    public static function save(DatabaseInterface $db,int $categoryId,array $data):void
    {
        if($categoryId<1)throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
        $r=self::normalize($data);
        $all=array_unique(array_merge($r['similar_categories'],$r['together_categories']));
        if($all){
            $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_categories')
                .' WHERE id IN ('.implode(',',$all).')');
            $existing=array_map('intval',(array)$db->loadColumn());
            if(count($existing)!==count($all))
                throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
        }
        if($r['similar_attributes']){
            $hierarchy=\MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes::loadHierarchy($db);
            $allowed=\MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes::resolve(
                $categoryId,$hierarchy['parents'],$hierarchy['rules']);
            foreach($r['similar_attributes'] as $attr)
                if(!isset($allowed[$attr]))
                    throw new \DomainException('COM_MINIMARKET_RECOMMEND_RULE_INVALID');
        }
        $table=$db->quoteName('#__minimarket_category_recommendations');
        if($r['similar_scope']==='inherit'&&$r['together_scope']==='inherit'){
            $db->setQuery('DELETE FROM '.$table.' WHERE category_id='.$categoryId);
        }else{
            $db->setQuery('INSERT INTO '.$table
                .' (category_id,similar_scope,similar_categories,similar_attributes,'
                .'together_scope,together_categories,updated_at) VALUES('
                .$categoryId.','.$db->quote($r['similar_scope']).','
                .$db->quote(implode(',',$r['similar_categories'])).','
                .$db->quote(implode(',',$r['similar_attributes'])).','
                .$db->quote($r['together_scope']).','
                .$db->quote(implode(',',$r['together_categories']))
                .',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE '
                .'similar_scope=VALUES(similar_scope),similar_categories=VALUES(similar_categories),'
                .'similar_attributes=VALUES(similar_attributes),together_scope=VALUES(together_scope),'
                .'together_categories=VALUES(together_categories),updated_at=VALUES(updated_at)');
        }
        $db->execute();
    }
}
