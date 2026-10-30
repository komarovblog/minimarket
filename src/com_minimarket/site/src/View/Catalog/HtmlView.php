<?php
namespace MiniMarket\Component\Minimarket\Site\View\Catalog;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public string $catalogTitle = 'MiniMarket';

    public function display($tpl = null)
    {
        $this->catalogTitle = (string) ComponentHelper::getParams('com_minimarket')->get('catalog_title', 'MiniMarket');
        parent::display($tpl);
    }
}
