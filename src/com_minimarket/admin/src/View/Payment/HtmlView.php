<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Payment;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $order=null;
    public array $records=[];
    public bool $canConfirm=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canConfirm=$user->authorise('core.edit.state','com_minimarket');
        $id=$app->input->getInt('id',0);
        if($id<1)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select('o.*')
            ->from($db->quoteName('#__minimarket_orders','o'))
            ->where('o.id='.$id);
        $db->setQuery($q);
        $this->order=$db->loadObject()?:null;
        if(!$this->order)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $q=$db->getQuery(true)->select('*')
            ->from($db->quoteName('#__minimarket_payment_records'))
            ->where('order_id='.$id)->order('confirmed_at DESC,id DESC');
        $db->setQuery($q);
        $this->records=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_PAYMENTS_TITLE').' #'.$id);
        parent::display($tpl);
    }
}
