<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Collections;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public array $items=[];
    public function display($tpl=null)
    {
        $this->items=$this->get('Items'); $this->pagination=$this->get('Pagination');
        $this->filterForm=$this->get('FilterForm'); $this->activeFilters=$this->get('ActiveFilters');
        $this->state=$this->get('State');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_COLLECTIONS'));
        $user=Factory::getApplication()->getIdentity();
        if ($user->authorise('core.create','com_minimarket')) ToolbarHelper::addNew('collection.add');
        if ($user->authorise('core.edit.state','com_minimarket')) {
            ToolbarHelper::publishList('collections.publish');
            ToolbarHelper::unpublishList('collections.unpublish');
        }
        if ($user->authorise('core.delete','com_minimarket')) {
            ToolbarHelper::deleteList(Text::_('COM_MINIMARKET_BULK_DELETE_CONFIRM'),'collections.delete');
        }
        parent::display($tpl);
    }
}
