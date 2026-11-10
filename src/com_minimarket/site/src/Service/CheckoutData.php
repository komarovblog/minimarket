<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/** Validate contact details before any order is written. No prices in POST. */
final class CheckoutData
{
    /** @return array{name:string,phone:string,email:string,comment:string} */
    public static function normalize(array $data): array
    {
        foreach (['name','phone','email','comment'] as $field) {
            if (!is_scalar($data[$field] ?? '')) {
                throw new \DomainException('COM_MINIMARKET_CHECKOUT_INVALID');
            }
        }
        $name=trim(preg_replace('/\s+/u',' ',strip_tags((string)($data['name']??''))) ?? '');
        $phone=trim((string)($data['phone']??''));
        $email=trim((string)($data['email']??''));
        $comment=trim(strip_tags((string)($data['comment']??'')));
        if (mb_strlen($name)<2 || mb_strlen($name)>120
            || preg_match('/[\x00-\x1f<>]/u',$name)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_NAME_INVALID');
        }
        $digits=preg_replace('/\D/','',$phone);
        if (strlen((string)$digits)<7 || strlen((string)$digits)>18
            || strlen($phone)>32 || !preg_match('/^\+?[0-9() \-]+$/D',$phone)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_PHONE_INVALID');
        }
        if ($email!=='' && (strlen($email)>254
            || filter_var($email,FILTER_VALIDATE_EMAIL)===false)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_EMAIL_INVALID');
        }
        if (mb_strlen($comment)>2000
            || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u',$comment)) {
            throw new \DomainException('COM_MINIMARKET_CHECKOUT_COMMENT_INVALID');
        }
        return compact('name','phone','email','comment');
    }
}
