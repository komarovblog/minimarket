<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Product;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
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
            throw new \RuntimeException('MiniMarket product form unavailable');
        }

        // Joomla Save/Apply проверяет form-validate; без скрипта сохранение может не начаться.
        Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('form.validate');

        ToolbarHelper::title(Text::_('COM_MINIMARKET_PRODUCT_EDIT_TITLE'));
        ToolbarHelper::apply('product.apply');
        ToolbarHelper::save('product.save');
        ToolbarHelper::cancel('product.cancel');

        parent::display($tpl);
    }
}
