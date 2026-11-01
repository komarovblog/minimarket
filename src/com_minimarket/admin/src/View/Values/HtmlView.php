<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Values;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public int $attributeId = 0;

    public function display($tpl = null)
    {
        $this->attributeId = Factory::getApplication()->input->getInt('attribute_id', 0);
        $this->items = $this->get('Items');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_VALUES'));

        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_minimarket')) {
            ToolbarHelper::addNew('value.add');
        }

        $user = Factory::getApplication()->getIdentity();
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
