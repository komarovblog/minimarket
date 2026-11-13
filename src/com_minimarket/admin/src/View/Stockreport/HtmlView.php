<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Stockreport;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools;
use MiniMarket\Component\Minimarket\Administrator\Service\StockReport;

final class HtmlView extends BaseHtmlView
{
    public array $rows=[];
    public int $total=0,$offset=0,$pageSize=20,$period=30;
    public string $search='',$stockFilter='',$abcFilter='',$xyzFilter='',$sort='title';
    public bool $inventoryEnabled=true;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        NativeListTools::importRequest();
        $this->inventoryEnabled=(int)ComponentHelper::getParams('com_minimarket')->get('inventory_enabled',1)===1;
        $this->period=$app->input->getInt('filter_period',30)===90?90:30;
        $this->search=mb_strtolower(mb_substr(trim($app->input->getString('filter_search','')),0,100));
        $status=$app->input->getCmd('filter_stock','');
        $this->stockFilter=in_array($status,['out','low','reserved','no_demand'],true)?$status:'';
        $abc=$app->input->getCmd('filter_abc','');
        $this->abcFilter=in_array($abc,['A','B','C','none'],true)?$abc:'';
        $xyz=$app->input->getCmd('filter_xyz','');
        $this->xyzFilter=in_array($xyz,['X','Y','Z','none'],true)?$xyz:'';
        $sort=$app->input->getCmd('filter_sort','title');
        $this->sort=in_array($sort,['title','cover','sold','available'],true)?$sort:'title';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        // ABC is assigned over the entire eligible catalog, not just the
        // currently searched page or the product selected by the operator.
        $all=StockReport::load($db,$this->period);
        $selected=array_filter($all,function(array $r):bool{
            if($this->search!==''
                &&!str_contains(mb_strtolower((string)$r['title']),$this->search)
                &&!str_contains(mb_strtolower((string)$r['sku']),$this->search))return false;
            if($this->abcFilter!=='' && $r['abc']!==($this->abcFilter==='none'?'':$this->abcFilter))return false;
            if($this->xyzFilter!=='' && $r['xyz']!==($this->xyzFilter==='none'?'':$this->xyzFilter))return false;
            return match($this->stockFilter){
                'out'=>$r['available']===0,
                'low'=>$r['cover_days']!==null&&$r['cover_days']<=7,
                'reserved'=>$r['held']>0,
                'no_demand'=>$r['sold']===0,
                default=>true
            };
        });
        $selected=array_values($selected);
        usort($selected,function(array $a,array $b):int{
            $compare=match($this->sort){
                'sold'=>$b['sold']<=>$a['sold'],
                'available'=>$a['available']<=>$b['available'],
                'cover'=>($a['cover_days']??INF)<=>($b['cover_days']??INF),
                default=>strnatcasecmp((string)$a['title'],(string)$b['title'])
            };
            return $compare?:strcmp((string)$a['key'],(string)$b['key']);
        });
        $this->total=count($selected);
        $this->offset=$this->total?min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize)):0;
        $this->rows=array_slice($selected,$this->offset,$this->pageSize);
        ToolbarHelper::title(Text::_('COM_MINIMARKET_STOCK_REPORT_TITLE'));
        parent::display($tpl);
    }
}
