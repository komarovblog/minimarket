<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Businessreport;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CommerceReportRange as Range;
final class HtmlView extends BaseHtmlView
{
    public array $metrics=[];
    public string $from='',$to='';
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
        $source=$db->quoteName('#__minimarket_orders','o');
        $db->setQuery('SELECT COUNT(*) AS orders_count,'
            .'COALESCE(SUM(o.items_total_cents-o.discount_cents),0) AS goods_cents,'
            .'COALESCE(SUM(o.delivery_fee_cents),0) AS shipping_charged_cents'
            .' FROM '.$source.' WHERE '.$where);
        $orders=$db->loadAssoc();
        $db->setQuery('SELECT COUNT(*) AS lines_count,'
            .'SUM(CASE WHEN oi.line_cost_cents IS NULL THEN 1 ELSE 0 END) AS missing,'
            .'COALESCE(SUM(oi.line_cost_cents),0) AS costs_cents'
            .' FROM '.$db->quoteName('#__minimarket_order_items','oi')
            .' INNER JOIN '.$source.' ON o.id=oi.order_id WHERE '.$where);
        $cost=$db->loadAssoc();
        $db->setQuery('SELECT COUNT(*) AS shipments_count,'
            .'SUM(CASE WHEN s.actual_cost_cents IS NULL THEN 1 ELSE 0 END) AS missing,'
            .'COALESCE(SUM(s.actual_cost_cents),0) AS delivery_cost_cents'
            .' FROM '.$db->quoteName('#__minimarket_shipments','s')
            .' INNER JOIN '.$source.' ON o.id=s.order_id WHERE '.$where);
        $shipment=$db->loadAssoc();
        $goods=(int)($orders['goods_cents']??0);
        $costKnown=(int)($orders['orders_count']??0)>0 && (int)($cost['lines_count']??0)>0
            &&(int)($cost['missing']??0)===0;
        $deliveryKnown=(int)($shipment['missing']??0)===0;
        $this->metrics=[
            'orders'=>(int)($orders['orders_count']??0),
            'goods'=>$goods,
            'shipping_charged'=>(int)($orders['shipping_charged_cents']??0),
            'goods_cost'=>$costKnown?(int)$cost['costs_cents']:null,
            'goods_difference'=>$costKnown?$goods-(int)$cost['costs_cents']:null,
            'shipping_expense'=>$deliveryKnown?(int)($shipment['delivery_cost_cents']??0):null,
            'shipments'=>(int)($shipment['shipments_count']??0)
        ];
        ToolbarHelper::title(Text::_('COM_MINIMARKET_BUSINESS_REPORT_TITLE'));
        parent::display($tpl);
    }
}
