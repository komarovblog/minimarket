<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Review;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $products=[];
    public ?object $review=null;
    public bool $canEdit=false,$canModerate=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $u=$app->getIdentity();
        $id=$app->input->getInt('id',0);
        $this->canEdit=$u->authorise('core.edit','com_minimarket');
        $this->canModerate=$u->authorise('core.edit.state','com_minimarket');
        if($id>0){
            if(!$u->authorise('core.manage','com_minimarket'))
                throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
            $db=Factory::getContainer()->get(DatabaseInterface::class);
            $q=$db->getQuery(true)->select(['r.*','p.title AS product_title'])
                ->from($db->quoteName('#__minimarket_reviews','r'))
                ->join('INNER',$db->quoteName('#__minimarket_products','p').' ON p.id=r.product_id')
                ->where('r.id='.(int)$id);
            $db->setQuery($q);$this->review=$db->loadObject()?:null;
            if(!$this->review)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
            $this->setLayout('default');
            ToolbarHelper::title(Text::_('COM_MINIMARKET_REVIEWS_TITLE').' #'.$id);
        }else{
            if(!$u->authorise('core.create','com_minimarket'))
                throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
            $this->setLayout('create');
            ToolbarHelper::title(Text::_('COM_MINIMARKET_REVIEW_NEW'));
        }
        parent::display($tpl);
    }
}
