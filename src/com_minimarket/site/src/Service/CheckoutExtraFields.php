<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/** Declarative checkout fields, server-side validation and immutable order snapshot. */
final class CheckoutExtraFields
{
    public static function definitions(object $params): array
    {
        $raw=$params->get('checkout_custom_fields',[]);
        if(is_string($raw))$raw=json_decode($raw,true) ?: [];
        if(is_object($raw))$raw=(array)$raw;
        if(!is_array($raw))return [];
        if(count($raw)>20)throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
        $result=[];
        foreach($raw as $entry){
            if(is_object($entry))$entry=(array)$entry;
            if(!is_array($entry) || (int)($entry['state']??1)!==1)continue;
            $code=(string)($entry['code']??'');
            $title=trim((string)($entry['title']??''));
            $type=(string)($entry['type']??'text');
            if(!preg_match('/^[a-z][a-z0-9_]{1,39}$/D',$code)
                ||in_array($code,['name','phone','email','comment','website','checkout_key'],true)
                ||isset($result[$code])
                ||$title==='' ||mb_strlen($title)>120
                ||!in_array($type,['text','tel','email','number','textarea','select','checkbox'],true))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
            // Hidden type-specific settings remain saved in Joomla but must not invalidate a different active type.
            $mask=in_array($type,['text','tel'],true) ? trim((string)($entry['mask']??'')) : '';
            if(strlen($mask)>64 || ($mask!=='' && !preg_match('/^[\p{L}\p{N} ()+*._\/-]+$/uD',$mask)))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
            $choices=$type==='select'
                ?array_values(array_filter(array_map('trim',preg_split('/\r\n|\n|\r/',(string)($entry['options']??'')) ?: []),
                    static fn($v)=>$v!==''))
                :[];
            if(count($choices)>30 || array_filter($choices,static fn($v)=>mb_strlen($v)>120))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
            if($type==='select'&&!$choices)throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
            $result[$code]=[
              'code'=>$code,'title'=>strip_tags($title),'type'=>$type,
              'required'=>(int)($entry['required']??0)===1,
              'placeholder'=>in_array($type,['text','tel','email','number','textarea'],true)
                  ?mb_substr(strip_tags((string)($entry['placeholder']??'')),0,120):'',
              'description'=>mb_substr(strip_tags((string)($entry['description']??'')),0,500),
              'mask'=>$mask,'choices'=>$choices
            ];
        }
        return $result;
    }

    public static function validate(array $posted,array $definitions):array
    {
        $values=[];
        foreach($definitions as $code=>$field){
            $value=$posted[$code]??'';
            if(!is_scalar($value))throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
            $value=trim(strip_tags((string)$value));
            if(mb_strlen($value)>2000 ||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u',$value))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
            if($field['type']==='checkbox'){
                if(!in_array($value,['','0','1'],true))throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
                $value=$value==='1'?'1':'0';
            }
            if($field['required'] && ($value===''||($field['type']==='checkbox'&&$value==='0')))
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_REQUIRED');
            if($value!==''){
                if($field['type']==='select'&&!in_array($value,$field['choices'],true)
                    ||$field['type']==='email'&&filter_var($value,FILTER_VALIDATE_EMAIL)===false
                    ||$field['type']==='number'&&!preg_match('/^-?\d+(?:\.\d+)?$/D',$value)
                    ||$field['type']==='tel'&&!preg_match('/^[+0-9() \-]+$/D',$value))
                    throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_INVALID');
                if($field['mask']!==''&&!self::maskMatches($field['mask'],$value))
                    throw new \DomainException('COM_MINIMARKET_CHECKOUT_EXTRA_MASK_INVALID');
            }
            $values[]=['code'=>$code,'title'=>$field['title'],'value'=>$value];
        }
        return $values;
    }

    public static function maskMatches(string $mask,string $value):bool
    {
        $pattern='';
        foreach(preg_split('//u',$mask,-1,PREG_SPLIT_NO_EMPTY) ?: [] as $char){
            $pattern.=$char==='9'?'[0-9]':($char==='A'?'[\\p{L}]':($char==='*'?'.':preg_quote($char,'~')));
        }
        return @preg_match('~^'.$pattern.'$~uD',$value)===1;
    }
}
