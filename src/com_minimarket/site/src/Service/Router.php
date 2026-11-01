<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;

/**
 * Typed category/alias-ID and product/alias-ID routes through ordinary
 * public Joomla Shop menu items (home or /shop). Multiple items are allowed;
 * product/category URLs have one deterministic primary route.
 * No hidden menu items or category ancestry in URLs.
 */
final class Router implements RouterInterface
{
    private AbstractMenu $menu;
    private DatabaseInterface $db;

    public function __construct($app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
    {
        $this->menu = $menu;
        $this->db = $db;
    }

    public function preprocess($query)
    {
        $items = $this->getShopItems();

        if (!$items) {
            throw new \RuntimeException('MiniMarket requires a published Shop menu item');
        }

        $view = (string) ($query['view'] ?? 'catalog');
        $requestedId = (int) ($query['Itemid'] ?? 0);

        // Each ordinary Joomla Shop menu entry may open its own storefront.
        // An explicit Itemid is preserved ONLY for the storefront view.
        // Product/category links always use a single canonical route.
        if ($view === 'catalog' && $requestedId > 0) {
            foreach ($items as $item) {
                if ((int) $item->id === $requestedId) {
                    return $query;
                }
            }
        }

        $query['Itemid'] = (int) $this->getPrimaryShopItem($items)->id;

        return $query;
    }

    public function build(&$query)
    {
        $view = (string) ($query['view'] ?? 'catalog');

        if ($view === 'catalog') {
            unset($query['view']);
            return [];
        }

        if (!\in_array($view, ['category', 'product'], true) || empty($query['id'])) {
            return [];
        }

        $id = filter_var($query['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            return [];
        }

        $record = $this->getPublished($view, (int) $id);

        if ($record === null) {
            return [];
        }

        unset($query['view'], $query['id']);
        return [$view, $record->alias . '-' . (int) $record->id];
    }

    public function parse(&$segments)
    {
        if (!$segments) {
            return ['view' => 'catalog'];
        }

        if (\count($segments) === 2) {
            $view = (string) $segments[0];

            if (!\in_array($view, ['category', 'product'], true)) {
                throw new \RuntimeException('MiniMarket route not found', 404);
            }

            $id = $this->parseSlug((string) $segments[1], $view);
            $segments = [];

            return ['view' => $view, 'id' => $id];
        }

        if (\count($segments) === 1) {
            // Temporary legacy compatibility; 301 redirects need separate tests.
            $matches = [];

            foreach (['category', 'product'] as $view) {
                try {
                    $matches[] = ['view' => $view, 'id' => $this->parseSlug((string) $segments[0], $view)];
                } catch (\RuntimeException $exception) {
                    if ($exception->getCode() !== 404) {
                        throw $exception;
                    }
                }
            }

            if (\count($matches) !== 1) {
                throw new \RuntimeException('MiniMarket legacy route not found or ambiguous', 404);
            }

            $segments = [];

            return $matches[0];
        }

        throw new \RuntimeException('MiniMarket route not found', 404);
    }

    /** @return object[] */
    private function getShopItems(): array
    {
        // SiteMenu already filters out unpublished entries; Joomla 6 menu
        // objects do not expose a "published" property.
        $items = $this->menu->getItems('component', 'com_minimarket') ?: [];
        $result = [];

        foreach ($items as $item) {
            if (($item->query['view'] ?? null) === 'catalog') {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Stable SEO route for products/categories with multiple public Shop items.
     *
     * 1. The explicitly checked "Primary shop URL" menu parameter.
     * 2. An ordinary Joomla home menu item pointing to MiniMarket.
     * 3. The oldest published MiniMarket menu item (smallest numeric ID).
     *
     * Never create or depend on hidden menu items.
     * @param object[] $items Non-empty ordinary published MiniMarket items.
     */
    private function getPrimaryShopItem(array $items): object
    {
        usort($items, static fn ($a, $b) => (int) $a->id <=> (int) $b->id);

        foreach ($items as $item) {
            if (method_exists($item, 'getParams')
                && (int) $item->getParams()->get('shop_primary_route', 0) === 1) {
                return $item;
            }
        }

        foreach ($items as $item) {
            if ((int) ($item->home ?? 0) !== 0) {
                return $item;
            }
        }

        return $items[0];
    }

    private function parseSlug(string $slug, string $view): int
    {
        if (!preg_match('/^([a-z0-9][a-z0-9-]*)-([1-9][0-9]*)$/D', $slug, $matches)) {
            throw new \RuntimeException('MiniMarket route not found', 404);
        }

        $id = filter_var($matches[2], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new \RuntimeException('MiniMarket route not found', 404);
        }

        $record = $this->getPublished($view, (int) $id);

        if ($record === null || $record->alias !== $matches[1]) {
            throw new \RuntimeException('MiniMarket route not found', 404);
        }

        return (int) $id;
    }

    private function getPublished(string $view, int $id): ?object
    {
        if ($view === 'category') {
            $query = $this->db->getQuery(true)
                ->select([$this->db->quoteName('id'), $this->db->quoteName('alias')])
                ->from($this->db->quoteName('#__minimarket_categories'))
                ->where($this->db->quoteName('id') . ' = ' . $id)
                ->where($this->db->quoteName('state') . ' = 1');
        } else {
            $query = $this->db->getQuery(true)
                ->select([$this->db->quoteName('p.id'), $this->db->quoteName('p.alias')])
                ->from($this->db->quoteName('#__minimarket_products', 'p'))
                ->join(
                    'INNER',
                    $this->db->quoteName('#__minimarket_categories', 'c')
                    . ' ON ' . $this->db->quoteName('p.category_id')
                    . ' = ' . $this->db->quoteName('c.id')
                )
                ->where($this->db->quoteName('p.id') . ' = ' . $id)
                ->where($this->db->quoteName('p.state') . ' = 1')
                ->where($this->db->quoteName('c.state') . ' = 1');
        }

        $this->db->setQuery($query);

        return $this->db->loadObject() ?: null;
    }
}
