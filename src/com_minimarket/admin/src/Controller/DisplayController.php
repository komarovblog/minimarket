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
                'com_minimarket.admin-reference-picker',
                'media/com_minimarket/js/admin-reference-picker.js',
                [],
                ['defer'=>true]
            )
            ->registerAndUseScript(
                'com_minimarket.inherited-options',
                'media/com_minimarket/js/inherited-options.js',
                [],
                ['defer'=>true]
            );

        // Joomla's own table-columns menu is the sole column selector.
        // No MiniMarket DOM enhancer, duplicate style or localStorage logic.
        if (in_array($viewName, [
            'attributes', 'brands', 'bundlegroups', 'bundles', 'categories',
            'collections', 'groups', 'orderoptions', 'products', 'values',
            'orders', 'deliveries', 'returns', 'payments', 'customers','tickets','stockreport','salesreport','returnsreport','businessreport','planfactreport','planfactreport','planfactreport', 'deliveryzones', 'reviews', 'promocodes',
            'seofilters', 'autodiscounts', 'recommendations', 'feeds', 'registrationmethods', 'deliverymethods', 'paymentmethods', 'notificationmethods'
        ], true)) {
            \Joomla\CMS\Factory::getApplication()->getDocument()
                ->getWebAssetManager()->useScript('table.columns')
                ->useScript('multiselect');
        }

        if (!\in_array($viewName, ['dashboard', 'feeds', 'feed', 'categories', 'category', 'products', 'product', 'attributes', 'attribute', 'values', 'value', 'groups', 'group', 'orderoptions', 'orderoption', 'import', 'brands', 'brand', 'collections', 'collection', 'bundles', 'bundlegroups', 'orders', 'order', 'deliveries', 'delivery', 'returns', 'returncase', 'payments', 'payment', 'customers','customer','tickets','ticket','workqueue','stockreport','salesreport','returnsreport','businessreport', 'deliveryzones', 'deliveryzone', 'deliverymethods', 'paymentmethods', 'registrationmethods', 'notificationmethods', 'reviews', 'review', 'seofilters', 'seofilter', 'promocodes','promocode','autodiscounts','autodiscount','recommendations','recommendation'], true)) {
            throw new \RuntimeException('Unknown MiniMarket view', 404);
        }

        // Normalize Joomla's native filter[...]/list[...] POST fields before
        // legacy no-ListModel views read the request. This is intentionally
        // limited to entity lists and never alters edit/save actions.
        if (in_array($viewName, [
            'orders', 'deliveries', 'returns', 'payments', 'customers','tickets','stockreport', 'reviews', 'promocodes', 'autodiscounts', 'recommendations', 'seofilters',
            'feeds', 'registrationmethods', 'deliveryzones',
            'deliverymethods', 'paymentmethods', 'notificationmethods'
        ], true)) {
            \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::importRequest();
        }

        // Keep the proven M0 dashboard without introducing a dummy Model.
        if (in_array($viewName, ['dashboard','import','feeds','feed','orders','order','deliveries','delivery','returns','returncase','payments','payment','customers','customer','tickets','ticket','workqueue','stockreport','salesreport','returnsreport','businessreport','deliveryzones','deliveryzone','deliverymethods','paymentmethods','registrationmethods','notificationmethods','reviews','review','seofilters','seofilter','promocodes','promocode','autodiscounts','autodiscount','recommendations','recommendation'],true)) {
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
