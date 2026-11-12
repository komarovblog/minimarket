<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/** Published filter dictionaries restricted to a category and its ancestors. */
final class SeoCategoryChoices
{
    public static function groups(DatabaseInterface $db,int $categoryId):array
    {
        if($categoryId<1)return [];
        $q=$db->getQuery(true)->select(['id','parent_id','state'])
          ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q,0,10000);
        $tree=[];
        foreach((array)$db->loadAssocList() as $row)$tree[(int)$row['id']]=$row;
        $seen=[];$node=$categoryId;
        while($node>0){
            if(isset($seen[$node])||!isset($tree[$node])||(int)$tree[$node]['state']!==1)return [];
            $seen[$node]=true;
            $node=(int)$tree[$node]['parent_id'];
        }
        $attributes=CategoryAttributes::loadHierarchy($db);
        $options=CategoryOrderOptions::hierarchy($db);
        $allowAttrs=CategoryAttributes::resolveModes($categoryId,$attributes['parents'],$attributes['rules']);
        $allowOpts=CategoryOrderOptions::resolveModes($categoryId,$options['parents'],$options['rules']);
        $groups=[];
        if($allowAttrs){
            $ids=implode(',',array_map('intval',array_keys($allowAttrs)));
            $q=$db->getQuery(true)->select([
                'a.id AS property_id','a.title AS property_title',
                'a.input_type','a.filter_mode',
                'v.id AS value_id','v.title AS value_title'
            ])->from($db->quoteName('#__minimarket_attributes','a'))
              ->join('LEFT',$db->quoteName('#__minimarket_attribute_values','v')
                 .' ON v.attribute_id=a.id AND v.state=1')
              ->where('a.id IN ('.$ids.')')->where('a.state=1')
              ->where('a.is_filterable=1')->where("a.filter_mode<>'none'")
              ->order('a.title ASC,v.sort_order ASC,v.id ASC');
            $db->setQuery($q,0,5000);
            foreach((array)$db->loadAssocList() as $row){
                $key='a'.(int)$row['property_id'];
                $type=(string)($row['input_type']??'');
                $filterMode=(string)($row['filter_mode']??'');
                $kind=in_array($filterMode,['slider','range'],true)?'range'
                    :($filterMode==='text'?'text':'choices');
                if(!isset($groups[$key]))$groups[$key]=[
                    'key'=>$key,'title'=>(string)$row['property_title'],
                    'kind'=>$kind,'values'=>[]
                ];
                if($kind==='choices' && $row['value_id']!==null
                    && (int)$row['value_id']>0){
                    $groups[$key]['values'][]=[
                        'token'=>'v:'.(int)$row['value_id'],
                        'title'=>(string)$row['value_title']
                    ];
                }elseif($kind==='choices' && $type==='boolean'){
                    $groups[$key]['values']=[
                        ['token'=>'b:1','title'=>'Yes'],
                        ['token'=>'b:0','title'=>'No']
                    ];
                }
                if($kind==='choices' && !$groups[$key]['values']
                    && $type==='text')$groups[$key]['kind']='text-token';
            }
        }
        if($allowOpts){
            $ids=implode(',',array_map('intval',array_keys($allowOpts)));
            $q=$db->getQuery(true)->select([
                'o.id AS property_id','o.title AS property_title',
                'v.id AS value_id','v.title AS value_title'
            ])->from($db->quoteName('#__minimarket_order_options','o'))
              ->join('INNER',$db->quoteName('#__minimarket_order_option_values','v').' ON v.option_id=o.id')
              ->where('o.id IN ('.$ids.')')->where('o.state=1')->where('v.state=1')
              ->order('o.title ASC,v.sort_order ASC,v.id ASC');
            $db->setQuery($q,0,5000);
            foreach((array)$db->loadAssocList() as $row){
                $key='s'.(int)$row['property_id'];
                if(!isset($groups[$key]))$groups[$key]=[
                    'key'=>$key,'title'=>(string)$row['property_title'],
                    'kind'=>'choices','values'=>[]
                ];
                $groups[$key]['values'][]=[
                    'token'=>'v:'.(int)$row['value_id'],'title'=>(string)$row['value_title']
                ];
            }
        }
        return array_values($groups);
    }

    /** Server-side safeguard: reject facets and values not bound to this category. */
    public static function validate(DatabaseInterface $db,int $categoryId,string $json):void
    {
        $selected=json_decode($json,true);
        if(!is_array($selected))throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        $allowed=[];
        foreach(self::groups($db,$categoryId) as $group)
            $allowed[$group['key']]=array_fill_keys(array_column($group['values'],'token'),true);
        foreach($selected as $key=>$values){
            if(!preg_match('/^[as][1-9][0-9]*$/D',(string)$key))continue;
            if(!isset($allowed[$key])||!is_array($values)||!$values)
                throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            foreach($values as $value)
                if(!is_string($value)||!isset($allowed[$key][$value]))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        }
    }
    /** Server-authoritative validation of three-state facet rules for a category. */
    public static function validateConditions(DatabaseInterface $db,int $categoryId,array $rules):void
    {
        $allowed=[];
        foreach(self::groups($db,$categoryId) as $group)$allowed[$group['key']]=$group;
        foreach($rules as $key=>$rule){
            if(in_array($key,['price','stock','sku'],true))continue;
            if(!isset($allowed[$key]))throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            if(($rule['mode']??'')==='any')continue;
            if(($rule['mode']??'')!=='exact')
                throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            $value=$rule['value']??null;
            $kind=$allowed[$key]['kind']??'choices';
            if($kind==='range'){
                if(!is_array($value)||isset($value['text']))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            }elseif($kind==='text'){
                if(!is_array($value)||!isset($value['text']))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            }elseif($kind==='text-token'){
                if(!is_string($value)||!preg_match('/^t:[^<>\\x00-\\x1f]{1,100}$/D',$value))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            }else{
                $tokens=array_column($allowed[$key]['values'],'token');
                if(!is_string($value)||!in_array($value,$tokens,true))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            }
        }
    }

}
