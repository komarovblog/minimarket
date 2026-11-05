<?php
namespace MiniMarket\Component\Minimarket\Site\View\Product;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\SeoHelper;
use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;

final class HtmlView extends BaseHtmlView
{
    public object $product;
    public array $gallery = [];
    public array $videos = [];
    public array $attributes = [];
    public array $variants = ['axes'=>[], 'items'=>[]];
    public array $relatedGroups = [];
    public array $collections = [];
    public array $bundleComponents = [];

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

        $this->product = $product;
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
            ->registerAndUseScript('com_minimarket.variants', 'media/com_minimarket/js/variants.js', [], ['defer' => true]);
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

        parent::display($tpl);
    }
}
