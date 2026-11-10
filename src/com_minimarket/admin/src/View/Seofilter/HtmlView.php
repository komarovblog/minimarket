<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Seofilter;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $categories=[];
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        if(!$app->getIdentity()->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['id','title'])->from($db->quoteName('#__minimarket_categories'))
            ->where('state=1')->order('title ASC,id ASC');
        $db->setQuery($q,0,5000);$this->categories=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_SEOFILTER_CREATE'));
        parent::display($tpl);
    }
}
