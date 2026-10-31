<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Products;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');

        ToolbarHelper::title(Text::_('COM_MINIMARKET_PRODUCTS'));

        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_minimarket')) {
            ToolbarHelper::addNew('product.add');
        }

        parent::display($tpl);
    }
}
