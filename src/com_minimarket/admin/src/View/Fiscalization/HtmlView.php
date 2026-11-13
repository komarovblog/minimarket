<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Fiscalization;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** Read-only audit registry. No fiscal provider installed or simulated. */
final class HtmlView extends BaseHtmlView
{
    public array $operations=[];
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.admin','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery('SELECT id,order_id,operation_type,provider_code,status,'
            .'provider_operation_id,fiscal_reference,receipt_url,last_error_code,created_at,confirmed_at '
            .'FROM '.$db->quoteName('#__minimarket_fiscal_operations')
            .' ORDER BY id DESC',0,100);
        $this->operations=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_FISCAL_TITLE'));
        parent::display($tpl);
    }
}
