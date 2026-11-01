<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Categories;

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

        ToolbarHelper::title(Text::_('COM_MINIMARKET_CATEGORIES'));

        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_minimarket')) {
            ToolbarHelper::addNew('category.add');
        }

        // Joomla component Options are not a separate MiniMarket MVC view.
        // Show the standard Options action on the pages admins use most.
        $user = Factory::getApplication()->getIdentity();
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
