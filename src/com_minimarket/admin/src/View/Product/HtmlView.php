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
    public array $recSimilar=[],$recTogether=[];

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if (!$this->form) {
            throw new \RuntimeException('MiniMarket product form unavailable');
        }

        if((int)($this->item->id??0)>0){
            $db=Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $db->setQuery($db->getQuery(true)->select(['l.kind','p.id','p.title'])
                ->from($db->quoteName('#__minimarket_recommendation_links','l'))
                ->join('INNER',$db->quoteName('#__minimarket_products','p')
                    .' ON p.id=l.target_product_id')
                ->where('l.source_product_id='.(int)$this->item->id)
                ->order('l.kind ASC,l.sort_order ASC,l.id ASC'));
            foreach((array)$db->loadAssocList() as $entry){
                $kind=(string)$entry['kind'];
                if(in_array($kind,['similar','together'],true))
                    $this->{'rec'.ucfirst($kind)}[(int)$entry['id']]=(string)$entry['title'];
            }
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
            ->registerAndUseScript('com_minimarket.stock-controls',
                'media/com_minimarket/js/stock-controls.js', [], ['defer' => true]);
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.sku-matrix',
                'media/com_minimarket/js/sku-matrix.js', [], ['defer' => true])
            ->registerAndUseScript('com_minimarket.group-editor',
                'media/com_minimarket/js/group-editor.js', [], ['defer' => true]);

        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.entity-picker',
                'media/com_minimarket/js/entity-picker.js',[],['defer'=>true]);
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.product-badges',
                'media/com_minimarket/js/product-badges.js',[],['defer'=>true]);
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.promo-video-editor',
                'media/com_minimarket/js/promo-video-editor.js',[],['defer'=>true]);
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript('com_minimarket.product-digital',
                'media/com_minimarket/js/product-digital.js',[],['defer'=>true]);
        $isBundle=($this->item->product_type ?? '')==='bundle'
            || ((int)($this->item->id ?? 0)===0
                && Factory::getApplication()->input->getCmd('product_type')==='bundle');
        ToolbarHelper::title(Text::_($isBundle
            ? 'COM_MINIMARKET_BUNDLE_EDIT_TITLE'
            : 'COM_MINIMARKET_PRODUCT_EDIT_TITLE'));
        ToolbarHelper::apply('product.apply');
        ToolbarHelper::save('product.save');
        ToolbarHelper::cancel('product.cancel');

        parent::display($tpl);
    }
}
