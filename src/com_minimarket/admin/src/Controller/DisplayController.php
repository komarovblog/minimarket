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
            );

        if (!\in_array($viewName, ['dashboard', 'categories', 'category', 'products', 'product', 'attributes', 'attribute', 'values', 'value', 'groups', 'group', 'orderoptions', 'orderoption', 'import', 'brands', 'brand', 'collections', 'collection', 'bundles', 'bundlegroups'], true)) {
            throw new \RuntimeException('Unknown MiniMarket view', 404);
        }

        // Keep the proven M0 dashboard without introducing a dummy Model.
        if ($viewName === 'dashboard' || $viewName === 'import') {
            $view = $this->getView($viewName, 'html');
            $view->display();

            return $this;
        }

        return parent::display($cachable, $urlparams);
    }
}
