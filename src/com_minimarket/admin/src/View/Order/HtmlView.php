<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Order;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $order=null;
    public array $items=[];
    public bool $canUpdate=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $identity=$app->getIdentity();
        if (!$identity->authorise('core.manage','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $this->canUpdate=$identity->authorise('core.edit.state','com_minimarket');
        $id=$app->input->getInt('id',0);
        if ($id<1) throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select('*')
            ->from($db->quoteName('#__minimarket_orders'))
            ->where($db->quoteName('id').'='.(int)$id);
        $db->setQuery($q);
        $this->order=$db->loadObject() ?: null;
        if ($this->order===null) throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $q=$db->getQuery(true)->select('*')
            ->from($db->quoteName('#__minimarket_order_items'))
            ->where($db->quoteName('order_id').'='.(int)$id)
            ->order('id ASC');
        $db->setQuery($q);
        $this->items=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::sprintf('COM_MINIMARKET_ORDER_HEADING',$id));
        parent::display($tpl);
    }
}
