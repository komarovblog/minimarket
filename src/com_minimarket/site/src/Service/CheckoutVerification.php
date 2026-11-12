<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Short-lived email challenge tied to the buyer, checkout and destination.
 * The OTP is never stored or logged as clear text. Nonce and order key
 * must not be used as payment authorization.
 */
final class CheckoutVerification
{
    private const STORAGE='com_minimarket.checkout.otp.';
    private const EXPIRES=600;
    private const COOLDOWN=60;
    private const MAX_PER_HOUR=5;
    private const MAX_ATTEMPTS=5;

    private static function bucket(string $scope):string
    {
        if(!in_array($scope,['purchase','recipient'],true))
            throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
        return self::STORAGE.$scope;
    }

    public static function issue(object $session,string $scope,int $userId,string $key,string $email):void
    {
        $bucket=self::bucket($scope);
        $email=mb_strtolower(trim($email));
        if(!preg_match('/^[a-f0-9]{32}$/D',$key)
            ||filter_var($email,FILTER_VALIDATE_EMAIL)===false
            ||strlen($email)>254)
            throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
        $now=time();
        $prev=$session->get($bucket,[]);
        if(!is_array($prev))$prev=[];
        $window=(int)($prev['window']??0);
        $count=$window>$now-3600?(int)($prev['count']??0):0;
        if($now-(int)($prev['sent']??0)<self::COOLDOWN
            ||$count>=self::MAX_PER_HOUR)
            throw new \DomainException('COM_MINIMARKET_OTP_RATE_LIMIT');
        $code=(string)random_int(100000,999999);
        $secret=(string)Factory::getConfig()->get('secret','');
        if(strlen($secret)<16)throw new \DomainException('COM_MINIMARKET_OTP_UNAVAILABLE');
        $hash=hash_hmac('sha256',$scope.'|'.$userId.'|'.$key.'|'.$email.'|'.$code,$secret);
        $session->set($bucket,[
            'hash'=>$hash,'sent'=>$now,'expires'=>$now+self::EXPIRES,
            'attempts'=>0,'user_id'=>$userId,'key'=>$key,'email'=>$email,
            'window'=>$count===0?$now:$window,'count'=>$count+1,
        ]);
        try {
            $mail=Factory::getMailer();
            $config=Factory::getConfig();
            $mail->setSender([(string)$config->get('mailfrom'),(string)$config->get('fromname')]);
            $mail->addRecipient($email);
            $lang=Factory::getApplication()->getLanguage();
            $lang->load('com_minimarket',JPATH_SITE,$lang->getTag(),true);
            $mail->setSubject(Text::_('COM_MINIMARKET_OTP_SUBJECT'));
            $mail->isHtml(false);
            $mail->setBody(Text::sprintf('COM_MINIMARKET_OTP_BODY',$code));
            $sent=$mail->send();
            if($sent===false ||$sent instanceof \Throwable)
                throw new \RuntimeException('Mail transport rejected email');
        }catch(\Throwable){
            // Rate limiting remains in force on failed SMTP attempts.
            throw new \DomainException('COM_MINIMARKET_OTP_UNAVAILABLE');
        }
    }

    public static function assertValid(object $session,string $scope,int $userId,string $key,
        string $email,string $code):void
    {
        $bucket=self::bucket($scope);
        $data=$session->get($bucket,[]);
        $now=time();
        if(!is_array($data)||!preg_match('/^[0-9]{6}$/D',$code)
            ||!preg_match('/^[a-f0-9]{32}$/D',$key)
            ||(int)($data['expires']??0)<=$now
            ||(int)($data['user_id']??-1)!==$userId
            ||!hash_equals((string)($data['key']??''),$key)
            ||!hash_equals((string)($data['email']??''),mb_strtolower(trim($email)))
            ||(int)($data['attempts']??0)>=self::MAX_ATTEMPTS)
            throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
        $secret=(string)Factory::getConfig()->get('secret','');
        if(strlen($secret)<16)throw new \DomainException('COM_MINIMARKET_OTP_UNAVAILABLE');
        $hash=hash_hmac('sha256',$scope.'|'.$userId.'|'.$key.'|'.mb_strtolower(trim($email)).'|'.$code,$secret);
        if(!hash_equals((string)($data['hash']??''),$hash)){
            $data['attempts']=(int)$data['attempts']+1;
            $session->set($bucket,$data);
            throw new \DomainException('COM_MINIMARKET_OTP_INVALID');
        }
    }

    public static function consume(object $session):void
    {
        foreach(['purchase','recipient'] as $kind){
            $session->set(self::STORAGE.$kind,[]);
        }
    }
}
