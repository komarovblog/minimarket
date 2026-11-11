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

    public static function mode(object $params): string
    {
        $mode=(string)$params->get('checkout_auth_mode','guest');
        $mode=in_array($mode,['guest','sms','email'],true)?$mode:'sms';
        if($mode==='email' && (string)$params->get('checkout_verification_method','email')!=='email')
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_AUTH_NOT_CONFIGURED');
        return $mode;
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
