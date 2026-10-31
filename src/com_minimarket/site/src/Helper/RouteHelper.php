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

    public static function category(int $id): string
    {
        return Route::_('index.php?option=com_minimarket&view=category&id=' . $id, false);
    }

    public static function product(int $id): string
    {
        return Route::_('index.php?option=com_minimarket&view=product&id=' . $id, false);
    }
}
