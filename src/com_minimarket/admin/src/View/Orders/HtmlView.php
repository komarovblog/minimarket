<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Orders;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $orders=[];
    public int $total=0;
    public int $offset=0;
    public int $pageSize=50;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__minimarket_orders'));
        $this->total=(int)$db->loadResult();
        $q=$db->getQuery(true)->select(['id','customer_name','phone','status',
            'total_cents','item_count','created_at'])
            ->from($db->quoteName('#__minimarket_orders'))
            ->order('id DESC');
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->orders=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_ORDERS_TITLE'));
        parent::display($tpl);
    }
}
