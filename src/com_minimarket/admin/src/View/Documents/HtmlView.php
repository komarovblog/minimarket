<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Documents;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools;

final class HtmlView extends BaseHtmlView
{
    public array $documents=[];
    public int $total=0,$offset=0,$pageSize=20,$orderFilter=0;
    public string $search='',$sort='newest';
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        NativeListTools::importRequest();
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $this->orderFilter=max(0,$app->input->getInt('filter_order',0));
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=[];
        if($this->orderFilter>0)$where[]='d.order_id='.$this->orderFilter;
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $parts=['d.document_number LIKE '.$like,'o.customer_name LIKE '.$like];
            if(ctype_digit($this->search))$parts[]='d.order_id='.(int)$this->search;
            $where[]='('.implode(' OR ',$parts).')';
        }
        $q=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_order_documents','d'))
            ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=d.order_id');
        foreach($where as $term)$q->where($term);
        $db->setQuery($q);$this->total=(int)$db->loadResult();
        $this->offset=$this->total?min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize)):0;
        $q=$db->getQuery(true)
            ->select(['d.id','d.document_number','d.order_id','d.document_type','d.total_cents',
                'd.currency','d.created_at','o.customer_name'])
            ->from($db->quoteName('#__minimarket_order_documents','d'))
            ->join('INNER',$db->quoteName('#__minimarket_orders','o').' ON o.id=d.order_id')
            ->order('d.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $term)$q->where($term);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->documents=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_DOCUMENTS_TITLE'));
        parent::display($tpl);
    }
}
