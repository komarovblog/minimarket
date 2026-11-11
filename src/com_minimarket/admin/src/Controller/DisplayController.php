<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = [])
    {
        $viewName = $this->input->getCmd('view', 'dashboard');
        // One responsive admin stylesheet for every MiniMarket entity, no
        // global Joomla changes and no horizontal form-table scrolling.
        \Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseStyle(
                'com_minimarket.admin-ui',
                'media/com_minimarket/css/admin-ui.css'
            )
            ->registerAndUseScript(
                'com_minimarket.admin-columns',
                'media/com_minimarket/js/admin-columns.js',
                [],
                ['defer'=>true]
            )
            ->registerAndUseScript(
                'com_minimarket.admin-reference-picker',
                'media/com_minimarket/js/admin-reference-picker.js',
                [],
                ['defer'=>true]
            )
            ->registerAndUseScript(
                'com_minimarket.admin-list-tools',
                'media/com_minimarket/js/admin-list-tools.js',
                [],
                ['defer'=>true]
            )
            ->registerAndUseScript(
                'com_minimarket.inherited-options',
                'media/com_minimarket/js/inherited-options.js',
                [],
                ['defer'=>true]
            );

        if (!\in_array($viewName, ['dashboard', 'feeds', 'feed', 'categories', 'category', 'products', 'product', 'attributes', 'attribute', 'values', 'value', 'groups', 'group', 'orderoptions', 'orderoption', 'import', 'brands', 'brand', 'collections', 'collection', 'bundles', 'bundlegroups', 'orders', 'order', 'deliveries', 'delivery', 'deliveryzones', 'deliveryzone', 'integrations', 'reviews', 'review', 'seofilters', 'seofilter', 'promocodes','promocode'], true)) {
            throw new \RuntimeException('Unknown MiniMarket view', 404);
        }

        // Keep the proven M0 dashboard without introducing a dummy Model.
        if (in_array($viewName, ['dashboard','import','feeds','feed','orders','order','deliveries','delivery','deliveryzones','deliveryzone','integrations','reviews','review','seofilters','seofilter','promocodes','promocode'],true)) {
            if(in_array($viewName,['feeds','feed'],true)) {
                \Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()
                    ->registerAndUseStyle('com_minimarket.feed-editor',
                        'media/com_minimarket/css/feed-editor.css');
            }
            if($viewName==='feed') {
                \Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()
                    ->registerAndUseScript('com_minimarket.feed-editor',
                        'media/com_minimarket/js/feed-editor.js',[],['defer'=>true]);
            }
            $view = $this->getView($viewName, 'html');
            $view->display();

            return $this;
        }

        return parent::display($cachable, $urlparams);
    }
}
