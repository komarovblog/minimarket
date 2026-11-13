<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Workqueue;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\WorkqueueReads;

class HtmlView extends BaseHtmlView
{
    public array $groups=[];
    public bool $onlyUnread=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->onlyUnread=$app->input->getCmd('view','workqueue')==='notifications';
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        // Counts stay based on unresolved sources; read receipts never reduce them.
        $tables=[
            'payment'=>['orders',"payment_status IN ('unpaid','processing') AND status NOT IN ('cancelled','completed') AND deleted_at IS NULL"],
            'delivery'=>['shipments',"problem_status='open'"],
            'ticket'=>['support_tickets',"status IN ('new','in_progress')"],
            'returncase'=>['returns',"status IN ('new','approved','received')"]
        ];
        foreach(WorkqueueReads::KINDS as $kind){
            [$table,$where]=$tables[$kind];
            $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_'.$table)
                .' WHERE '.$where);
            $total=(int)$db->loadResult();
            $alias=match($kind){'payment'=>'o','delivery'=>'s','ticket'=>'t','returncase'=>'r'};
            $priorityFirst=$kind==='ticket'
                ?'CASE WHEN t.priority='.$db->quote('high').' THEN 0 ELSE 1 END,':'';
            $db->setQuery(WorkqueueReads::sourceSql($db,$kind)
                .' ORDER BY '.$priorityFirst.'date_value ASC,'.$alias.'.id ASC',0,100);
            $rows=(array)$db->loadObjectList();
            $ids=array_map(static fn($row)=>(int)$row->id,$rows);
            $receipts=WorkqueueReads::receipts($db,(int)$user->id,$kind,$ids);
            foreach($rows as $row){
                $row->signature=WorkqueueReads::signature($kind,$row);
                $saved=$receipts[(int)$row->id]??'';
                $row->wasRead=$saved!==''&&hash_equals($saved,$row->signature);
            }
            if($this->onlyUnread){
                $rows=array_values(array_filter($rows,
                    static fn($row)=>!$row->wasRead));
                // The inbox shows the first 100 current events per source;
                // do not call this number the global total when >100 exist.
                $total=count($rows);
            }
            $this->groups[$kind]=['total'=>$total,'rows'=>$rows];
        }
        ToolbarHelper::title(Text::_($this->onlyUnread
            ?'COM_MINIMARKET_NOTIFICATIONS_TITLE':'COM_MINIMARKET_WORKQUEUE_TITLE'));
        parent::display($tpl);
    }
}
