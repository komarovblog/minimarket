<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Notifications;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminInbox;
use MiniMarket\Component\Minimarket\Administrator\Service\AttentionInbox;

final class HtmlView extends BaseHtmlView
{
    public array $events=[];
    public array $attentionGroups=[];
    public int $attentionTotal=0;
    public int $unread=0;
    public bool $capped=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $attention=AttentionInbox::load($db);
        $this->attentionGroups=$attention['groups'];
        $this->attentionTotal=$attention['total'];
        $feed=AdminInbox::load($db,(int)$user->id);
        $this->events=$feed['rows'];$this->unread=$feed['unread'];$this->capped=$feed['capped'];
        ToolbarHelper::title(Text::_('COM_MINIMARKET_NOTIFICATIONS_TITLE'));
        parent::display($tpl);
    }
}
