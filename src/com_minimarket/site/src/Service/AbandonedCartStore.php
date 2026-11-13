<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/**
 * Bounded cart snapshot for abandonment analysis.
 * Identifies only the first-party Joomla session via a random opaque token.
 * No email, telephone, IP, recipient, campaign permission or client-side tracking.
 */
final class AbandonedCartStore
{
    private const TOKEN_KEY='com_minimarket.cart_observation_key';
    public const ABANDONED_MINUTES=60;
    public const RETENTION_DAYS=30;

    /** @return array<string,int> */
    public static function sanitize(array $cart):array
    {
        return CartState::normalize($cart);
    }

    private static function token(object $session,bool $create):string
    {
        $token=(string)$session->get(self::TOKEN_KEY,'');
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)){
            if(!$create)return '';
            $token=bin2hex(random_bytes(32));
            $session->set(self::TOKEN_KEY,$token);
        }
        return $token;
    }

    /** Called only after successful cart mutation, never before CSRF validation. */
    public static function record(DatabaseInterface $db,object $session,int $userId,array $cart):void
    {
        $cart=self::sanitize($cart);
        $token=self::token($session,(bool)$cart);
        if($token==='')return;
        $table=$db->quoteName('#__minimarket_cart_snapshots');
        $json=json_encode($cart,JSON_THROW_ON_ERROR);
        $key=hash('sha256',$token);
        $db->setQuery('INSERT INTO '.$table
            .' (cart_key,user_id,items_json,item_units,cart_status,converted_order_id,created_at,updated_at) VALUES('
            .$db->quote($key).',' .max(0,$userId).','.$db->quote($json).','
            .array_sum($cart).','.$db->quote($cart?'active':'cleared')
            .',NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP())'
            .' ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),items_json=VALUES(items_json),'
            .'item_units=VALUES(item_units),cart_status=VALUES(cart_status),'
            .'converted_order_id=NULL,updated_at=VALUES(updated_at)');
        $db->execute();
    }

    /** Never changes OrderStorage state. A completed order is NOT abandoned. */
    public static function converted(DatabaseInterface $db,object $session,int $orderId):void
    {
        if($orderId<1)return;
        $token=self::token($session,false);
        if($token==='')return;
        $key=hash('sha256',$token);
        $db->setQuery('UPDATE '.$db->quoteName('#__minimarket_cart_snapshots')
            ." SET cart_status='converted',converted_order_id=".$orderId
            .',updated_at=UTC_TIMESTAMP() WHERE cart_key='.$db->quote($key)
            ." AND cart_status='active'");
        $db->execute();
        $session->set(self::TOKEN_KEY,'');
    }

    public static function abandonedPredicate(string $alias='c'):string
    {
        if(!preg_match('/^[a-z]$/D',$alias))
            throw new \InvalidArgumentException('Invalid table alias');
        return $alias.".cart_status='active' AND ".$alias.'.item_units>0 AND '
            .$alias.'.updated_at<=DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.self::ABANDONED_MINUTES.' MINUTE)'
            .' AND '.$alias.'.updated_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.self::RETENTION_DAYS.' DAY)';
    }
}
