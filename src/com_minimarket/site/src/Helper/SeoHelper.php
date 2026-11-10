<?php
namespace MiniMarket\Component\Minimarket\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

/**
 * Canonical URL for this component's three public views.
 *
 * The Joomla router chooses the menu path and host/scheme; do not
 * hardcode minimarket.local, index.php or the category ancestry.
 * The site System - SEF plugin may normalize protocol/domain later.
 */
final class SeoHelper
{
    public static function setCanonical(HtmlDocument $document, string $view, int $id = 0, int $page = 1): void
    {
        if (!\in_array($view, ['catalog', 'category', 'product', 'seofilter'], true)) {
            throw new \InvalidArgumentException('Unknown MiniMarket SEO view');
        }

        if ($view !== 'catalog' && $id < 1) {
            throw new \InvalidArgumentException('Missing MiniMarket entity ID');
        }

        $route = 'index.php?option=com_minimarket&view=' . $view;

        if ($view !== 'catalog') {
            $route .= '&id=' . $id;
        }

        // Storefront menu items are independent Joomla pages: if the menu
        // points directly to MiniMarket, keep that page's own canonical.
        // Category and product URLs, in contrast, use the single primary
        // Shop item selected by the component router.
        if ($view === 'catalog') {
            $active = Factory::getApplication()->getMenu()->getActive();

            if ($active !== null
                && ($active->component ?? '') === 'com_minimarket'
                && ($active->query['view'] ?? null) === 'catalog') {
                $route .= '&Itemid=' . (int) $active->id;
            }
        }

        // Each paginated assortment page has its own canonical.
        // Deliberately avoid rewriting product/category URLs or menu aliases.
        if ($view === 'catalog' && $page > 1) {
            $route .= '&mm_page=' . $page;
        }

        // Joomla 6 API: last argument requests an absolute URL.
        $canonical = Route::_($route, false, Route::TLS_IGNORE, true);
        $scheme = parse_url($canonical, PHP_URL_SCHEME);

        if (!\in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            throw new \RuntimeException('MiniMarket canonical URL is not absolute');
        }

        $head = $document->getHeadData();
        $links = $head['links'] ?? [];

        // Joomla link entries are keyed by href. Avoid duplicate
        // canonical tags if a component or earlier plugin set one.
        foreach ($links as $href => $link) {
            if (($link['relation'] ?? null) === 'canonical') {
                unset($links[$href]);
            }
        }

        if (($head['links'] ?? []) !== $links) {
            $document->setHeadData(['links' => $links]);
        }

        $document->addHeadLink($canonical, 'canonical');
    }
}
