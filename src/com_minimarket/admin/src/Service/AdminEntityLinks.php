<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

/**
 * Administrator links. Route::_() defaults to XHTML escaping (&amp;).
 * Escape only once: route with xhtml=false, then encode for an HTML attribute.
 */
final class AdminEntityLinks
{
    private const ENTITIES = ['attribute', 'orderoption', 'category', 'product', 'group', 'value', 'brand', 'collection'];
    private const VIEWS = ['dashboard', 'categories', 'products', 'groups', 'attributes',
        'orderoptions', 'import', 'feeds', 'brands', 'collections', 'bundles', 'bundlegroups','orders','deliveries','deliveryzones','deliverymethods','paymentmethods','reviews','seofilters','promocodes','settings','integrations'];

    public static function edit(string $entity, int $id): string
    {
        if (!in_array($entity, self::ENTITIES, true) || $id < 1) {
            throw new \InvalidArgumentException('Invalid MiniMarket edit target');
        }

        return self::htmlUrl('index.php?option=com_minimarket&task=' . $entity . '.edit&id=' . $id);
    }

    public static function add(string $entity): string
    {
        if (!in_array($entity, self::ENTITIES, true)) {
            throw new \InvalidArgumentException('Invalid MiniMarket create target');
        }

        return self::htmlUrl('index.php?option=com_minimarket&task=' . $entity . '.add');
    }

    public static function view(string $view): string
    {
        if (!in_array($view, self::VIEWS, true)) {
            throw new \InvalidArgumentException('Invalid MiniMarket list target');
        }

        if ($view==='settings') {
            return self::htmlUrl('index.php?option=com_config&view=component&component=com_minimarket');
        }
        if ($view==='reviews') {
            return self::htmlUrl('index.php?option=com_minimarket&view=reviews&filter_status=pending');
        }
        if ($view==='deliveries') {
            return self::htmlUrl('index.php?option=com_minimarket&view=deliveries');
        }
        return self::htmlUrl('index.php?option=com_minimarket&view=' . $view);
    }

    private static function htmlUrl(string $url): string
    {
        // IMPORTANT: Route::_($url) already HTML-escapes '&'; doing that plus
        // htmlspecialchars() would produce '&amp;amp;' and hide task/view/id
        // from Joomla. The browser must receive a single '&amp;'.
        return htmlspecialchars(Route::_($url, false), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
