<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Integrations;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
/** Read-only plugin discovery; service credentials stay in plugin settings. */
final class HtmlView extends BaseHtmlView
{
    public array $plugins=[];
    public bool $canManagePlugins=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canManagePlugins=$user->authorise('core.manage','com_plugins');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['extension_id','name','element','enabled'])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type').'='.$db->quote('plugin'))
            ->where($db->quoteName('folder').'='.$db->quote('minimarket'))
            ->order('name ASC');
        $db->setQuery($q,0,200);
        $this->plugins=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_INTEGRATIONS_TITLE'));
        parent::display($tpl);
    }
}
