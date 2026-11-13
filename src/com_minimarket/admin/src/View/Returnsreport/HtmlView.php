<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Returnsreport;
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
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.admin','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        [$this->from,$this->to,$until]=Range::normalize(
            $app->input->getString('from',''),$app->input->getString('to',''));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=Range::where($db,'r',$this->from,$until);
        $db->setQuery('SELECT r.reason,r.status,COUNT(*) AS cases_count,'
            .'COALESCE(SUM(ri.items_count),0) AS items_count'
            .' FROM '.$db->quoteName('#__minimarket_returns','r')
            .' LEFT JOIN (SELECT return_id,SUM(quantity) AS items_count'
            .' FROM '.$db->quoteName('#__minimarket_return_items')
            .' GROUP BY return_id) ri ON ri.return_id=r.id'
            .' WHERE '.$where.' GROUP BY r.reason,r.status'
            .' ORDER BY cases_count DESC,r.reason ASC,r.status ASC');
        $this->rows=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_RETURNS_REPORT_TITLE'));
        parent::display($tpl);
    }
}
