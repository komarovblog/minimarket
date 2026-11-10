<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/**
 * The same canonical identity key is used for phone login, order contact
 * and future SMS verification. A typed number alone is not proof of ownership.
 */
final class PhoneIdentity
{
    public static function normalize(string $input): string
    {
        $value=trim($input);
        if ($value==='' || strlen($value)>40 || !preg_match('/^\+?[0-9()\-\s]+$/D',$value)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_PHONE_INVALID');
        }
        $digits=(string)preg_replace('/\D/','',$value);
        // Only the unambiguous Russian eleven-digit national form is expanded.
        if ($value[0]!=='+' && preg_match('/^8[0-9]{10}$/D',$digits)) {
            $digits='7'.substr($digits,1);
        } elseif ($value[0]!=='+' && !preg_match('/^7[0-9]{10}$/D',$digits)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_PHONE_INVALID');
        }
        $canonical='+'.$digits;
        if (!preg_match('/^\+[1-9][0-9]{7,14}$/D',$canonical)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_PHONE_INVALID');
        }
        return $canonical;
    }
}
