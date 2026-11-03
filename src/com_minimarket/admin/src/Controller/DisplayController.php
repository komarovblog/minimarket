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

        if (!\in_array($viewName, ['dashboard', 'categories', 'category', 'products', 'product', 'attributes', 'attribute', 'values', 'value', 'groups', 'group', 'orderoptions', 'orderoption', 'import'], true)) {
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
