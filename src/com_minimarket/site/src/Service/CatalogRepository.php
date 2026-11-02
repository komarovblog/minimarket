<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * All public reads exclude unpublished products and categories.
 */
final class CatalogRepository
{
    private ?CategoryTree $categoryTree = null;

    public function __construct(private DatabaseInterface $db)
    {
    }

    /**
     * A single category read per request powers navigation and guarantees
     * published ancestors throughout product/category storefront queries.
     */
    private function categoryTree(): CategoryTree
    {
        if ($this->categoryTree === null) {
            $q = $this->db->getQuery(true)
                ->select([
                    $this->db->quoteName('id'),
                    $this->db->quoteName('parent_id'),
                    $this->db->quoteName('title'),
                    $this->db->quoteName('alias'),
                    $this->db->quoteName('state'),
                    $this->db->quoteName('sort_order'),
                ])
                ->from($this->db->quoteName('#__minimarket_categories'));
            $this->db->setQuery($q);
            $this->categoryTree = new CategoryTree((array) $this->db->loadObjectList());
        }

        return $this->categoryTree;
    }

    public function getCategories(): array
    {
        // Only published root categories appear at storefront root.
        return $this->categoryTree()->children(0);
    }

    public function getChildCategories(int $parentId): array
    {
        return $this->categoryTree()->children($parentId);
    }

    private function visibleCategoryIdsSql(): string
    {
        return implode(',', $this->categoryTree()->visibleIds());
    }

    /**
     * Count only published products whose category is also published.
     * This source is deliberately shared by all menu contexts.
     */
    public function countAllProducts(): int
    {
        $visibleIds = $this->visibleCategoryIdsSql();

        if ($visibleIds === '') {
            return 0;
        }

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
            ->where($this->db->quoteName('c.state') . ' = 1')
            ->where($this->db->quoteName('p.category_id') . ' IN (' . $visibleIds . ')');

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
        $visibleIds = $this->visibleCategoryIdsSql();

        if ($visibleIds === '') {
            return [];
        }

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
                $this->db->quoteName('p.image'),
                $this->db->quoteName('p.image_alt'),
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
            ->where($this->db->quoteName('p.category_id') . ' IN (' . $visibleIds . ')')
            ->order($orders[$order] ?? $orders['newest']);

        $this->db->setQuery($q, max(0, $offset), max(1, min($limit, 48)));

        return (array) $this->db->loadObjectList();
    }

    public function getCategory(int $id): ?object
    {
        if (!$this->categoryTree()->isVisible($id)) {
            return null;
        }

        $q = $this->db->getQuery(true)
            ->select(array_map([$this->db, 'quoteName'], ['id', 'title', 'introtext', 'description', 'image', 'image_alt', 'show_children', 'show_products', 'meta_title', 'meta_description',
                'meta_robots', 'og_title', 'og_description', 'og_image', 'og_image_alt']))
            ->from($this->db->quoteName('#__minimarket_categories'))
            ->where($this->db->quoteName('id') . ' = ' . $id)
            ->where($this->db->quoteName('state') . ' = 1');
        $this->db->setQuery($q);

        return $this->db->loadObject() ?: null;
    }

    public function getProductsForCategory(int $id): array
    {
        if (!$this->categoryTree()->isVisible($id)) {
            return [];
        }

        $q = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('p.id'),
                $this->db->quoteName('p.title'),
                $this->db->quoteName('p.sku'),
                $this->db->quoteName('p.price'),
                $this->db->quoteName('p.stock'),
                $this->db->quoteName('p.image'),
                $this->db->quoteName('p.image_alt'),
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

    /**
     * One query for the currently opened product, not one per image or SKU.
     * File paths come from Joomla Media; this does not read binary image data.
     *
     * @return object[]
     */
    public function getProductGallery(int $productId): array
    {
        if ($productId < 1) {
            return [];
        }
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('path'),
                $this->db->quoteName('alt'),
                $this->db->quoteName('sort_order'),
            ])
            ->from($this->db->quoteName('#__minimarket_product_images'))
            ->where($this->db->quoteName('product_id') . ' = ' . $productId)
            ->order($this->db->quoteName('sort_order') . ' ASC, '
                . $this->db->quoteName('id') . ' ASC');
        $this->db->setQuery($query);

        return (array) $this->db->loadObjectList();
    }

    /** Ordered media references for an already-published product. */
    public function getProductVideos(int $id): array
    {
        if ($id < 1) return [];
        $q = $this->db->getQuery(true)
            ->select(['title','source','poster'])
            ->from($this->db->quoteName('#__minimarket_product_videos'))
            ->where($this->db->quoteName('product_id') . ' = ' . $id)
            ->order('sort_order ASC, id ASC');
        $this->db->setQuery($q);
        return (array) $this->db->loadObjectList();
    }

    /**
     * Published attributes only, with category rule inheritance.
     * Called after getProduct() has confirmed visible product and category.
     */
    public function getProductAttributes(int $productId, int $categoryId): array
    {
        if ($productId < 1) return [];
        return ProductAttributeCatalog::load(
            $this->db, $productId, $this->categoryTree()->ancestorIds($categoryId)
        );
    }

    public function getProduct(int $id): ?object
    {
        $q = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('p.id'),
                $this->db->quoteName('p.title'),
                $this->db->quoteName('p.alias'),
                $this->db->quoteName('p.introtext'),
                $this->db->quoteName('p.description'),
                $this->db->quoteName('p.meta_title'),
                $this->db->quoteName('p.meta_description'),
                $this->db->quoteName('p.meta_robots'),
                $this->db->quoteName('p.og_title'),
                $this->db->quoteName('p.og_description'),
                $this->db->quoteName('p.og_image'),
                $this->db->quoteName('p.og_image_alt'),
                $this->db->quoteName('p.sku'),
                $this->db->quoteName('p.price'),
                $this->db->quoteName('p.stock'),
                $this->db->quoteName('p.image'),
                $this->db->quoteName('p.image_alt'),
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

        $product = $this->db->loadObject();

        return $product !== null && $this->categoryTree()->isVisible((int) $product->category_id)
            ? $product
            : null;
    }
}
