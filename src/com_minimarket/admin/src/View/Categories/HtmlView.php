<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Categories;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public array $rows = [];
    public string $search = '';
    public string $stateFilter = 'all';

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->state = $this->get('State');
        $this->search = trim((string) $this->state->get('filter.search', ''));
        $requestedState = (string) $this->state->get('filter.published', '');
        $this->stateFilter = in_array($requestedState, ['0','1'], true)
            ? $requestedState : 'all';
        $this->rows = CategoryAdminTree::flatten(
            $this->items,
            $this->get('ProductCounts'),
            $this->search,
            $this->stateFilter === 'all' ? null : (int) $this->stateFilter
        );

        ToolbarHelper::title(Text::_('COM_MINIMARKET_CATEGORIES'));

        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_minimarket')) {
            ToolbarHelper::addNew('category.add');
        }

        // Joomla component Options are not a separate MiniMarket MVC view.
        // Show the standard Options action on the pages admins use most.
        $user = Factory::getApplication()->getIdentity();
        // Joomla's standard toolbar acts on selected cid[] checkboxes.
        if ($user->authorise('core.edit.state', 'com_minimarket')) {
            ToolbarHelper::publishList('categories.publish');
            ToolbarHelper::unpublishList('categories.unpublish');
        }
        if ($user->authorise('core.delete', 'com_minimarket')) {
            ToolbarHelper::deleteList(
                Text::_('COM_MINIMARKET_BULK_DELETE_CONFIRM'), 'categories.delete'
            );
        }
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
