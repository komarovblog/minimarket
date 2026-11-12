<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Orderoption;

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
        if (!$this->form) throw new \RuntimeException('MiniMarket order option form unavailable');
        Text::script('COM_MINIMARKET_HEX_CLEAR');
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->useScript('form.validate')
            ->registerAndUseScript('com_minimarket.option-value-color',
                'media/com_minimarket/js/option-value-color.js', [], ['defer' => true]);
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.compact-values',
                'media/com_minimarket/js/compact-values.js', [], ['defer'=>true])
            ->registerAndUseStyle('com_minimarket.compact-values',
                'media/com_minimarket/css/compact-values.css');
        Text::script('COM_MINIMARKET_VALUE_NEW');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_ORDER_OPTION_EDIT'));
        ToolbarHelper::apply('orderoption.apply');
        ToolbarHelper::save('orderoption.save');
        ToolbarHelper::cancel('orderoption.cancel');
        parent::display($tpl);
    }
}
