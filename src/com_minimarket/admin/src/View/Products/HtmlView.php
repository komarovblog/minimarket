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
    public array $categoryOptions = [];
    public $pagination;
    public string $search = '';
    public string $stateFilter = 'all';
    public int $categoryFilter = 0;
    public string $sort = 'newest';

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->categoryOptions = $this->get('CategoryOptions');
        $input = Factory::getApplication()->input;
        $this->search = trim($input->getString('mm_search', ''));
        $this->categoryFilter = max(0, $input->getInt('mm_category', 0));
        $this->stateFilter = $input->getCmd('mm_state', 'all');
        if (!in_array($this->stateFilter, ['all', '1', '0'], true)) {
            $this->stateFilter = 'all';
        }
        $this->sort = $input->getCmd('mm_sort', 'newest');
        if (!in_array($this->sort, ['newest', 'title', 'price_asc', 'price_desc', 'order'], true)) {
            $this->sort = 'newest';
        }

        ToolbarHelper::title(Text::_('COM_MINIMARKET_PRODUCTS'));

        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_minimarket')) {
            ToolbarHelper::addNew('product.add');
        }

        // Joomla component Options are not a separate MiniMarket MVC view.
        // Show the standard Options action on the pages admins use most.
        $user = Factory::getApplication()->getIdentity();
        // Joomla's standard toolbar acts on selected cid[] checkboxes.
        if ($user->authorise('core.edit.state', 'com_minimarket')) {
            ToolbarHelper::publishList('products.publish');
            ToolbarHelper::unpublishList('products.unpublish');
        }
        if ($user->authorise('core.delete', 'com_minimarket')) {
            ToolbarHelper::deleteList(
                Text::_('COM_MINIMARKET_BULK_DELETE_CONFIRM'), 'products.delete'
            );
        }
        if ($user->authorise('core.admin', 'com_minimarket')
            || $user->authorise('core.options', 'com_minimarket')) {
            ToolbarHelper::preferences('com_minimarket');
        }

        parent::display($tpl);
    }
}
