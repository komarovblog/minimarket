<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Payments;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools;

/**
 * Payment control register: exactly one row per Order payment obligation.
 * Actual confirmed receipts are in payment_records, not synthetic rows.
 */
final class HtmlView extends BaseHtmlView
{
    public array $payments=[];
    public int $total=0,$offset=0,$pageSize=20;
    public string $search='',$statusFilter='',$methodFilter='',$sort='newest';

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        NativeListTools::importRequest();
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $status=$app->input->getCmd('filter_status','');
        $this->statusFilter=in_array($status,['unpaid','processing','paid','refunded'],true)?$status:'';
        $method=$app->input->getCmd('filter_method','');
        $this->methodFilter=in_array($method,['on_receipt','bank_transfer','pending'],true)?$method:'';
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=['o.deleted_at IS NULL'];
        if($this->statusFilter!=='')$where[]='o.payment_status='.$db->quote($this->statusFilter);
        if($this->methodFilter!=='')$where[]='o.payment_method='.$db->quote($this->methodFilter);
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $terms=['o.customer_name LIKE '.$like,'o.phone LIKE '.$like,'o.email LIKE '.$like];
            if(ctype_digit($this->search))$terms[]='o.id='.(int)$this->search;
            $where[]='('.implode(' OR ',$terms).')';
        }
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_orders','o'));
        foreach($where as $w)$q->where($w);
        $db->setQuery($q);$this->total=(int)$db->loadResult();
        if($this->total>0)$this->offset=min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        else $this->offset=0;
        $q=$db->getQuery(true)
            ->select(['o.id','o.customer_name','o.created_at','o.paid_at',
                      'o.payment_status','o.payment_method','o.payment_reference',
                      'o.total_cents','o.currency'])
            ->from($db->quoteName('#__minimarket_orders','o'))
            ->order('o.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $w)$q->where($w);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->payments=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_PAYMENTS_TITLE'));
        parent::display($tpl);
    }
}
