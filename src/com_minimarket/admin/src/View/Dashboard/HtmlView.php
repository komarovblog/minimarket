<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Dashboard;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public function display($tpl = null)
    {
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DASHBOARD_TITLE'));

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
