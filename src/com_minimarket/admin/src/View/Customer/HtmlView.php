<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Customer;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public ?object $customer=null;
    public array $orders=[],$notes=[],$tickets=[];
    public bool $canNote=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $id=$app->input->getInt('id',0);
        if($id<1)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $this->canNote=$user->authorise('core.edit','com_minimarket');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($db->getQuery(true)->select(['id','name','email','registerDate','block'])
            ->from($db->quoteName('#__users'))->where('id='.$id));
        $this->customer=$db->loadObject()?:null;
        if(!$this->customer)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $db->setQuery($db->getQuery(true)->select(['id','created_at','status','payment_status',
            'fulfillment_status','total_cents'])
            ->from($db->quoteName('#__minimarket_orders'))
            ->where('user_id='.$id)->where('deleted_at IS NULL')
            ->order('id DESC'),0,100);
        $this->orders=(array)$db->loadObjectList();
        $db->setQuery($db->getQuery(true)->select(['id','body','author_user_id','created_at'])
            ->from($db->quoteName('#__minimarket_customer_notes'))
            ->where('user_id='.$id)->order('id DESC'),0,100);
        $this->notes=(array)$db->loadObjectList();
        $db->setQuery($db->getQuery(true)->select(['id','subject','status','priority','updated_at','created_at'])
            ->from($db->quoteName('#__minimarket_support_tickets'))
            ->where('user_id='.$id)->order('id DESC'),0,100);
        $this->tickets=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_CUSTOMER_TITLE').' #'.$id);
        parent::display($tpl);
    }
}
