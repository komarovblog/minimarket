<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Attributes;

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
        ToolbarHelper::title(Text::_('COM_MINIMARKET_ATTRIBUTES'));

        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_minimarket')) {
            ToolbarHelper::addNew('attribute.add');
        }

        $user = Factory::getApplication()->getIdentity();
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
