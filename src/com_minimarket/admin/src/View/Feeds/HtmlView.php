<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Feeds;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;

final class HtmlView extends BaseHtmlView
{
    public array $feeds=[];
    public string $search='', $statusFilter='all', $providerFilter='', $sort='newest';
    public int $total=0, $offset=0, $pageSize=20;
    public bool $canCreate=false;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.edit','com_minimarket');
        $this->search=mb_substr(trim($app->input->getString('filter_search','')),0,100);
        $status=$app->input->getCmd('filter_status','all');
        $this->statusFilter=in_array($status,['all','published','unpublished'],true)?$status:'all';
        $provider=$app->input->getCmd('filter_provider','');
        $this->providerFilter=isset(FeedConfig::PROVIDERS[$provider])?$provider:'';
        $this->sort=$app->input->getCmd('filter_sort','newest')==='oldest'?'oldest':'newest';
        $limit=$app->input->getInt('limit',20);
        $this->pageSize=in_array($limit,[20,50,100],true)?$limit:20;
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $all=FeedConfig::all(Factory::getContainer()->get(DatabaseInterface::class));
        $filtered=array_values(array_filter($all,function($feed){
            if($this->providerFilter!=='' && (string)($feed['provider']??'')!==$this->providerFilter)return false;
            if($this->statusFilter!=='all'
                && (int)($feed['state']??0)!==($this->statusFilter==='published'?1:0))return false;
            if($this->search!==''){
                $term=mb_strtolower($this->search);
                $haystack=mb_strtolower((string)($feed['title']??'').' '.(string)($feed['provider']??''));
                if(!str_contains($haystack,$term))return false;
            }
            return true;
        }));
        usort($filtered,fn($a,$b)=>$this->sort==='oldest'
            ?((int)$a['id']<=>(int)$b['id'])
            :((int)$b['id']<=>(int)$a['id']));
        $this->total=count($filtered);
        $this->offset=$this->total===0?0:min($this->offset,(int)(floor(($this->total-1)/$this->pageSize)*$this->pageSize));
        $this->feeds=array_slice($filtered,$this->offset,$this->pageSize);
        ToolbarHelper::title(Text::_('COM_MINIMARKET_FEEDS_TITLE'),'share-alt');
        if($this->canCreate)ToolbarHelper::addNew('feed.add');
        if($user->authorise('core.options','com_minimarket')||$user->authorise('core.admin','com_minimarket'))
            ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
