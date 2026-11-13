<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Planfactreport;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\SalesPlan;

final class HtmlView extends BaseHtmlView
{
    public int $year=0,$month=0,$actualCents=0,$orders=0;
    public ?int $targetCents=null,$percentTenths=null;
    public array $history=[];

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.admin','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->year=$app->input->getInt('year',(int)gmdate('Y'));
        $this->month=$app->input->getInt('month',(int)gmdate('n'));
        try{[$from,$until]=SalesPlan::period($this->year,$this->month);}
        catch(\DomainException){throw new \RuntimeException(Text::_('COM_MINIMARKET_PLANFACT_REPORT_INVALID'),400);}
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery('SELECT target_goods_cents FROM '
            .$db->quoteName('#__minimarket_sales_plans')
            .' WHERE period_year='.$this->year.' AND period_month='.$this->month);
        $result=$db->loadResult();
        $this->targetCents=$result===null?null:(int)$result;
        $db->setQuery('SELECT COUNT(*) AS orders_count,'
            .' COALESCE(SUM(o.items_total_cents-o.discount_cents),0) AS goods_cents'
            .' FROM '.$db->quoteName('#__minimarket_orders','o')
            .' WHERE o.created_at>='.$db->quote($from)
            .' AND o.created_at<'.$db->quote($until)
            ." AND o.status='completed' AND o.payment_status='paid'"
            ." AND o.currency='RUB' AND o.deleted_at IS NULL");
        $sales=$db->loadAssoc();
        $this->orders=(int)($sales['orders_count']??0);
        $this->actualCents=(int)($sales['goods_cents']??0);
        $this->percentTenths=$this->targetCents===null?null
            :SalesPlan::percentTenths($this->actualCents,$this->targetCents);
        $db->setQuery('SELECT previous_target_cents,target_goods_cents,actor_user_id,created_at'
            .' FROM '.$db->quoteName('#__minimarket_sales_plan_revisions')
            .' WHERE period_year='.$this->year.' AND period_month='.$this->month
            .' ORDER BY id DESC',0,50);
        $this->history=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_PLANFACT_REPORT_TITLE'));
        parent::display($tpl);
    }
}
