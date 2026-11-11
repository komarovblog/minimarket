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
    public string $sort = 'order';
    public string $direction = 'asc';

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->state = $this->get('State');
        $this->pagination = $this->get('Pagination');
        $this->attributeId = (int) $this->state->get('filter.attribute_id', 0);
        ToolbarHelper::title(Text::_('COM_MINIMARKET_VALUES'));

        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_minimarket')) {
            ToolbarHelper::addNew('value.add');
        }

        $user = Factory::getApplication()->getIdentity();
        // Joomla's standard toolbar acts on selected cid[] checkboxes.
        if ($user->authorise('core.edit.state', 'com_minimarket')) {
            ToolbarHelper::publishList('values.publish');
            ToolbarHelper::unpublishList('values.unpublish');
        }
        if ($user->authorise('core.delete', 'com_minimarket')) {
            ToolbarHelper::deleteList(
                Text::_('COM_MINIMARKET_BULK_DELETE_CONFIRM'), 'values.delete'
            );
        }
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
