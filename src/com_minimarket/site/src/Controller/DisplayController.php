<?php
namespace MiniMarket\Component\Minimarket\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    protected $default_view = 'catalog';

    public function display($cachable = false, $urlparams = [])
    {
        $viewName = $this->input->getCmd('view', 'catalog');

        if (!\in_array($viewName, ['catalog', 'category', 'product'], true)) {
            throw new \RuntimeException('Unknown MiniMarket view', 404);
        }

        $view = $this->getView($viewName, 'html');
        $view->display();

        return $this;
    }
}
