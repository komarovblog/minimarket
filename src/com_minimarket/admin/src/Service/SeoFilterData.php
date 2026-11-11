<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

/**
 * A landing stores ONLY the mmf filter payload, not a raw URL or arbitrary
 * GET parameters. Actual facets are normalized by FacetedCatalog on display.
 */
final class SeoFilterData
{
    public static function normalize(array $input):array
    {
        foreach (['alias','title','heading','introtext','description',
                  'meta_description','meta_robots','filter_query',
                  'image','image_alt','og_title','og_description','og_image'] as $field) {
            if (!is_scalar($input[$field]??'')) throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        }
        $category=(int)($input['category_id']??0);
        $alias=trim((string)($input['alias']??''));
        $title=trim(strip_tags((string)($input['title']??'')));
        $heading=trim(strip_tags((string)($input['heading']??'')));
        $meta=trim(strip_tags((string)($input['meta_description']??'')));
        $robots=(string)($input['meta_robots']??'index,follow');
        if($category<1 || !preg_match('/^[a-z0-9][a-z0-9-]{0,189}$/D',$alias)
           || mb_strlen($title)<3 || mb_strlen($title)>255
           || mb_strlen($heading)<3 || mb_strlen($heading)>255
           || mb_strlen($meta)>320
           || !in_array($robots,['index,follow','noindex,follow','noindex,nofollow'],true)) {
            throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        }
        $filter=\Joomla\CMS\Filter\InputFilter::getInstance();
        $intro=trim((string)$filter->clean((string)($input['introtext']??''),'HTML'));
        $description=trim((string)$filter->clean((string)($input['description']??''),'HTML'));
        if(mb_strlen($intro)>10000 || mb_strlen($description)>20000)
            throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        $image=MediaPath::normalize((string)($input['image']??''));
        $ogImage=MediaPath::normalize((string)($input['og_image']??''));
        $imageAlt=trim(strip_tags((string)($input['image_alt']??'')));
        $ogTitle=trim(strip_tags((string)($input['og_title']??'')));
        $ogDescription=trim(strip_tags((string)($input['og_description']??'')));
        if($image===null||$ogImage===null||mb_strlen($imageAlt)>255
            ||mb_strlen($ogTitle)>255||mb_strlen($ogDescription)>320)
            throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        $query=trim((string)($input['filter_query']??''));
        if(strlen($query)>4000)throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        if(str_contains($query,'?'))$query=explode('?',$query,2)[1];
        parse_str($query,$parsed);
        if(!is_array($parsed['mmf']??null) || !$parsed['mmf'] || count($parsed['mmf'])>12) {
            throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        }
        $flat=0;
        $validate=function(mixed $value,int $depth=0) use (&$validate,&$flat):void {
            if($depth>3)throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            if(is_array($value)) {
                if(count($value)>20)throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
                foreach($value as $v)$validate($v,$depth+1);
            }elseif(is_string($value)) {
                ++$flat;
                if(strlen($value)>120||preg_match('/[\x00-\x1f<>]/',$value))
                    throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
            }else throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        };
        $validate($parsed['mmf']);
        if($flat<1||$flat>40)throw new \DomainException('COM_MINIMARKET_SEOFILTER_INVALID');
        return ['category_id'=>$category,'alias'=>$alias,'title'=>$title,'heading'=>$heading,
            'introtext'=>$intro,'description'=>$description,'meta_description'=>$meta,
            'meta_robots'=>$robots,'image'=>$image,'image_alt'=>$imageAlt,
            'og_title'=>$ogTitle,'og_description'=>$ogDescription,'og_image'=>$ogImage,
            'filters_json'=>json_encode($parsed['mmf'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)];
    }
}
