<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Abandonedcarts;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Site\Service\AbandonedCartStore;
use MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools;

final class HtmlView extends BaseHtmlView
{
    public array $carts=[];
    public int $total=0,$offset=0,$pageSize=20;
    public string $search='',$sort='newest';
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        NativeListTools::importRequest();
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,80);
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $where=[AbandonedCartStore::abandonedPredicate()];
        if($this->search!==''){
            if(ctype_digit($this->search))$where[]='c.user_id='.(int)$this->search;
            else $where[]='1=0'; // Never guess a guest by name or email.
        }
        $base=static fn()=> $db->getQuery(true)
            ->from($db->quoteName('#__minimarket_cart_snapshots','c'));
        $count=$base()->select('COUNT(*)');
        foreach($where as $w)$count->where($w);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        if($this->total>0)$this->offset=min($this->offset,
            (int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        else $this->offset=0;
        $q=$base()->select(['c.cart_key','c.user_id','c.items_json','c.item_units',
            'c.created_at','c.updated_at'])
            ->order('c.updated_at '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($where as $w)$q->where($w);
        $db->setQuery($q,$this->offset,$this->pageSize);
        foreach((array)$db->loadAssocList() as $row){
            $lines=json_decode((string)$row['items_json'],true);
            $row['lines']=is_array($lines)?$lines:[];
            $this->carts[]=$row;
        }
        ToolbarHelper::title(Text::_('COM_MINIMARKET_ABANDONED_CARTS_TITLE'));
        parent::display($tpl);
    }
}
