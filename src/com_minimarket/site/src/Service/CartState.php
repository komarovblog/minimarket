<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/** Minimal bounded cart payload stored in the Joomla session; never stores prices. */
final class CartState
{
    public const SESSION_KEY = 'com_minimarket.cart.v1';
    public const MAX_LINES = 80;
    public const MAX_QUANTITY = 99;
    public const MAX_UNITS = 500;

    public static function key(int $productId, int $variantId = 0): string
    {
        if ($productId < 1 || $variantId < 0) {
            throw new \DomainException('COM_MINIMARKET_CART_INVALID_ITEM');
        }
        return $productId . ':' . $variantId;
    }

    /** @return array<string,int> */
    public static function normalize(mixed $raw): array
    {
        if (!is_array($raw)) return [];
        $result = []; $units = 0;
        foreach ($raw as $key => $quantity) {
            if (!is_string($key) || !preg_match('/^[1-9][0-9]{0,9}:(?:0|[1-9][0-9]{0,9})$/D', $key)) continue;
            if (!is_int($quantity) || $quantity < 1 || $quantity > self::MAX_QUANTITY) continue;
            if (count($result) >= self::MAX_LINES || $units + $quantity > self::MAX_UNITS) break;
            $result[$key] = $quantity;
            $units += $quantity;
        }
        return $result;
    }

    public static function put(array $current, int $productId, int $variantId, int $quantity): array
    {
        if ($quantity < 0 || $quantity > self::MAX_QUANTITY) {
            throw new \DomainException('COM_MINIMARKET_CART_QUANTITY_ERROR');
        }
        $current = self::normalize($current);
        $key = self::key($productId, $variantId);
        if ($quantity === 0) unset($current[$key]); else $current[$key] = $quantity;
        if (count($current) > self::MAX_LINES || array_sum($current) > self::MAX_UNITS) {
            throw new \DomainException('COM_MINIMARKET_CART_LIMIT_ERROR');
        }
        return $current;
    }

    public static function add(array $current, int $productId, int $variantId, int $quantity): array
    {
        if ($quantity < 1) throw new \DomainException('COM_MINIMARKET_CART_QUANTITY_ERROR');
        $current = self::normalize($current);
        $key = self::key($productId, $variantId);
        return self::put($current, $productId, $variantId, ($current[$key] ?? 0) + $quantity);
    }

    public static function count(array $cart): int
    {
        return array_sum(self::normalize($cart));
    }

    public static function cents(string $price): int
    {
        if (!preg_match('/^([0-9]{1,11})\.([0-9]{2})$/D', $price, $m)) {
            throw new \DomainException('COM_MINIMARKET_CART_PRICE_ERROR');
        }
        $value = ((int)$m[1] * 100) + (int)$m[2];
        if ($value < 0 || $value > 999999999999) {
            throw new \DomainException('COM_MINIMARKET_CART_PRICE_ERROR');
        }
        return $value;
    }

    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ' ');
    }
}
