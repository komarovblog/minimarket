<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * All public reads exclude unpublished products and categories.
 */
final class CatalogRepository
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getCategories(): array
    {
        $q = $this->db->getQuery(true)
            ->select([$this->db->quoteName('id'), $this->db->quoteName('title'), $this->db->quoteName('alias')])
            ->from($this->db->quoteName('#__minimarket_categories'))
            ->where($this->db->quoteName('state') . ' = 1')
            ->order($this->db->quoteName('title') . ' ASC');
        $this->db->setQuery($q);

        return (array) $this->db->loadObjectList();
    }

    /**
     * Count only published products whose category is also published.
     * This source is deliberately shared by all menu contexts.
     */
    public function countAllProducts(): int
    {
        $q = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__minimarket_products', 'p'))
            ->join(
                'INNER',
                $this->db->quoteName('#__minimarket_categories', 'c')
                . ' ON ' . $this->db->quoteName('p.category_id')
                . ' = ' . $this->db->quoteName('c.id')
            )
            ->where($this->db->quoteName('p.state') . ' = 1')
            ->where($this->db->quoteName('c.state') . ' = 1');

        $this->db->setQuery($q);

        return (int) $this->db->loadResult();
    }

    /**
     * Page of real, published products; fixed allowlist prevents SQL injection
     * via a menu option or a future public sorting control.
     *
     * @return object[]
     */
    public function getAllProductsPage(int $offset, int $limit, string $order): array
    {
        $orders = [
            'newest' => $this->db->quoteName('p.id') . ' DESC',
            'name' => $this->db->quoteName('p.title') . ' ASC, ' . $this->db->quoteName('p.id') . ' ASC',
            'price_asc' => $this->db->quoteName('p.price') . ' ASC, ' . $this->db->quoteName('p.id') . ' ASC',
            'price_desc' => $this->db->quoteName('p.price') . ' DESC, ' . $this->db->quoteName('p.id') . ' ASC',
        ];

        $q = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('p.id'),
                $this->db->quoteName('p.title'),
                $this->db->quoteName('p.sku'),
                $this->db->quoteName('p.price'),
                $this->db->quoteName('p.stock'),
            ])
            ->from($this->db->quoteName('#__minimarket_products', 'p'))
            ->join(
                'INNER',
                $this->db->quoteName('#__minimarket_categories', 'c')
                . ' ON ' . $this->db->quoteName('p.category_id')
                . ' = ' . $this->db->quoteName('c.id')
            )
            ->where($this->db->quoteName('p.state') . ' = 1')
            ->where($this->db->quoteName('c.state') . ' = 1')
            ->order($orders[$order] ?? $orders['newest']);

        $this->db->setQuery($q, max(0, $offset), max(1, min($limit, 48)));

        return (array) $this->db->loadObjectList();
    }

    public function getCategory(int $id): ?object
    {
        $q = $this->db->getQuery(true)
            ->select([$this->db->quoteName('id'), $this->db->quoteName('title'), $this->db->quoteName('description')])
            ->from($this->db->quoteName('#__minimarket_categories'))
            ->where($this->db->quoteName('id') . ' = ' . $id)
            ->where($this->db->quoteName('state') . ' = 1');
        $this->db->setQuery($q);

        return $this->db->loadObject() ?: null;
    }

    public function getProductsForCategory(int $id): array
    {
        $q = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('p.id'),
                $this->db->quoteName('p.title'),
                $this->db->quoteName('p.sku'),
                $this->db->quoteName('p.price'),
                $this->db->quoteName('p.stock'),
            ])
            ->from($this->db->quoteName('#__minimarket_products', 'p'))
            ->join(
                'INNER',
                $this->db->quoteName('#__minimarket_categories', 'c')
                . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('p.category_id')
            )
            ->where($this->db->quoteName('p.category_id') . ' = ' . $id)
            ->where($this->db->quoteName('p.state') . ' = 1')
            ->where($this->db->quoteName('c.state') . ' = 1')
            ->order($this->db->quoteName('p.id') . ' DESC');

        $this->db->setQuery($q);

        return (array) $this->db->loadObjectList();
    }

    public function getProduct(int $id): ?object
    {
        $q = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('p.id'),
                $this->db->quoteName('p.title'),
                $this->db->quoteName('p.alias'),
                $this->db->quoteName('p.sku'),
                $this->db->quoteName('p.price'),
                $this->db->quoteName('p.stock'),
                $this->db->quoteName('p.category_id'),
                $this->db->quoteName('c.title', 'category_title'),
            ])
            ->from($this->db->quoteName('#__minimarket_products', 'p'))
            ->join(
                'INNER',
                $this->db->quoteName('#__minimarket_categories', 'c')
                . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('p.category_id')
            )
            ->where($this->db->quoteName('p.id') . ' = ' . $id)
            ->where($this->db->quoteName('p.state') . ' = 1')
            ->where($this->db->quoteName('c.state') . ' = 1');

        $this->db->setQuery($q);

        return $this->db->loadObject() ?: null;
    }
}
