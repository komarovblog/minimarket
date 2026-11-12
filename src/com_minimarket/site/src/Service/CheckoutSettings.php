<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/** Single source of truth for global checkout field and login settings. */
final class CheckoutSettings
{
    public const FIELD_DEFAULTS=[
        'name'=>'required',
        'phone'=>'required',
        'email'=>'optional',
        'comment'=>'optional',
    ];

    /** guest: no identity check; guests: signed-in only; all: signed-in + fresh OTP. */
    public static function mode(object $params):string
    {
        $value=(string)$params->get('checkout_auth_mode','guest');
        // Values saved in 0.5.99 represent the old signed-in-only mode.
        if(in_array($value,['email','sms'],true))return 'guests';
        return in_array($value,['guest','guests','all'],true)?$value:'guests';
    }

    public static function requiresAccount(object $params):bool
    {
        return self::mode($params)!=='guest';
    }

    public static function requiresPurchaseCode(object $params):bool
    {
        return self::mode($params)==='all';
    }

    public static function step(object $params,string $step):bool
    {
        if(!in_array($step,['buyer','delivery','payment','digital'],true))
            throw new \InvalidArgumentException('Unknown checkout step');
        return (int)$params->get('checkout_step_'.$step.'_enabled',1)===1;
    }

    /** No silent fallback to an unavailable verification provider. */
    public static function method(object $params):string
    {
        $method=(string)$params->get('checkout_verification_method','email');
        if($method!=='email')throw new \DomainException('COM_MINIMARKET_CHECKOUT_AUTH_NOT_CONFIGURED');
        return $method;
    }

    /** Auto-hide buyer data only when the account contains every required detail. */
    public static function skipBuyer(object $params,object $user,array $customFields=[]):bool
    {
        if((int)($user->id??0)<1 || !empty($user->block)
            ||mb_strlen(trim((string)($user->name??'')))<2
            ||filter_var((string)($user->email??''),FILTER_VALIDATE_EMAIL)===false)return false;
        $fields=self::fields($params);
        if($fields['phone']==='required'||$fields['comment']==='required')return false;
        foreach($customFields as $field)if(!empty($field['required']))return false;
        return true;
    }

    /** @return array<string,string> */
    public static function fields(object $params): array
    {
        $result=[];
        foreach (self::FIELD_DEFAULTS as $name=>$default) {
            $mode=(string)$params->get('checkout_'.$name.'_mode',$default);
            $result[$name]=in_array($mode,['hidden','optional','required'],true)?$mode:$default;
        }
        // An order must offer a way to contact the buyer. This setting is
        // enforced on the server even if somebody posts hidden form values.
        if ($result['phone']==='hidden' && $result['email']==='hidden') {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_CONTACT_CONFIG_INVALID');
        }
        return $result;
    }
}
