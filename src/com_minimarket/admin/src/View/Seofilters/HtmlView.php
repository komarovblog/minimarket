<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Seofilters;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $pages=[];
    public bool $canCreate=false;
    public bool $canPublish=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $this->canPublish=$user->authorise('core.edit.state','com_minimarket');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['s.id','s.category_id','s.alias','s.title',
           's.heading','s.state','c.title AS category_title'])
            ->from($db->quoteName('#__minimarket_seo_filters','s'))
            ->join('INNER',$db->quoteName('#__minimarket_categories','c').' ON c.id=s.category_id')
            ->order('s.id DESC');
        $db->setQuery($q,0,1000);
        $this->pages=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_SEOFILTERS_TITLE'));
        parent::display($tpl);
    }
}
