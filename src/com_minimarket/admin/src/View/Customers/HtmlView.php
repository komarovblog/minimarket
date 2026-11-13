<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Customers;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $customers=[];
    public int $total=0,$offset=0,$pageSize=20;
    public string $search='',$sort='newest';
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=['o.user_id>0','o.deleted_at IS NULL'];
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $checks=['u.name LIKE '.$like,'u.email LIKE '.$like];
            if(ctype_digit($this->search))$checks[]='o.user_id='.(int)$this->search;
            $where[]='('.implode(' OR ',$checks).')';
        }
        $whereSql=implode(' AND ',$where);
        $db->setQuery('SELECT COUNT(DISTINCT o.user_id) FROM '
            .$db->quoteName('#__minimarket_orders','o')
            .' INNER JOIN '.$db->quoteName('#__users','u').' ON u.id=o.user_id'
            .' WHERE '.$whereSql);
        $this->total=(int)$db->loadResult();
        $this->offset=$this->total?min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize)):0;
        $db->setQuery('SELECT o.user_id,u.name,u.email,COUNT(*) AS orders_count,'
            .'COALESCE(SUM(o.total_cents),0) AS orders_total_cents,'
            .'MAX(o.created_at) AS last_order FROM '.$db->quoteName('#__minimarket_orders','o')
            .' INNER JOIN '.$db->quoteName('#__users','u').' ON u.id=o.user_id'
            .' WHERE '.$whereSql.' GROUP BY o.user_id,u.name,u.email'
            .' ORDER BY last_order '.($this->sort==='oldest'?'ASC':'DESC'),
            $this->offset,$this->pageSize);
        $this->customers=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_CUSTOMERS_TITLE'));
        parent::display($tpl);
    }
}
