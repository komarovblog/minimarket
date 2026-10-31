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

    public function display($tpl = null)
    {
        $this->catalogTitle = (string) ComponentHelper::getParams('com_minimarket')->get('catalog_title', 'MiniMarket');
        $repo = new CatalogRepository(Factory::getContainer()->get(DatabaseInterface::class));
        $this->categories = $repo->getCategories();
        $this->getDocument()->setTitle($this->catalogTitle);
        SeoHelper::setCanonical($this->getDocument(), 'catalog');

        parent::display($tpl);
    }
}
