<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Reviews;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** Moderation queue and operator-created reviews. */
final class HtmlView extends BaseHtmlView
{
    public array $reviews=[];
    public string $statusFilter='pending';
    public int $offset=0;
    public int $total=0;
    public int $pageSize=50;
    public bool $canCreate=false;
    public bool $canModerate=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $this->canModerate=$user->authorise('core.edit.state','com_minimarket');
        $raw=$app->input->getCmd('filter_status','pending');
        $this->statusFilter=in_array($raw,['all','pending','published','rejected'],true)?$raw:'pending';
        $states=['pending'=>0,'published'=>1,'rejected'=>2];
        $this->offset=max(0,min(1000000,$app->input->getInt('limitstart',0)));
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $count=$db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_reviews','r'));
        if(isset($states[$this->statusFilter]))$count->where('r.state='.(int)$states[$this->statusFilter]);
        $db->setQuery($count);$this->total=(int)$db->loadResult();
        $query=$db->getQuery(true)->select(['r.id','r.product_id','r.author_name','r.rating',
              'r.title','r.body','r.source','r.state','r.created_at','p.title AS product_title'])
            ->from($db->quoteName('#__minimarket_reviews','r'))
            ->join('INNER',$db->quoteName('#__minimarket_products','p').' ON p.id=r.product_id')
            ->order('r.id DESC');
        if(isset($states[$this->statusFilter]))$query->where('r.state='.(int)$states[$this->statusFilter]);
        $db->setQuery($query,$this->offset,$this->pageSize);
        $this->reviews=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_REVIEWS_TITLE'));
        parent::display($tpl);
    }
}
