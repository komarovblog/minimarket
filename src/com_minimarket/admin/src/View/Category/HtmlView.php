<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Category;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if (!$this->form) {
            throw new \RuntimeException('Category form unavailable');
        }

        ToolbarHelper::title(Text::_('COM_MINIMARKET_CATEGORY_EDIT_TITLE'));
        ToolbarHelper::apply('category.apply');
        ToolbarHelper::save('category.save');
        ToolbarHelper::cancel('category.cancel');

        parent::display($tpl);
    }
}
