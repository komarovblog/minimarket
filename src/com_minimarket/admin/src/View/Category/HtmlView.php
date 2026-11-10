<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Category;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public array $inheritedDefaults=[];

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if (!$this->form) {
            throw new \RuntimeException('Category form unavailable');
        }

        $global=\Joomla\CMS\Component\ComponentHelper::getParams('com_minimarket');
        $boolean=[
            'show_children'=>['category_show_children',1],
            'show_products'=>['category_show_products',1],
            'show_bundles'=>['category_show_bundles',1],
            'show_descendant_products'=>['category_show_descendant_products',0],
            'show_descendant_bundles'=>['category_show_descendant_bundles',0],
            'filter_stock_mode'=>['filter_stock_enabled',0],
        ];
        foreach ($boolean as $field=>[$globalKey,$default]) {
            $this->inheritedDefaults[$field]=(int)$global->get($globalKey,$default)===1
                ?Text::_('JYES'):Text::_('JNO');
        }
        foreach(['xs'=>1,'sm'=>1,'md'=>2,'lg'=>3,'xl'=>4,'xxl'=>4] as $size=>$fallback) {
            $this->inheritedDefaults['cards_'.$size]=(string)$global->get('category_cards_'.$size,$fallback);
        }
        $this->inheritedDefaults['products_per_page']=(string)$global->get('category_products_per_page',10);
        $this->inheritedDefaults['product_layout']=Text::_(
            (string)$global->get('category_product_layout','list')==='grid'
            ?'COM_MINIMARKET_LAYOUT_GRID':'COM_MINIMARKET_LAYOUT_LIST');
        $price=(string)$global->get('filter_price_mode','slider');
        $this->inheritedDefaults['filter_price_mode']=Text::_(
            ['none'=>'COM_MINIMARKET_MODE_NONE','range'=>'COM_MINIMARKET_FILTER_RANGE',
             'slider'=>'COM_MINIMARKET_FILTER_SLIDER'][$price]??'COM_MINIMARKET_FILTER_SLIDER');
        $this->inheritedDefaults['filter_sku_mode']=Text::_(
            (string)$global->get('filter_sku_mode','none')==='text'
            ?'COM_MINIMARKET_SYSTEM_SKU_SEARCH':'COM_MINIMARKET_MODE_NONE');
        $this->inheritedDefaults['meta_robots']=(string)Factory::getConfig()->get('robots','index,follow');

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

        ToolbarHelper::title(Text::_('COM_MINIMARKET_CATEGORY_EDIT_TITLE'));
        ToolbarHelper::apply('category.apply');
        ToolbarHelper::save('category.save');
        ToolbarHelper::cancel('category.cancel');

        parent::display($tpl);
    }
}
