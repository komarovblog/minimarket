<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Attribute;

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
            throw new \RuntimeException('MiniMarket attribute form unavailable');
        }

        Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('form.validate');
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript(
                'com_minimarket.attribute-settings',
                'media/com_minimarket/js/attribute-settings.js',
                [],
                ['defer' => true]
            );
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.compact-values',
                'media/com_minimarket/js/compact-values.js', [], ['defer'=>true])
            ->registerAndUseStyle('com_minimarket.compact-values',
                'media/com_minimarket/css/compact-values.css');
        Text::script('COM_MINIMARKET_VALUE_NEW');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_ATTRIBUTE_EDIT_TITLE'));
        ToolbarHelper::apply('attribute.apply');
        ToolbarHelper::save('attribute.save');
        ToolbarHelper::cancel('attribute.cancel');

        parent::display($tpl);
    }
}
