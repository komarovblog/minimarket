<?php
namespace MiniMarket\Component\Minimarket\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductBadges;

/** Identical accessible badges and old-price markup across catalog/category/product. */
final class BadgeHelper
{
    private static function escape(string $value):string
    {
        return htmlspecialchars($value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function render(object $product):string
    {
        $info=ProductBadges::forProduct($product);
        $tags=[];
        if($info['sale']!==null) {
            $tags[]='<span class="mm-product-badge mm-product-badge--sale">'
                .self::escape(Text::sprintf('COM_MINIMARKET_BADGE_SALE_LABEL',$info['sale'])).'</span>';
        }
        if($info['new']) {
            $tags[]='<span class="mm-product-badge mm-product-badge--new">'
                .self::escape(Text::_('COM_MINIMARKET_BADGE_NEW_LABEL')).'</span>';
        }
        if($info['popular']) {
            $tags[]='<span class="mm-product-badge mm-product-badge--popular">'
                .self::escape(Text::_('COM_MINIMARKET_BADGE_POPULAR_LABEL')).'</span>';
        }
        return $tags ? '<div class="mm-product-badges" aria-label="'
            .self::escape(Text::_('COM_MINIMARKET_BADGES_PUBLIC')).'">'
            .implode('',$tags).'</div>' : '';
    }

    /** Old price is a comparison, not a second editable SKU price. */
    public static function oldPrice(object $product):string
    {
        $old=ProductBadges::forProduct($product)['old_price'];
        if($old===null)return '';
        return '<del class="mm-old-price" title="'
            .self::escape(Text::_('COM_MINIMARKET_BADGE_OLD_PRICE_LABEL')).'">'
            .self::escape($old).'</del>';
    }
}
