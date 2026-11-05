<?php
namespace MiniMarket\Component\Minimarket\Site\View\Category;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\SeoHelper;
use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;

final class HtmlView extends BaseHtmlView
{
    public object $category;
    public array $products = [];
    public array $children = [];
    public bool $displayProducts = true;

    public function display($tpl = null)
    {
        $id = Factory::getApplication()->input->getInt('id');

        if ($id < 1) {
            throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'), 404);
        }

        $repo = new CatalogRepository(Factory::getContainer()->get(DatabaseInterface::class));
        $category = $repo->getCategory($id);

        if ($category === null) {
            throw new \RuntimeException(Text::_('JERROR_LAYOUT_PAGE_NOT_FOUND'), 404);
        }

        $this->category = $category;
        $options = ComponentHelper::getParams('com_minimarket');
        $children = (int) ($category->show_children ?? -1);
        $products = (int) ($category->show_products ?? -1);
        $this->children = ($children === 1 || ($children === -1 && (int) $options->get('category_show_children', 1) === 1))
            ? $repo->getChildCategories($id) : [];
        $this->displayProducts = ($products === 1
            || ($products === -1 && (int) $options->get('category_show_products', 1) === 1));
        $this->products = $this->displayProducts ? $repo->getProductsForCategory($id) : [];
        $document = $this->getDocument();
        $metaTitle = trim((string) ($category->meta_title ?? ''));
        $document->setTitle($metaTitle !== '' ? $metaTitle : (string) $category->title);
        $metaDescription = trim((string) ($category->meta_description ?? ''));
        if ($metaDescription === '') {
            $source = (string) ($category->introtext ?: $category->description ?: '');
            $metaDescription = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($source), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
            $metaDescription = mb_substr($metaDescription, 0, 160);
        }
        if ($metaDescription !== '') {
            $document->setDescription($metaDescription);
        }
        $robots = (string) ($category->meta_robots ?? '');
        if (in_array($robots, ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            $document->setMetaData('robots', $robots);
        }
        $document->setMetaData('og:type', 'website', 'property');
        $document->setMetaData('og:title',
            (string) ($category->og_title ?: $metaTitle ?: $category->title), 'property');
        $ogDescription = trim((string) ($category->og_description ?: $metaDescription));
        if ($ogDescription !== '') {
            $document->setMetaData('og:description', $ogDescription, 'property');
        }
        $ogImage = explode('#', (string) ($category->og_image ?: $category->image ?: ''), 2)[0];
        if (str_starts_with($ogImage, 'images/')
            && !str_contains($ogImage, '..')
            && !preg_match('/[\x00-\x1f<>]/', $ogImage)) {
            $document->setMetaData('og:image', Uri::root() . $ogImage, 'property');
            $document->setMetaData('og:image:alt',
                (string) ($category->og_image_alt ?: $category->image_alt ?: $category->title), 'property');
        }
        SeoHelper::setCanonical($document, 'category', $id);

        $this->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.badges',
                'media/com_minimarket/css/badges.css');

        parent::display($tpl);
    }
}
