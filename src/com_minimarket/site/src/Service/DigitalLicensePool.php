<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * License codes are AES-unrelated libsodium secretboxes, never public in an
 * HTML form or a Joomla Media file. Issued keys are never recycled.
 */
final class DigitalLicensePool
{
    private static function secret():string
    {
        if(!function_exists('sodium_crypto_secretbox'))
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_UNAVAILABLE');
        $siteSecret=(string)Factory::getConfig()->get('secret','');
        if(strlen($siteSecret)<16)
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_UNAVAILABLE');
        return hash_hmac('sha256','minimarket-license-pool-v1',$siteSecret,true);
    }

    private static function encrypt(string $code,string $key):string
    {
        $nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce.sodium_crypto_secretbox($code,$nonce,$key));
    }

    private static function decrypt(string $payload,string $key):string
    {
        $raw=base64_decode($payload,true);
        if($raw===false ||strlen($raw)<=SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_UNAVAILABLE');
        $nonce=substr($raw,0,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $code=sodium_crypto_secretbox_open(substr($raw,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),$nonce,$key);
        if($code===false)throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_UNAVAILABLE');
        return $code;
    }

    public static function available(DatabaseInterface $db,int $productId):int
    {
        if($productId<1)return 0;
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_digital_keys'))
            ->where('product_id='.(int)$productId)
            ->where('order_item_id IS NULL');
        $db->setQuery($q);
        return (int)$db->loadResult();
    }

    /** Admin import only. Textarea never prepopulates existing codes. */
    public static function import(DatabaseInterface $db,int $productId,string $input):int
    {
        if(trim($input)==='')return 0;
        if(strlen($input)>40000||$productId<1)
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_INVALID');
        $db->setQuery('SELECT delivery_type,digital_delivery_mode FROM '
            .$db->quoteName('#__minimarket_products').' WHERE id='.(int)$productId);
        $product=$db->loadAssoc();
        if(!$product||$product['delivery_type']!=='digital'
            ||$product['digital_delivery_mode']!=='license')
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_INVALID');
        $codes=array_values(array_filter(array_map('trim',
            preg_split('/\r\n|\r|\n/',trim($input))?:[]),
            static fn($x)=>$x!==''));
        if(!$codes||count($codes)>200
            ||count($codes)!==count(array_unique($codes)))
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_INVALID');
        $key=self::secret();
        foreach($codes as $code){
            if(strlen($code)>500||preg_match('/[\x00-\x1f\x7f]/',$code))
                throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_INVALID');
            $fingerprint=hash_hmac('sha256','license-code:'.$code,$key);
            $q=$db->getQuery(true)->insert($db->quoteName('#__minimarket_digital_keys'))
                ->columns($db->quoteName(['product_id','fingerprint','encrypted_code','created_at']))
                ->values(implode(',',[(string)$productId,$db->quote($fingerprint),
                    $db->quote(self::encrypt($code,$key)),'UTC_TIMESTAMP()']));
            // Duplicate codes are rejected by unique(product_id,fingerprint).
            $db->setQuery($q);$db->execute();
        }
        return count($codes);
    }

    /** Must run in the paid-order transaction with row locks held. */
    public static function assign(DatabaseInterface $db,int $productId,int $orderItemId,int $quantity):void
    {
        if($productId<1||$orderItemId<1||$quantity<1||$quantity>100)
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_UNAVAILABLE');
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_digital_keys'))
            ->where('product_id='.$productId)->where('order_item_id='.$orderItemId);
        $db->setQuery($q);
        $assigned=(int)$db->loadResult();
        if($assigned>$quantity)throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_UNAVAILABLE');
        $remaining=$quantity-$assigned;
        if(!$remaining)return;
        $db->setQuery('SELECT id FROM '.$db->quoteName('#__minimarket_digital_keys')
            .' WHERE product_id='.$productId.' AND order_item_id IS NULL'
            .' ORDER BY id ASC LIMIT '.$remaining.' FOR UPDATE');
        $ids=array_map('intval',(array)$db->loadColumn());
        if(count($ids)!==$remaining)
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_OUT_OF_STOCK');
        $q=$db->getQuery(true)->update($db->quoteName('#__minimarket_digital_keys'))
            ->set('order_item_id='.$orderItemId)->set('assigned_at=UTC_TIMESTAMP()')
            ->where('id IN ('.implode(',',$ids).')')->where('order_item_id IS NULL');
        $db->setQuery($q);$db->execute();
        if((int)$db->getAffectedRows()!==$remaining)
            throw new \DomainException('COM_MINIMARKET_DIGITAL_LICENSE_OUT_OF_STOCK');
    }

    /** Called only by paid-order fulfillment; never exposed to anonymous query. */
    public static function forOrderItem(DatabaseInterface $db,int $id):array
    {
        $q=$db->getQuery(true)->select('encrypted_code')
            ->from($db->quoteName('#__minimarket_digital_keys'))
            ->where('order_item_id='.(int)$id)->order('id ASC');
        $db->setQuery($q);
        $key=self::secret();
        return array_map(static fn($s):string=>self::decrypt((string)$s,$key),
            (array)$db->loadColumn());
    }
}
