<?php
namespace MiniMarket\Component\Minimarket\Site\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\BundleStock;
use MiniMarket\Component\Minimarket\Administrator\Service\BundlePrice;

/**
 * All public reads exclude unpublished products and categories.
 */
final class CatalogRepository
{
    private ?CategoryTree $categoryTree = null;
    private ?array $bundleSnapshot = null;
    private ?array $bundlePriceSnapshot = null;
    /** @var object[]|null Published, in-stock catalog entries cached per request. */
    private ?array $listingCache = null;
    private ?bool $inventoryEnabled = null;

    private function inventoryEnabled(): bool
    {
        if ($this->inventoryEnabled === null) {
            $this->inventoryEnabled = !class_exists(\Joomla\CMS\Component\ComponentHelper::class)
                || (int)\Joomla\CMS\Component\ComponentHelper::getParams('com_minimarket')
                    ->get('inventory_enabled',1) === 1;
        }
        return $this->inventoryEnabled;
    }

    /** A product can be sold only if its relevant SKU stock has units left. */
    private function availabilitySql(): string
    {
        if (!$this->inventoryEnabled()) return '1=1';
        $variants = $this->db->quoteName('#__minimarket_product_variants');
        return "(p.product_type = 'bundle' OR (p.product_type = 'simple' AND ("
            . "(p.option_stock_mode = 0 AND p.stock > 0)"
            . " OR (p.option_stock_mode <> 0 AND ("
            . "EXISTS (SELECT 1 FROM ".$variants." AS mmv"
            . " WHERE mmv.product_id=p.id AND mmv.state=1 AND mmv.stock>0)"
            . " OR (p.stock>0 AND NOT EXISTS (SELECT 1 FROM ".$variants." AS mmv2"
            . " WHERE mmv2.product_id=p.id))"
            . ")))))";
    }

    /** Physical bundle stock is derived from its real components, not p.stock. */
    private function filterListingRows(array $rows): array
    {
        if (!$this->inventoryEnabled()) return $rows;
        return array_values(array_filter($rows,static fn($p):bool=>
            !isset($p->stock) || (int)$p->stock > 0));
    }


    private function bundlePriceSnapshot(): array
    {
        return $this->bundlePriceSnapshot ??= BundlePrice::snapshot($this->db);
    }

    private function bundleSnapshot(): array
    {
        return $this->bundleSnapshot ??= BundleStock::snapshot($this->db);
    }

    private function derivedStock(array $products): array
    {
        foreach ($products as $product) {
            if (($product->product_type ?? 'simple') === 'bundle') {
                $product->stock = BundleStock::available((int)$product->id,
                    $this->bundleSnapshot());
                $quote=BundlePrice::calculate((int)$product->id,
                    $this->bundlePriceSnapshot());
                $product->price=$quote['price'];
                $product->bundle_discount_amount=$quote['discount_amount'];
            }
        }
        return $products;
    }

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
     * Load published candidates once, filter real bundle availability before
     * pagination, and let SEO product URLs continue resolving independently.
     * This prevents empty pages caused by filtering only after LIMIT/OFFSET.
     */
    private function catalogListing(): array
    {
        if ($this->listingCache !== null) return $this->listingCache;
        $visibleIds = $this->visibleCategoryIdsSql();
        if ($visibleIds === '') return $this->listingCache = [];
        $fields=['id','title','sku','price','old_price','badge_sale',
            'badge_benefit','badge_sale_display','badge_benefit_display',
            'bundle_price_mode','bundle_discount_pct','badge_new','badge_new_days',
            'badge_new_since','badge_popular','badge_popular_days',
            'badge_popular_since','stock','product_type','option_stock_mode',
            'category_id','image','image_alt'];
        $q=$this->db->getQuery(true)
            ->select(array_map(fn($field)=>$this->db->quoteName('p.'.$field),$fields))
            ->from($this->db->quoteName('#__minimarket_products','p'))
            ->join('INNER',$this->db->quoteName('#__minimarket_categories','c')
                .' ON p.category_id=c.id')
            ->where('p.state = 1')->where('c.state = 1')
            ->where('p.category_id IN ('.$visibleIds.')')
            ->where($this->availabilitySql())
            ->order('p.id DESC');
        $this->db->setQuery($q);
        return $this->listingCache = $this->filterListingRows(
            $this->derivedStock((array)$this->db->loadObjectList()));
    }

    public function countAllProducts(): int
    {
        return count($this->catalogListing());
    }

