<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

/**
 * An SEO landing has one category and an EXACT predicate per facet.
 * unused: facet must be absent; any: facet must be used with any nonempty
 * selection; exact: facet must have precisely one chosen value/range.
 *
 * Store only explicitly used rules. Every omitted facet means "unused".
 * Sorting, layout and pagination are deliberately outside the predicate.
 */
final class SeoLandingRules
{
    public static function normalize(array $input):array
    {
        if(count($input)>32)throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        $result=[];$exact=0;
        foreach($input as $id=>$rule){
            if(!is_string($id)||!preg_match('/^(?:[as][1-9][0-9]*|price|sku|stock)$/D',$id)
                ||!is_array($rule))
                throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            $mode=$rule['mode']??null;
            if(!is_string($mode)||!in_array($mode,['unused','any','exact'],true))
                throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            if($mode==='unused')continue;
            if($mode==='any'){
                if(isset($rule['value']))throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $result[$id]=['mode'=>'any'];
                continue;
            }
            ++$exact;
            $v=$rule['value']??null;
            if($id==='price'||(preg_match('/^a[1-9][0-9]*$/D',$id)
                &&is_array($v)&&!isset($v['text']))){
                if(!is_array($v)||!$v||count($v)>2)
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $range=[];
                foreach(['min','max'] as $bound){
                    if(!array_key_exists($bound,$v))continue;
                    if(!is_scalar($v[$bound])||!is_numeric((string)$v[$bound])
                        ||!is_finite((float)$v[$bound])
                        ||(float)$v[$bound]<0 ||(float)$v[$bound]>999999999)
                        throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                    $range[$bound]=(float)$v[$bound];
                }
                if(count($range)!==count($v)
                    ||(isset($range['min'],$range['max'])&&$range['min']>$range['max']))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $v=$range;
            }elseif(preg_match('/^a[1-9][0-9]*$/D',$id) &&is_array($v)){
                if(array_keys($v)!==['text']||!is_string($v['text'])
                    ||trim($v['text'])===''||mb_strlen($v['text'])>100
                    ||preg_match('/[\\x00-\\x1f<>]/',$v['text']))
                    throw new \\DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $v=['text'=>trim($v['text'])];
            }elseif($id==='stock'){
                if(!in_array($v,['b:0','b:1'],true))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            }elseif($id==='sku'||(is_array($rules[$id]['value']??null)
                &&array_key_exists('text',$rules[$id]['value']))){
                if(!is_string($v)||trim($v)===''||mb_strlen($v)>100
                    ||preg_match('/[\x00-\x1f<>]/',$v))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                $v=trim($v);
            }else{
                if(!is_string($v)||!preg_match('/^(?:v:[1-9][0-9]*|b:[01]|t:[^<>\\x00-\\x1f]{1,100}|n:-?[0-9]+(?:\\.[0-9]{1,4})?)$/D',$v))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            }
            $result[$id]=['mode'=>'exact','value'=>$v];
        }
        if($exact===0)throw new \DomainException('COM_MINIMARKET_SEOFILTER_NEEDS_EXACT');
        ksort($result,SORT_STRING);
        return $result;
    }

    /** Convert exact matches to the already supported mmf query shape. */
    public static function exactFilters(array $rules):array
    {
        $filters=[];
        foreach($rules as $key=>$condition){
            if(($condition['mode']??'')!=='exact')continue;
            $v=$condition['value'];
            if(is_array($v))$filters[$key]=$v;
            elseif($key==='sku')$filters[$key]=$v;
            else $filters[$key]=[$v];
        }
        return $filters;
    }

    /** Normalized facets from FacetedCatalog::selection are the source of truth. */
    public static function matches(array $rules,array $selected):bool
    {
        foreach($selected as $id=>$selection){
            if(!isset($rules[$id]))return false;
            if(!is_array($selection)||!self::selected($selection))return false;
        }
        foreach($rules as $id=>$rule){
            if(!is_array($rule)||!isset($selected[$id])||!self::selected($selected[$id]))
                return false;
            if(($rule['mode']??'')==='any')continue;
            if(($rule['mode']??'')!=='exact')return false;
            $value=$rule['value']??null;
            $actual=$selected[$id];
            if(is_array($value)&&array_key_exists('text',$value)){
                if(count($actual)!==1||($actual['text']??null)!==$value['text'])return false;
            }elseif(is_array($value)){
                if(count($actual)!==count($value))return false;
                foreach($value as $bound=>$amount){
                    if(!array_key_exists($bound,$actual)||(float)$actual[$bound]!== (float)$amount)
                        return false;
                }
            }elseif($id==='sku'){
                if(count($actual)!==1||($actual['text']??null)!==$value)return false;
            }else{
                if(!isset($actual['values'])||count($actual['values'])!==1
                    ||$actual['values'][0]!==$value)return false;
            }
        }
        return true;
    }

    private static function selected(array $rule):bool
    {
        return !empty($rule['values'])
            ||trim((string)($rule['text']??''))!==''
            ||isset($rule['min'])||isset($rule['max']);
    }

    /**
     * Lightweight reconstruction for query-string validation BEFORE the
     * category template renders. The full facet engine revalidates tokens.
     */
    public static function rawSelection(array $raw,array $rules=[]):array
    {
        if(count($raw)>32)return [];
        $out=[];
        foreach($raw as $id=>$value){
            if(!is_string($id)||!preg_match('/^(?:[as][1-9][0-9]*|price|stock|sku)$/D',$id))
                return [];
            if($id==='sku'){
                if(is_string($value)&&trim($value)!=='')
                    $out[$id]=['text'=>trim($value)];
            }elseif(is_array($value)
                &&(array_key_exists('min',$value)||array_key_exists('max',$value))){
                $bounds=[];
                foreach(['min','max'] as $bound){
                    if(isset($value[$bound])&&is_scalar($value[$bound])
                        &&is_numeric((string)$value[$bound]))
                        $bounds[$bound]=(float)$value[$bound];
                }
                if($bounds)$out[$id]=$bounds;
            }else{
                $values=is_array($value)?array_values($value):[$value];
                $values=array_values(array_filter($values,static fn($v)=>
                    is_string($v)&&$v!==''));
                if($values)$out[$id]=['values'=>$values];
            }
        }
        return $out;
    }

    public static function specificity(array $rules):int
    {
        return count(array_filter($rules,static fn($r)=>
            ($r['mode']??'')==='exact'));
    }

    /** Whether both rules can match one and the same visitor selection. */
    public static function overlap(array $a,array $b):bool
    {
        foreach(array_unique(array_merge(array_keys($a),array_keys($b))) as $id){
            $left=$a[$id]??null;$right=$b[$id]??null;
            if(($left===null)!==($right===null))return false;
            if($left===null)continue;
            if(($left['mode']??'')==='exact'&&($right['mode']??'')==='exact'
                &&($left['value']??null)!==($right['value']??null))return false;
        }
        return true;
    }

    /** Any-match ranks below exact; ties are rejected at publication. */
    public static function pick(array $pages,array $selection):?array
    {
        $winner=null;$best=-1;
        foreach($pages as $page){
            $rules=is_array($page['rules']??null)?$page['rules']:[];
            if(!$rules||!self::matches($rules,$selection))continue;
            $score=self::specificity($rules);
            if($score>$best){$best=$score;$winner=$page;}
        }
        return $winner;
    }
}
