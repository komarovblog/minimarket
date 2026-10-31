<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;

/**
 * One flat alias-ID segment for each category or product.
 *
 * The Joomla menu item is the routing base. When catalog is the homepage,
 * the item URLs are /alias-ID; with a "catalog" menu item, /catalog/alias-ID.
 * Neither route contains a product category ancestry.
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
        if (!empty($query['Itemid'])) {
            return $query;
        }

        // Keep generated links on the MiniMarket catalog menu rather than
        // inheriting a menu item for an unrelated Joomla component.
        $items = $this->menu->getItems('component', 'com_minimarket') ?: [];

        foreach ($items as $item) {
            if (($item->query['view'] ?? '') === 'catalog' && (int) ($item->published ?? 1) === 1) {
                $query['Itemid'] = (int) $item->id;
                break;
            }
        }

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
        return [$record->alias . '-' . (int) $record->id];
    }

    public function parse(&$segments)
    {
        if (!$segments) {
            return ['view' => 'catalog'];
        }

        if (\count($segments) !== 1) {
            throw new \RuntimeException('MiniMarket route not found', 404);
        }

        $slug = (string) $segments[0];

        if (!preg_match('/^([a-z0-9][a-z0-9-]*)-([1-9][0-9]*)$/D', $slug, $matches)) {
            throw new \RuntimeException('MiniMarket route not found', 404);
        }

        $id = filter_var($matches[2], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new \RuntimeException('MiniMarket route not found', 404);
        }

        $matchesFound = [];

        foreach (['category', 'product'] as $view) {
            $record = $this->getPublished($view, (int) $id);

            if ($record !== null && $record->alias === $matches[1]) {
                $matchesFound[] = $view;
            }
        }

        // Never silently serve the wrong entity if legacy records collide.
        if (\count($matchesFound) !== 1) {
            throw new \RuntimeException('MiniMarket route not found or ambiguous', 404);
        }

        $segments = [];

        return ['view' => $matchesFound[0], 'id' => (int) $id];
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
