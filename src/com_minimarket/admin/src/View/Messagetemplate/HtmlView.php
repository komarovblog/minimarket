<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Messagetemplate;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\MessageTemplates;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;

final class HtmlView extends BaseHtmlView
{
    public ?object $item=null;
    public array $events=[];
    public string $initialAudience='customer',$initialEvent='order_created',$initialChannel='email';
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$id=$app->input->getInt('id',0);
        $user=$app->getIdentity();
        if(!$user->authorise($id?'core.edit':'core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        if($id){
            $db->setQuery('SELECT * FROM '.$db->quoteName('#__minimarket_message_templates')
                .' WHERE id='.$id);
            $this->item=$db->loadObject()?:null;
            if(!$this->item)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        }
        $selected=$app->input->getCmd('audience','customer');
        $this->initialAudience=in_array($selected,MessageTemplates::AUDIENCES,true)
            ?$selected:'customer';
        $event=$app->input->getCmd('event_code','');
        $available=MessageTemplates::events($this->initialAudience);
        $this->initialEvent=in_array($event,$available,true)?$event:(string)($available[0]??'');
        $channel=$app->input->getCmd('channel_code','email');
        $this->initialChannel=in_array($channel,MessageTemplates::CHANNELS,true)?$channel:'email';
        $this->events=[
            'customer'=>NotificationPolicy::EVENTS,
            'admin'=>NotificationPolicy::ADMIN_EVENTS,
            'marketing'=>array_combine(MessageTemplates::MARKETING_EVENTS,
                array_map(static fn($event)=>\MiniMarket\Component\Minimarket\Administrator\Service\MarketingFlows::TRIGGERS[$event],
                MessageTemplates::MARKETING_EVENTS))
        ];
        $this->setLayout('edit');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_MESSAGE_TEMPLATES_TITLE'));
        parent::display($tpl);
    }
}
