<?php
namespace MiniMarket\Component\Minimarket\Site\View\Catalog;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\SeoHelper;
use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;

final class HtmlView extends BaseHtmlView
{
    public string $catalogTitle = 'MiniMarket';
    public array $categories = [];
    public bool $showCategories = true;
    public bool $showProducts = false;
    public array $products = [];
    public int $productPage = 1;
    public int $productsPerPage = 12;
    public int $productPageCount = 1;
    public int $productTotal = 0;
    public int $shopItemId = 0;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->catalogTitle = (string) ComponentHelper::getParams('com_minimarket')
            ->get('catalog_title', 'MiniMarket');

        // Parameters belong to the actual ordinary menu item that was opened.
        // This allows '/', '/catalog' and '/shop' to show different blocks.
        $active = $app->getMenu()->getActive();
        $params = null;

        if ($active !== null
            && ($active->component ?? '') === 'com_minimarket'
            && ($active->query['view'] ?? 'catalog') === 'catalog') {
            $this->shopItemId = (int) $active->id;
            $params = $active->getParams();
        }

        $this->showCategories = (int) ($params?->get('show_categories', 1) ?? 1) === 1;
        $this->showProducts = (int) ($params?->get('show_products', 0) ?? 0) === 1;

        if ($this->showCategories || $this->showProducts) {
            $repo = new CatalogRepository(Factory::getContainer()->get(DatabaseInterface::class));

            if ($this->showCategories) {
                $this->categories = $repo->getCategories();
            }

            if ($this->showProducts) {
                $allowedLimits = [12, 24, 48];
                $limit = (int) ($params?->get('products_per_page', 12) ?? 12);
                $this->productsPerPage = \in_array($limit, $allowedLimits, true) ? $limit : 12;

                $sort = (string) ($params?->get('products_order', 'newest') ?? 'newest');

                $this->productTotal = $repo->countAllProducts();
                $this->productPageCount = max(1, (int) ceil($this->productTotal / $this->productsPerPage));
                $requestedPage = max(1, $app->input->getInt('mm_page', 1));
                $this->productPage = min($requestedPage, $this->productPageCount);

                $this->products = $repo->getAllProductsPage(
                    ($this->productPage - 1) * $this->productsPerPage,
                    $this->productsPerPage,
                    $sort
                );
            }
        }

        $this->getDocument()->setTitle($this->catalogTitle);
        SeoHelper::setCanonical(
            $this->getDocument(),
            'catalog',
            0,
            $this->showProducts ? $this->productPage : 1
        );

        $this->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.badges',
                'media/com_minimarket/css/badges.css');

        parent::display($tpl);
    }
}
