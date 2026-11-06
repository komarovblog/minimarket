<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Product/bundle badges. Effective product.price is supplied by CatalogRepository:
 * for a bundle it is already its calculated price, not the stored fallback.
 * Pure PHP logic: no N+1 queries, no cron, no mutation on public page views.
 */
final class ProductBadges
{
    /** Comparison is done in integer kopecks, so 5.01 vs 5.00 is still a 1% badge. */
    private static function cents(mixed $number): ?int
    {
        $s=trim((string)($number??''));
        if(!preg_match('/^(0|[1-9][0-9]{0,12})(?:\.([0-9]{1,2}))?$/D',$s,$m)) return null;
        return ((int)$m[1])*100 + (int)str_pad($m[2]??'',2,'0');
    }

    /** For an unchanged ON flag do not restart the timer when the product is saved. */
    public static function activationSince(bool $on, bool $wasOn, ?string $previous, string $now): ?string
    {
        if (!$on) return null;
        if ($wasOn && is_string($previous) &&
            preg_match('/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/D',$previous)) {
            return $previous;
        }
        return $now;
    }

    private static function timed(bool $on, mixed $days, mixed $since, \DateTimeImmutable $now): bool
    {
        if(!$on) return false;
        if($days===null || $days==='') return true;
        $n=(int)$days;
        if($n<1 || $n>36500 || !is_string($since) || $since==='') return false;
        try {
            $start=new \DateTimeImmutable($since,new \DateTimeZone('UTC'));
        } catch (\Throwable) {
            return false;
        }
        return $now < $start->modify('+'.$n.' days');
    }

    /**
     * @return array{sale:?int,new:bool,popular:bool,old_price:?string}
     * Sale toggle controls only the badge; valid old price is independently
     * shown as a comparison even when the badge is intentionally disabled.
     */
    public static function forProduct(object $product, ?\DateTimeImmutable $now=null): array
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $old=self::cents($product->old_price??null);
        $current=self::cents($product->price??null);
        // Automatic bundle discounts use Savings, not Sale/old-price labels.
        $automaticBundle=($product->product_type??'simple')==='bundle'
            && ($product->bundle_price_mode??'fixed')==='discount';
        $comparable=!$automaticBundle && $old!==null && $current!==null
            && $old>0 && $current<$old;
        $sale=null;
        if($comparable && (int)($product->badge_sale??0)===1) {
            $sale=intdiv(($old-$current)*100+$old-1,$old);
        }
        $amount=$comparable && $sale!==null
            ? number_format(($old-$current)/100,2,'.','') : null;
        $benefit=null;
        $benefitPercent = null;
        if ((int)($product->badge_benefit??0)===1
            && ($product->product_type??'simple')==='bundle'
            && ($product->bundle_price_mode??'fixed')==='discount') {
            $value=self::cents($product->bundle_discount_amount??null);
            if ($value!==null && $value>0) {
                $benefit=number_format($value/100,2,'.','');
                $benefitPercent=(string) (float)($product->bundle_discount_pct??0);
            }
        }
        return [
            'sale'=>$sale,
            'sale_amount'=>$amount,
            'benefit_amount'=>$benefit,
            'benefit_percent'=>$benefitPercent,
            'sale_display'=>in_array(($product->badge_sale_display??'percent'),['percent','money'],true)
                ? $product->badge_sale_display : 'percent',
            'benefit_display'=>in_array(($product->badge_benefit_display??'money'),['percent','money'],true)
                ? $product->badge_benefit_display : 'money',
            'new'=>self::timed((int)($product->badge_new??0)===1,
                $product->badge_new_days??null,$product->badge_new_since??null,$now),
            'popular'=>self::timed((int)($product->badge_popular??0)===1,
                $product->badge_popular_days??null,$product->badge_popular_since??null,$now),
            'old_price'=>$comparable ? number_format($old/100,2,'.','') : null,
        ];
    }
}
