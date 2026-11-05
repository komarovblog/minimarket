<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Bundles;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public array $categoryOptions = [];
    public $pagination;
    public string $search = '';
    public string $stateFilter = 'all';
    public int $categoryFilter = 0;
    public string $sort = 'newest';

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->state = $this->get('State');

        $this->pagination = $this->get('Pagination');
        $this->categoryOptions = $this->get('CategoryOptions');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_BUNDLES'));

        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_minimarket')) {
            ToolbarHelper::link('index.php?option=com_minimarket&view=product&layout=edit&product_type=bundle',
                Text::_('JTOOLBAR_NEW'), 'new');
        }

        // Joomla component Options are not a separate MiniMarket MVC view.
        // Show the standard Options action on the pages admins use most.
        $user = Factory::getApplication()->getIdentity();
        // Joomla's standard toolbar acts on selected cid[] checkboxes.
        if ($user->authorise('core.edit.state', 'com_minimarket')) {
            ToolbarHelper::publishList('bundles.publish');
            ToolbarHelper::unpublishList('bundles.unpublish');
        }
        if ($user->authorise('core.delete', 'com_minimarket')) {
            ToolbarHelper::deleteList(
                Text::_('COM_MINIMARKET_BULK_DELETE_CONFIRM'), 'bundles.delete'
            );
        }
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
