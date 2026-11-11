<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Reviews;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $reviews=[];
    public string $statusFilter='pending', $search='', $sort='newest';
    public int $offset=0, $total=0, $pageSize=20;
    public bool $canCreate=false, $canModerate=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $this->canModerate=$user->authorise('core.edit.state','com_minimarket');
        $raw=$app->input->getCmd('filter_status','pending');
        $this->statusFilter=in_array($raw,['all','pending','published','rejected'],true)?$raw:'pending';
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $conditions=[];
        $states=['pending'=>0,'published'=>1,'rejected'=>2];
        if(isset($states[$this->statusFilter]))$conditions[]='r.state='.(int)$states[$this->statusFilter];
        if($this->search!==''){
            $like=$db->quote('%'.$db->escape($this->search,true).'%',false);
            $terms=['r.author_name LIKE '.$like,'r.title LIKE '.$like,'r.body LIKE '.$like,
                'p.title LIKE '.$like];
            if(ctype_digit($this->search))$terms[]='r.id='.(int)$this->search;
            $conditions[]='('.implode(' OR ',$terms).')';
        }
        $base=static function() use($db) {
            return $db->getQuery(true)->from($db->quoteName('#__minimarket_reviews','r'))
                ->join('INNER',$db->quoteName('#__minimarket_products','p').' ON p.id=r.product_id');
        };
        $count=$base()->select('COUNT(*)');
        foreach($conditions as $condition)$count->where($condition);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $this->offset=$this->total===0?0:min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        $q=$base()->select(['r.id','r.product_id','r.author_name','r.rating',
            'r.title','r.body','r.source','r.state','r.created_at','p.title AS product_title'])
            ->order('r.id '.($this->sort==='oldest'?'ASC':'DESC'));
        foreach($conditions as $condition)$q->where($condition);
        $db->setQuery($q,$this->offset,$this->pageSize);
        $this->reviews=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_REVIEWS_TITLE'));
        if($this->canCreate)ToolbarHelper::addNew('reviews.add');
        if($this->canModerate){
            ToolbarHelper::custom('reviews.publish','publish','',Text::_('COM_MINIMARKET_REVIEWS_PUBLISHED'),true);
            ToolbarHelper::custom('reviews.reject','unpublish','',Text::_('COM_MINIMARKET_REVIEWS_REJECTED'),true);
        }
        if($user->authorise('core.options','com_minimarket')||$user->authorise('core.admin','com_minimarket'))
            ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
