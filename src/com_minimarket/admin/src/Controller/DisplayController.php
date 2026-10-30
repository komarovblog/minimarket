<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->getView('dashboard', 'html');
        $view->display();
    }
}
