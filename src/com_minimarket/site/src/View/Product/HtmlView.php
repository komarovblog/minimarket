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
        // Gallery behavior is progressive-enhancement: images stay clickable
        // even if JS is blocked or the currently active template changes.
        $this->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.gallery', 'media/com_minimarket/js/gallery.js',
                [], ['defer' => true])
            ->registerAndUseStyle('com_minimarket.gallery', 'media/com_minimarket/css/gallery.css');
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
