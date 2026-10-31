<?php
namespace MiniMarket\Component\Minimarket\Site\View\Category;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Helper\SeoHelper;
use MiniMarket\Component\Minimarket\Site\Service\CatalogRepository;

final class HtmlView extends BaseHtmlView
{
    public object $category;
    public array $products = [];

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
        $this->products = $repo->getProductsForCategory($id);
        $this->getDocument()->setTitle((string) $category->title);
        SeoHelper::setCanonical($this->getDocument(), 'category', $id);

        parent::display($tpl);
    }
}
