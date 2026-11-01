<?php
namespace MiniMarket\Component\Minimarket\Site\View\Category;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
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
        $this->getDocument()->setTitle((string) $category->title);
        SeoHelper::setCanonical($this->getDocument(), 'category', $id);

        parent::display($tpl);
    }
}
