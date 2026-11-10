<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/**
 * Cryptographic OTP primitives only. Persist digests and counters atomically,
 * enforce rate limits in DB, and send with a configured external SMS provider
 * BEFORE exposing a public verification endpoint. No demo or bypass codes.
 */
final class PhoneOtp
{
    public const TTL_SECONDS=300;
    public const MAX_ATTEMPTS=5;

    /** @return array{code:string,nonce:string,digest:string,expires_at:int} */
    public static function issue(string $phone,string $intent,string $sessionId,string $secret,?int $now=null): array
    {
        $phone=PhoneIdentity::normalize($phone);
        self::context($intent,$sessionId,$secret);
        $code=(string)random_int(100000,999999);
        $nonce=bin2hex(random_bytes(16));
        return [
            'code'=>$code,
            'nonce'=>$nonce,
            'digest'=>self::digest($phone,$intent,$sessionId,$nonce,$code,$secret),
            'expires_at'=>($now??time())+self::TTL_SECONDS
        ];
    }

    public static function verify(
        string $phone,string $intent,string $sessionId,string $secret,
        string $nonce,string $storedDigest,string $submittedCode,
        int $expiresAt,int $attempts,?int $now=null
    ): bool {
        if (($now??time())>=$expiresAt || $attempts<0 || $attempts>=self::MAX_ATTEMPTS
            || !preg_match('/^[0-9]{6}$/D',$submittedCode)
            || !preg_match('/^[a-f0-9]{32}$/D',$nonce)
            || !preg_match('/^[a-f0-9]{64}$/D',$storedDigest)) {
            return false;
        }
        try {
            $phone=PhoneIdentity::normalize($phone);
            self::context($intent,$sessionId,$secret);
            return hash_equals($storedDigest,self::digest(
                $phone,$intent,$sessionId,$nonce,$submittedCode,$secret
            ));
        } catch (\InvalidArgumentException | \DomainException $e) {
            return false;
        }
    }

    private static function context(string $intent,string $sessionId,string $secret): void
    {
        if (!in_array($intent,['login','checkout','link'],true)
            || strlen($sessionId)<16 || strlen($secret)<32) {
            throw new \InvalidArgumentException('Invalid OTP context');
        }
    }

    private static function digest(
        string $phone,string $intent,string $sessionId,string $nonce,
        string $code,string $secret
    ): string {
        return hash_hmac('sha256',
            implode("\0",[$phone,$intent,$sessionId,$nonce,$code]),$secret);
    }
}
