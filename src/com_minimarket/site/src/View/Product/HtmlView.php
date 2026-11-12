<?php
namespace MiniMarket\Component\Minimarket\Site\View\Product;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\SeoHelper;
use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;
use MiniMarket\Component\Minimarket\Site\Service\ProductRecommendations;

final class HtmlView extends BaseHtmlView
{
    public object $product;
    public array $gallery = [];
    public array $reviews = [];
    public bool $reviewsEnabled = false;
    public bool $reviewsCanSubmit = false;
    public array $videos = [];
    public array $attributes = [];
    public array $variants = ['axes'=>[], 'items'=>[]];
    public bool $inventoryEnabled = true;
    public array $relatedGroups = [];
    public array $collections = [];
    public array $bundleComponents = [];
    public array $alternatives = [];
    public bool $outOfStock = false;
    public string $pageLayout = 'full';
    public bool $showSectionNav = false;
    public bool $showStickyBuyBar = false;
    public string $galleryEffect = 'lightbox';
    public array $similarProducts = [];
    public array $boughtTogetherProducts = [];

    public function display($tpl = null)
    {
        $id = Factory::getApplication()->input->getInt('id');

        if ($id < 1) {
            throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'), 404);
        }

        $repo = new CatalogRepository(Factory::getContainer()->get(DatabaseInterface::class));
        $product = $repo->getProduct($id);

        if ($product === null) {
            throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'), 404);
        }

        $this->inventoryEnabled = (int)ComponentHelper::getParams('com_minimarket')
            ->get('inventory_enabled', 1) === 1;
        $this->product = $product;
        $params=ComponentHelper::getParams('com_minimarket');
        $requestedLayout=(string)$params->get('product_detail_layout','full');
        $this->pageLayout=in_array($requestedLayout,['full','tabs'],true)?$requestedLayout:'full';
        $this->showSectionNav=(int)$params->get('product_detail_section_nav',0)===1;
        $this->showStickyBuyBar=(int)$params->get('product_detail_sticky_bar',0)===1;
        $effect=(string)$params->get('product_detail_gallery_effect','lightbox');
        $this->galleryEffect=in_array($effect,['lightbox','zoom'],true)?$effect:'lightbox';
        if((int)$params->get('product_detail_show_similar',0)===1){
            $this->similarProducts=ProductRecommendations::similar(
                $repo,$id,(int)$product->category_id,4);
        }
        if((int)$params->get('product_detail_show_bought_together',0)===1){
            $this->boughtTogetherProducts=ProductRecommendations::boughtTogether(
                Factory::getContainer()->get(DatabaseInterface::class),$repo,$id,4);
        }
        $this->reviewsEnabled=(int)$params->get('reviews_enabled',0)===1;
        if ($this->reviewsEnabled) {
            $user=Factory::getApplication()->getIdentity();
            $this->reviewsCanSubmit=(string)$params->get('reviews_author_mode','registered')==='all'
                || ((int)$user->id>0 && empty($user->block));
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $q=$db->getQuery(true)->select(['id','author_name','rating','title',
                'body','photo_path','video_url','created_at'])
                ->from($db->quoteName('#__minimarket_reviews'))
                ->where('product_id='.(int)$id)->where('state=1')
                ->order('id DESC');
            $db->setQuery($q,0,30);
            $this->reviews=(array)$db->loadObjectList();
        }
        $this->gallery = $repo->getProductGallery($id);
        $this->videos = $repo->getProductVideos($id);
        $this->attributes = $repo->getProductAttributes($id, (int) $product->category_id);
        $this->variants = $repo->getProductVariants($id, (int) $product->category_id);
        // All sellable combinations share the product's single price.
        foreach ($this->variants['items'] as &$variant) {
            $variant['price'] = (string) $product->price;
        }
        unset($variant);
        $this->relatedGroups = $repo->getRelatedGroups($id);
        if ($this->inventoryEnabled && (string)($product->delivery_type??'physical')==='physical') {
            $baseStock = (int)($product->stock??0);
            $variantStock = $this->variants['items'] !== []
                && (int)($product->option_stock_mode??1) !== 0;
            $sellable = $variantStock
                ? count(array_filter($this->variants['items'],
                    static fn($item):bool=>(int)($item['stock']??0)>0))>0
                : $baseStock>0;
            $this->outOfStock = !$sellable;
            if ($this->outOfStock) {
                // A direct SEO link remains available; offer published in-stock items.
                $similar = $repo->getProductsForCategory(
                    (int)$product->category_id,true,true,false,false);
                $candidates = array_merge($similar,
                    $repo->getAllProductsPage(0,24,'newest'));
                $seen = [];
                foreach ($candidates as $candidate) {
                    $pid=(int)$candidate->id;
                    if ($pid===$id || isset($seen[$pid])) continue;
                    $seen[$pid]=true;
                    $this->alternatives[]=$candidate;
                    if (count($this->alternatives)>=6) break;
                }
            }
        }
        $this->collections = $repo->getProductCollections($id);
        if (($product->product_type ?? 'simple') === 'bundle') {
            $this->bundleComponents = $repo->getBundleComponents($id);
            // Bundle does not have its own selectable SKU matrix.
            $this->variants = ['axes'=>[], 'items'=>[]];
        }
        // Color/size selectors for actual SKUs replace static descriptive rows.
        if ($this->variants['items']) {
            foreach (array_keys($this->variants['axes']) as $axisId) {
                unset($this->attributes[(int) $axisId]);
            }
        }
        // Gallery behavior is progressive-enhancement: images stay clickable
        // even if JS is blocked or the currently active template changes.
        $this->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.gallery', 'media/com_minimarket/js/gallery.js',
                [], ['defer' => true])
            ->registerAndUseStyle('com_minimarket.gallery', 'media/com_minimarket/css/gallery.css')
            ->registerAndUseStyle('com_minimarket.attributes', 'media/com_minimarket/css/attributes.css')
            ->registerAndUseStyle('com_minimarket.variants', 'media/com_minimarket/css/variants.css')
            ->registerAndUseStyle('com_minimarket.promo', 'media/com_minimarket/css/promo-video.css')
            ->registerAndUseStyle('com_minimarket.cart', 'media/com_minimarket/css/cart.css')
            ->registerAndUseScript('com_minimarket.promo', 'media/com_minimarket/js/promo-video.js', [], ['defer' => true])
            ->registerAndUseScript('com_minimarket.variants', 'media/com_minimarket/js/variants.js', [], ['defer' => true])
            ->registerAndUseStyle('com_minimarket.product-page','media/com_minimarket/css/product-page.css')
            ->registerAndUseScript('com_minimarket.product-page','media/com_minimarket/js/product-page.js',[],['defer'=>true]);
        $document = $this->getDocument();
        $metaTitle = trim((string) ($product->meta_title ?? ''));
        $document->setTitle($metaTitle !== '' ? $metaTitle : (string) $product->title);
        $description = trim((string) ($product->meta_description ?? ''));
        if ($description === '') {
            $source = (string) ($product->introtext ?: $product->description ?: '');
            $description = trim(preg_replace('/\s+/u', ' ', html_entity_decode(
                strip_tags($source), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            $description = mb_substr($description, 0, 160);
        }
        if ($description !== '') {
            $document->setDescription($description);
        }
        $robots = (string) ($product->meta_robots ?? '');
        if (in_array($robots, ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            $document->setMetaData('robots', $robots);
        }
        $document->setMetaData('og:type', 'product', 'property');
        $document->setMetaData('og:title',
            (string) ($product->og_title ?: $metaTitle ?: $product->title), 'property');
        $ogDescription = trim((string) ($product->og_description ?: $description));
        if ($ogDescription !== '') {
            $document->setMetaData('og:description', $ogDescription, 'property');
        }
        $ogImage = explode('#', (string) ($product->og_image ?: $product->image ?: ''), 2)[0];
        if (str_starts_with($ogImage, 'images/') && !str_contains($ogImage, '..')
            && !preg_match('/[\x00-\x1f<>]/', $ogImage)) {
            $document->setMetaData('og:image', Uri::root() . $ogImage, 'property');
            $document->setMetaData('og:image:alt',
                (string) ($product->og_image_alt ?: $product->image_alt ?: $product->title), 'property');
        }
        SeoHelper::setCanonical($document, 'product', $id);

        $this->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.badges',
                'media/com_minimarket/css/badges.css');

        parent::display($tpl);
    }
}
