<?php
/** MiniMarket OTP primitives for future phone registration (not active in checkout). */
declare(strict_types=1);
define('_JEXEC',1);
require __DIR__.'/../src/com_minimarket/site/src/Service/PhoneIdentity.php';
require __DIR__.'/../src/com_minimarket/site/src/Service/PhoneOtp.php';
use MiniMarket\Component\Minimarket\Site\Service\PhoneIdentity;
use MiniMarket\Component\Minimarket\Site\Service\PhoneOtp;

$n=0;
$ok=function(bool $condition,string $message)use(&$n):void{
    $n++;
    if(!$condition)throw new RuntimeException('MM79_AUTH_FAILED '.$message);
};
$reject=function(callable $f)use($ok):void{
    try{$f();$ok(false,'should have rejected');}
    catch(DomainException | InvalidArgumentException $e){$ok(true,'rejected invalid context');}
};
$ok(PhoneIdentity::normalize('+7 (999) 123-45-67')==='+79991234567','Russian intl form');
$ok(PhoneIdentity::normalize('8 (999) 123-45-67')==='+79991234567','Russian trunk form');
$ok(PhoneIdentity::normalize('+372 5123 4567')==='+37251234567','international E164');
$reject(fn()=>PhoneIdentity::normalize('9991234567'));
$reject(fn()=>PhoneIdentity::normalize('abcdef'));
$reject(fn()=>PhoneIdentity::normalize('+000012345678'));
$secret=str_repeat('a',40);
$session='authenticated-browser-session-key';
$otp=PhoneOtp::issue('+79991234567','checkout',$session,$secret,100);
$ok((bool)preg_match('/^[0-9]{6}$/D',$otp['code']),'six-digit code');
$ok((bool)preg_match('/^[a-f0-9]{64}$/D',$otp['digest']),'digest is HMAC');
$ok(PhoneOtp::verify('+79991234567','checkout',$session,$secret,$otp['nonce'],
    $otp['digest'],$otp['code'],$otp['expires_at'],0,101),'valid OTP');
$ok(!PhoneOtp::verify('+79991234568','checkout',$session,$secret,$otp['nonce'],
    $otp['digest'],$otp['code'],$otp['expires_at'],0,101),'other phone denied');
$ok(!PhoneOtp::verify('+79991234567','login',$session,$secret,$otp['nonce'],
    $otp['digest'],$otp['code'],$otp['expires_at'],0,101),'other purpose denied');
$ok(!PhoneOtp::verify('+79991234567','checkout','different-browser-session',$secret,$otp['nonce'],
    $otp['digest'],$otp['code'],$otp['expires_at'],0,101),'other session denied');
$ok(!PhoneOtp::verify('+79991234567','checkout',$session,$secret,$otp['nonce'],
    $otp['digest'],$otp['code'],$otp['expires_at'],0,400),'expired code denied');
$ok(!PhoneOtp::verify('+79991234567','checkout',$session,$secret,$otp['nonce'],
    $otp['digest'],$otp['code'],$otp['expires_at'],PhoneOtp::MAX_ATTEMPTS,101),'rate limit');
$reject(fn()=>PhoneOtp::issue('+79991234567','checkout',$session,'too short'));
$root=dirname(__DIR__).'/src/com_minimarket/';
$xml=(string)file_get_contents($root.'minimarket.xml');
$ok(str_contains($xml,'<version>0.5.84</version>'),'manifest version');
$storage=(string)file_get_contents($root.'site/src/Service/OrderStorage.php');
$ok(str_contains($storage,'$userId<0') && !str_contains($storage,'$userId<1'),
    'interim orders allow anonymous contact-only requests');
$ctrl=(string)file_get_contents($root.'site/src/Controller/CheckoutController.php');
$ok(!str_contains($ctrl,'$identity->id<1')
    && str_contains($ctrl,'OrderStorage::create'),
    'interim order controller permits guests');
$migration=(string)file_get_contents($root.'admin/sql/updates/mysql/0.5.79.sql');
$ok(str_contains($migration,'#__minimarket_user_phones')
    && str_contains($migration,'#__minimarket_phone_challenges'),'additive schema');
echo "MM79_AUTH_FOUNDATION_PASS: $n checks\n";
