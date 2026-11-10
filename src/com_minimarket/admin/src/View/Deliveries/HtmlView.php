<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Deliveries;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** Real courier queue, separate from Orders, with owner/admin authorization. */
final class HtmlView extends BaseHtmlView
{
    public array $deliveries=[];
    public string $status='';
    public int $offset=0;
    public int $total=0;
    public int $pageSize=50;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $requested=$app->input->getCmd('filter_status','');
        $this->status=in_array($requested,['new','confirmed','completed','cancelled'],true)?$requested:'';
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=$db->quoteName('delivery_method').'='.$db->quote('courier');
        $count=$db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__minimarket_orders'))->where($where);
        if($this->status!=='')$count->where($db->quoteName('status').'='.$db->quote($this->status));
        $db->setQuery($count);
        $this->total=(int)$db->loadResult();
        $query=$db->getQuery(true)
            ->select(['id','customer_name','phone','delivery_city','delivery_address',
                      'delivery_fee_cents','status','created_at'])
            ->from($db->quoteName('#__minimarket_orders'))->where($where)->order('id DESC');
        if($this->status!=='')$query->where($db->quoteName('status').'='.$db->quote($this->status));
        $db->setQuery($query,$this->offset,$this->pageSize);
        $this->deliveries=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DASHBOARD_DELIVERIES'));
        parent::display($tpl);
    }
}
