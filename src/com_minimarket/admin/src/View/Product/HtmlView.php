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
        // Component-local Joomla Media cleanup; no Joomla core modifications.
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript(
                'com_minimarket.clean-media',
                'media/com_minimarket/js/clean-media.js',
                [],
                ['defer' => true]
            );

        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript(
                'com_minimarket.product-attributes',
                'media/com_minimarket/js/product-attributes.js',
                [],
                ['defer' => true]
            );
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.variants-admin',
                'media/com_minimarket/js/variants-admin.js', [], ['defer' => true]);

        ToolbarHelper::title(Text::_('COM_MINIMARKET_PRODUCT_EDIT_TITLE'));
        ToolbarHelper::apply('product.apply');
        ToolbarHelper::save('product.save');
        ToolbarHelper::cancel('product.cancel');

        parent::display($tpl);
    }
}