    /** All item quantities and bundle discounts are resolved before sorting. */
    public function getAllProductsPage(int $offset,int $limit,string $order): array
    {
        $rows=$this->catalogListing();
        switch ($order) {
            case 'name':
                usort($rows,static fn($a,$b):int=>
                    strnatcasecmp((string)$a->title,(string)$b->title)
                    ?:((int)$a->id<=>(int)$b->id));break;
            case 'price_asc':
                usort($rows,static fn($a,$b):int=>
                    ((float)$a->price<=>(float)$b->price)
                    ?:((int)$a->id<=>(int)$b->id));break;
            case 'price_desc':
                usort($rows,static fn($a,$b):int=>
                    ((float)$b->price<=>(float)$a->price)
                    ?:((int)$a->id<=>(int)$b->id));break;
            default:
                usort($rows,static fn($a,$b):int=>(int)$b->id<=>(int)$a->id);
        }
        return array_slice($rows,max(0,$offset),max(1,min(48,$limit)));
    }

    public function getCategory(int $id): ?object
    {
        if (!$this->categoryTree()->isVisible($id)) {
            return null;
        }

        $q = $this->db->getQuery(true)
            ->select(array_map([$this->db, 'quoteName'], ['id', 'title', 'introtext', 'description', 'image', 'image_alt', 'show_children', 'show_products', 'show_bundles', 'show_descendant_products', 'show_descendant_bundles', 'product_layout', 'cards_xs', 'cards_sm', 'cards_md', 'cards_lg', 'cards_xl', 'cards_xxl', 'meta_title', 'meta_description',
                'meta_robots', 'og_title', 'og_description', 'og_image', 'og_image_alt']))
            ->from($this->db->quoteName('#__minimarket_categories'))
            ->where($this->db->quoteName('id') . ' = ' . $id)
            ->where($this->db->quoteName('state') . ' = 1');
        $this->db->setQuery($q);

        return $this->db->loadObject() ?: null;
    }

