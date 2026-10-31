<?php
namespace MiniMarket\Component\Minimarket\Site\View\Catalog;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public string $catalogTitle = 'MiniMarket';
    public array $categories = [];

    public function display($tpl = null)
    {
        $this->catalogTitle = (string) ComponentHelper::getParams('com_minimarket')->get('catalog_title', 'MiniMarket');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('title')])
            ->from($db->quoteName('#__minimarket_categories'))
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('id') . ' DESC');

        $db->setQuery($query);
        $this->categories = (array) $db->loadObjectList();

        parent::display($tpl);
    }
}
