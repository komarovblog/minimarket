<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Workqueue;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $groups=[];
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $queries=[
            'payment'=>'SELECT id,customer_name AS description,created_at AS date_value'
                .' FROM '.$db->quoteName('#__minimarket_orders')
                ." WHERE payment_status IN ('unpaid','processing')"
                ." AND status NOT IN ('cancelled','completed') AND deleted_at IS NULL"
                .' ORDER BY created_at ASC,id ASC',
            'delivery'=>'SELECT s.id,CONCAT("Order #",s.order_id," · ",s.problem_code) AS description,'
                .'s.created_at AS date_value FROM '.$db->quoteName('#__minimarket_shipments','s')
                .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o')
                .' ON o.id=s.order_id'
                ." WHERE s.problem_status='open' AND o.deleted_at IS NULL"
                .' ORDER BY s.created_at ASC,s.id ASC',
            'ticket'=>'SELECT id,subject AS description,created_at AS date_value'
                .' FROM '.$db->quoteName('#__minimarket_support_tickets')
                ." WHERE status IN ('new','in_progress')"
                .' ORDER BY created_at ASC,id ASC',
            'returncase'=>'SELECT r.id,CONCAT("Order #",r.order_id," · ",r.reason)'
                .' AS description,r.created_at AS date_value'
                .' FROM '.$db->quoteName('#__minimarket_returns','r')
                .' INNER JOIN '.$db->quoteName('#__minimarket_orders','o')
                .' ON o.id=r.order_id'
                ." WHERE r.status IN ('new','approved','received') AND o.deleted_at IS NULL"
                .' ORDER BY r.created_at ASC,r.id ASC'
        ];
        $tables=[
            'payment'=>['orders',"payment_status IN ('unpaid','processing') AND status NOT IN ('cancelled','completed') AND deleted_at IS NULL"],
            'delivery'=>['shipments',"problem_status='open'"],
            'ticket'=>['support_tickets',"status IN ('new','in_progress')"],
            'returncase'=>['returns',"status IN ('new','approved','received')"]
        ];
        foreach($queries as $kind=>$sql){
            [$table,$where]=$tables[$kind];
            $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_'.$table)
                .' WHERE '.$where);
            $total=(int)$db->loadResult();
            $db->setQuery($sql,0,100);
            $this->groups[$kind]=['total'=>$total,'rows'=>(array)$db->loadObjectList()];
        }
        ToolbarHelper::title(Text::_('COM_MINIMARKET_WORKQUEUE_TITLE'));
        parent::display($tpl);
    }
}