    public function getProductsForCategory(
        int $id, bool $showProducts = true, bool $showBundles = true,
        bool $descendantProducts = false, bool $descendantBundles = false
    ): array
    {
        if (!$this->categoryTree()->isVisible($id)) {
            return [];
        }

        $descendantIds = ($descendantProducts || $descendantBundles)
            ? $this->categoryTree()->descendantIds($id) : [];
        $typeSql = static function (bool $simple, bool $bundles): string {
            if ($simple && $bundles) return "p.product_type IN ('simple','bundle')";
            return $simple ? "p.product_type = 'simple'" : "p.product_type = 'bundle'";
        };
        $conditions = [];
        if ($showProducts || $showBundles) {
            $conditions[] = '(p.category_id = ' . $id . ' AND '
                . $typeSql($showProducts, $showBundles) . ')';
        }
        if ($descendantIds !== []) {
            $conditions[] = '(p.category_id IN (' . implode(',', $descendantIds) . ') AND '
                . $typeSql($descendantProducts, $descendantBundles) . ')';
        }
        if ($conditions === []) return [];

        $q = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('p.id'),
                $this->db->quoteName('p.title'),
                $this->db->quoteName('p.sku'),
                $this->db->quoteName('p.price'),
                $this->db->quoteName('p.old_price'),
                $this->db->quoteName('p.badge_sale'),
                $this->db->quoteName('p.badge_benefit'),
                $this->db->quoteName('p.badge_sale_display'),
                $this->db->quoteName('p.badge_benefit_display'),
                $this->db->quoteName('p.bundle_price_mode'),
                $this->db->quoteName('p.bundle_discount_pct'),
                $this->db->quoteName('p.badge_new'),
                $this->db->quoteName('p.badge_new_days'),
                $this->db->quoteName('p.badge_new_since'),
                $this->db->quoteName('p.badge_popular'),
                $this->db->quoteName('p.badge_popular_days'),
                $this->db->quoteName('p.badge_popular_since'),
                $this->db->quoteName('p.stock'),
                $this->db->quoteName('p.category_id'),
                $this->db->quoteName('p.option_stock_mode'),
                $this->db->quoteName('p.product_type'),
                $this->db->quoteName('p.image'),
                $this->db->quoteName('p.image_alt'),
            ])
            ->from($this->db->quoteName('#__minimarket_products', 'p'))
            ->join(
                'INNER',
                $this->db->quoteName('#__minimarket_categories', 'c')
                . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('p.category_id')
            )
            ->where('(' . implode(' OR ', $conditions) . ')')
            ->where($this->db->quoteName('p.state') . ' = 1')
            ->where($this->db->quoteName('c.state') . ' = 1')
            ->where($this->availabilitySql())
            ->order($this->db->quoteName('p.id') . ' DESC');

        $this->db->setQuery($q);

        return $this->filterListingRows(
            $this->derivedStock((array) $this->db->loadObjectList()));
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
            ->select(['title','source','poster','is_promo'])
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

    public function getProductVariants(int $productId, int $categoryId): array
    {
        return VariantCatalog::load($this->db, $productId,
            $this->categoryTree()->ancestorIds($categoryId));
    }

    /** Each published group is shown with its own set of published member products. */
    public function getRelatedGroups(int $productId): array
    {
        if ($productId < 1) return [];
        $q = $this->db->getQuery(true)->select(['g.id','g.title','g.type'])
            ->from($this->db->quoteName('#__minimarket_product_group_members', 'm'))
            ->join('INNER', $this->db->quoteName('#__minimarket_product_groups', 'g')
                . ' ON g.id = m.group_id')
            ->where('m.product_id = ' . $productId)
            ->where('g.state = 1')
            ->order('g.sort_order ASC, g.title ASC, g.id ASC');
        $this->db->setQuery($q);
        $groups = (array) $this->db->loadAssocList();
        if (!$groups) return [];
        $ids = array_map('intval', array_column($groups, 'id'));
        $q = $this->db->getQuery(true)->select([
                'm.group_id','p.id','p.title','p.category_id','p.image','p.image_alt'
            ])
            ->from($this->db->quoteName('#__minimarket_product_group_members', 'm'))
            ->join('INNER', $this->db->quoteName('#__minimarket_products', 'p')
                . ' ON p.id = m.product_id')
            ->where('m.group_id IN (' . implode(',', $ids) . ')')
            ->where('p.id <> ' . $productId)
            ->where('p.state = 1')
            ->order('m.sort_order ASC, p.id ASC');
        $this->db->setQuery($q);
        $members = [];
        foreach ((array) $this->db->loadObjectList() as $product) {
            if ($this->categoryTree()->isVisible((int) $product->category_id)) {
                $members[(int) $product->group_id][] = $product;
            }
        }
        $out = [];
        foreach ($groups as $group) {
            $groupId = (int) $group['id'];
            if (!empty($members[$groupId])) {
                $out[] = ['id' => $groupId, 'title' => $group['title'],
                    'type' => $group['type'], 'products' => $members[$groupId]];
            }
        }
        return $out;
    }

    /** Only published collections are visible publicly; unpublished values remain saved. */
    public function getProductCollections(int $productId): array
    {
        $q=$this->db->getQuery(true)->select(['c.id','c.title'])
            ->from($this->db->quoteName('#__minimarket_product_collections','pc'))
            ->join('INNER', $this->db->quoteName('#__minimarket_collections','c')
                . ' ON c.id=pc.collection_id')
            ->where('pc.product_id=' . $productId)
            ->where('c.state=1')
            ->order('pc.sort_order ASC,c.id ASC');
        $this->db->setQuery($q);
        return (array)$this->db->loadObjectList();
    }

    /** Bundle composition is read-only until cart/checkout is implemented. */
    public function getBundleComponents(int $id): array
    {
        $items=BundleStock::components($this->db,$id);
        return array_values(array_filter($items,fn($row) => (int)$row['state']===1
            && $this->categoryTree()->isVisible((int)$row['category_id'])));
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
                $this->db->quoteName('p.old_price'),
                $this->db->quoteName('p.badge_sale'),
                $this->db->quoteName('p.badge_benefit'),
                $this->db->quoteName('p.badge_sale_display'),
                $this->db->quoteName('p.badge_benefit_display'),
                $this->db->quoteName('p.bundle_price_mode'),
                $this->db->quoteName('p.bundle_discount_pct'),
                $this->db->quoteName('p.badge_new'),
                $this->db->quoteName('p.badge_new_days'),
                $this->db->quoteName('p.badge_new_since'),
                $this->db->quoteName('p.badge_popular'),
                $this->db->quoteName('p.badge_popular_days'),
                $this->db->quoteName('p.badge_popular_since'),
                $this->db->quoteName('p.stock'),
                $this->db->quoteName('p.option_stock_mode'),
                $this->db->quoteName('p.delivery_type'),
                $this->db->quoteName('p.product_type'),
                $this->db->quoteName('p.image'),
                $this->db->quoteName('p.image_alt'),
                $this->db->quoteName('p.category_id'),
                $this->db->quoteName('c.title', 'category_title'),
                $this->db->quoteName('b.title', 'brand_title'),
            ])
            ->from($this->db->quoteName('#__minimarket_products', 'p'))
            ->join(
                'INNER',
                $this->db->quoteName('#__minimarket_categories', 'c')
                . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('p.category_id')
            )
            ->join('LEFT', $this->db->quoteName('#__minimarket_brands','b')
                . ' ON b.id=p.brand_id AND b.state=1')
            ->where($this->db->quoteName('p.id') . ' = ' . $id)
            ->where($this->db->quoteName('p.state') . ' = 1')
            ->where($this->db->quoteName('c.state') . ' = 1');

        $this->db->setQuery($q);

        $product = $this->db->loadObject();

        if ($product === null || !$this->categoryTree()->isVisible((int)$product->category_id)) {
            return null;
        }
        if (($product->product_type ?? 'simple') === 'bundle') {
            $product->stock=BundleStock::available((int)$product->id,$this->bundleSnapshot());
            $quote=BundlePrice::calculate((int)$product->id,
                $this->bundlePriceSnapshot());
            $product->price=$quote['price'];
            $product->bundle_discount_amount=$quote['discount_amount'];
        }
        return $product;
    }
}
