<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
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

        // A dedicated Cart menu item has its own clean URL. Product/category
        // URLs continue to use the primary Shop item, unchanged.
        if ($view === 'cart') {
            $cartItems = $this->getCartItems();
            if ($cartItems) {
                foreach ($cartItems as $item) {
                    if ($requestedId > 0 && (int) $item->id === $requestedId) {
                        return $query;
                    }
                }
                usort($cartItems, static fn ($a, $b) => (int) $a->id <=> (int) $b->id);
                $query['Itemid'] = (int) $cartItems[0]->id;
                return $query;
            }
        }

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
        if ($view === 'seofilter' && isset($query['id'])) {
            $id=filter_var($query['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if ($id===false) return [];
            $record=$this->getPublished('seofilter',(int)$id);
            if ($record===null)return [];
            unset($query['view'],$query['id']);
            return ['seo',$record->alias.'-'.(int)$id];
        }
        if ($view === 'cart') {
            // When anchored at a dedicated cart menu item, the menu alias
            // supplies the full route; do not append a second /cart segment.
            foreach ($this->getCartItems() as $item) {
                if ((int) $item->id === (int) ($query['Itemid'] ?? 0)) {
                    unset($query['view']);
                    return [];
                }
            }
            unset($query['view']);
            return ['cart'];
        }
        if ($view === 'notificationpreferences') {
            unset($query['view']);
            return ['notification-preferences'];
        }
        if ($view === 'checkout') {
            unset($query['view']);
            return ['checkout'];
        }
        if ($view === 'ordercomplete') {
            unset($query['view']);
            return ['order-complete'];
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
            // Joomla has already matched the menu path: an empty tail on a
            // Cart item is the cart, whereas an empty Shop tail is the catalog.
            $active = $this->menu->getActive();
            if ($active !== null && ($active->component ?? '') === 'com_minimarket'
                && ($active->query['view'] ?? null) === 'cart') {
                return ['view' => 'cart'];
            }
            return ['view' => 'catalog'];
        }

        if (\count($segments) === 2) {
            $view = (string) $segments[0];

            if ($view==='seo') {
                $id=$this->parseSlug((string)$segments[1],'seofilter');
                $segments=[];
                return ['view'=>'seofilter','id'=>$id];
            }
            if (!\in_array($view, ['category', 'product'], true)) {
                throw new \RuntimeException('MiniMarket route not found', 404);
            }

            $id = $this->parseSlug((string) $segments[1], $view);
            $segments = [];

            return ['view' => $view, 'id' => $id];
        }

        if (\count($segments) === 1) {
            if ((string)$segments[0] === 'cart') {
                $segments = [];
                return ['view'=>'cart'];
            }
            if ((string)$segments[0] === 'notification-preferences') {
                $segments=[];
                return ['view'=>'notificationpreferences'];
            }
            if ((string)$segments[0] === 'checkout') {
                $segments = [];
                return ['view'=>'checkout'];
            }
            if ((string)$segments[0] === 'order-complete') {
                $segments = [];
                return ['view'=>'ordercomplete'];
            }
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
    private function getCartItems(): array
    {
        $items = $this->menu->getItems('component', 'com_minimarket') ?: [];
        return array_values(array_filter($items, static fn (object $item): bool =>
            ($item->query['view'] ?? null) === 'cart'));
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
     * One preferred base for generated category/product URLs.
     *
     * 1. Global MiniMarket option "primary_shop_itemid" when it points
     *    to a currently published ordinary MiniMarket storefront item.
     * 2. The published MiniMarket home menu item, if available.
     * 3. The published MiniMarket item with the lowest numeric ID.
     *
     * Historical per-menu "shop_primary_route" params are deliberately
     * ignored. No plugins or edits to Joomla menu records are required.
     *
     * @param object[] $items Non-empty published storefront items.
     */
    private function getPrimaryShopItem(array $items): object
    {
        usort($items, static fn ($a, $b) => (int) $a->id <=> (int) $b->id);

        $configuredId = (int) ComponentHelper::getParams('com_minimarket')
            ->get('primary_shop_itemid', 0);

        if ($configuredId > 0) {
            foreach ($items as $item) {
                if ((int) $item->id === $configuredId) {
                    return $item;
                }
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
        if ($view==='seofilter') {
            $query=$this->db->getQuery(true)
                ->select([$this->db->quoteName('s.id'),$this->db->quoteName('s.alias')])
                ->from($this->db->quoteName('#__minimarket_seo_filters','s'))
                ->join('INNER',$this->db->quoteName('#__minimarket_categories','c')
                    .' ON c.id=s.category_id')
                ->where('s.id='.(int)$id)
                ->where('s.state=1')
                ->where('c.state=1');
        } elseif ($view === 'category') {
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
