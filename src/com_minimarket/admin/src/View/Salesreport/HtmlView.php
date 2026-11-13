<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Salesreport;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CommerceReportRange as Range;
final class HtmlView extends BaseHtmlView
{
    public array $rows=[];
    public string $from='',$to='';
    public bool $truncated=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.admin','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        [$this->from,$this->to,$until]=Range::normalize(
            $app->input->getString('from',''),$app->input->getString('to',''));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=Range::where($db,'o',$this->from,$until)
            ." AND o.status='completed' AND o.payment_status='paid' AND o.deleted_at IS NULL";
        $db->setQuery('SELECT oi.product_id,oi.title,SUM(oi.quantity) AS qty,'
            .'SUM(oi.line_cents) AS gross_cents,'
            .'SUM(CASE WHEN oi.line_cost_cents IS NULL THEN 1 ELSE 0 END) AS unknown_cost_lines,'
            .'SUM(oi.line_cost_cents) AS known_cost_cents'
            .' FROM '.$db->quoteName('#__minimarket_order_items','oi')
            .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o')
            .' ON o.id=oi.order_id WHERE '.$where
            .' GROUP BY oi.product_id,oi.title ORDER BY gross_cents DESC,oi.product_id ASC',0,101);
        $all=(array)$db->loadObjectList();
        $this->truncated=count($all)>100;
        $this->rows=array_slice($all,0,100);
        ToolbarHelper::title(Text::_('COM_MINIMARKET_SALES_REPORT_TITLE'));
        parent::display($tpl);
    }
}
