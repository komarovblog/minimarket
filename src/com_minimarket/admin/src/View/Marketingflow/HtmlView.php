<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Marketingflow;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $flow=null;
    public array $steps=[],$templates=[];
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        $id=$app->input->getInt('id',0);
        if(!$user->authorise($id?'core.edit':'core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        if($id){
            $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_marketing_flows')
                .' WHERE id='.$id);
            $this->flow=$db->loadObject()?:null;
            if(!$this->flow)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
            $db->setQuery('SELECT step_no,delay_minutes,template_id FROM '
                .$db->quoteName('#__minimarket_marketing_flow_steps')
                .' WHERE flow_id='.$id.' ORDER BY step_no ASC');
            $this->steps=(array)$db->loadAssocList();
        }
        if(!$this->steps)$this->steps=[['delay_minutes'=>180,'template_id'=>0]];
        $db->setQuery('SELECT id,title,event_code FROM '
            .$db->quoteName('#__minimarket_message_templates')
            ." WHERE audience='marketing' AND channel_code='email' AND state=1"
            .' ORDER BY event_code ASC,title ASC,id ASC');
        $this->templates=(array)$db->loadAssocList();
        $this->setLayout('edit');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_MARKETING_FLOWS_TITLE'));
        parent::display($tpl);
    }
}
