<?php
namespace MiniMarket\Component\Minimarket\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

/**
 * One route builder for MiniMarket links (always use Joomla's router).
 */
final class RouteHelper
{
    public static function catalog(): string
    {
        return Route::_('index.php?option=com_minimarket&view=catalog', false);
    }

    /**
     * Storefront pagination retains the currently opened public Shop menu item
     * rather than silently switching to the canonical product/category route.
     */
    public static function catalogPage(int $page, int $itemId): string
    {
        $route = 'index.php?option=com_minimarket&view=catalog';

        if ($itemId > 0) {
            $route .= '&Itemid=' . $itemId;
        }

        if ($page > 1) {
            $route .= '&mm_page=' . $page;
        }

        return Route::_($route, false);
    }

    public static function category(int $id): string
    {
        return Route::_('index.php?option=com_minimarket&view=category&id=' . $id, false);
    }

    public static function product(int $id): string
    {
        return Route::_('index.php?option=com_minimarket&view=product&id=' . $id, false);
    }
}
