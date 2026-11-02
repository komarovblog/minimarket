<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Joomla-native multi-select publish, unpublish and delete.
 * Uses the matching singular AdminModel for safe database operations.
 */
final class ValuesController extends AdminController
{
    protected $text_prefix = 'COM_MINIMARKET_BULK';

    public function getModel($name = 'Value', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
