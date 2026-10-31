<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;

/**
 * Typed category/alias-ID and product/alias-ID routes based on exactly
 * one ordinary published Joomla Shop menu item (home or /shop).
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
        // One ordinary, published Shop menu item. Never use a foreign Itemid.
        $items = $this->getShopItems();

        if (\count($items) !== 1) {
            throw new \RuntimeException(
                'MiniMarket requires exactly one published Shop menu item; found ' . \count($items)
            );
        }

        $query['Itemid'] = (int) $items[0]->id;

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
        $items = $this->menu->getItems('component', 'com_minimarket') ?: [];
        $result = [];

        foreach ($items as $item) {
            if (($item->query['view'] ?? 'catalog') === 'catalog'
                && (int) ($item->published ?? 0) === 1) {
                $result[] = $item;
            }
        }

        return $result;
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
