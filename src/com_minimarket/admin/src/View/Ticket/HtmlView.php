<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Ticket;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public ?object $ticket=null;
    public array $messages=[];
    public bool $isCreate=false,$canEdit=false,$canChangeStatus=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$u=$app->getIdentity();
        if(!$u->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canEdit=$u->authorise('core.edit','com_minimarket');
        $this->canChangeStatus=$u->authorise('core.edit.state','com_minimarket');
        $this->isCreate=$app->input->getCmd('layout','default')==='create';
        if($this->isCreate){
            if(!$u->authorise('core.create','com_minimarket'))
                throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
            $this->setLayout('create');
            ToolbarHelper::title(Text::_('COM_MINIMARKET_TICKET_NEW'));
            parent::display($tpl);return;
        }
        $id=$app->input->getInt('id',0);
        if($id<1)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($db->getQuery(true)->select('*')
            ->from($db->quoteName('#__minimarket_support_tickets'))
            ->where('id='.$id));
        $this->ticket=$db->loadObject()?:null;
        if(!$this->ticket)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $db->setQuery($db->getQuery(true)->select(['body','internal','created_at','author_user_id'])
            ->from($db->quoteName('#__minimarket_support_messages'))
            ->where('ticket_id='.$id)->order('id ASC'),0,500);
        $this->messages=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_TICKETS_TITLE').' #'.$id);
        parent::display($tpl);
    }
}
