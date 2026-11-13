<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Fiscalproviders;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins;

/** Provider discovery, not an invented operational fiscal integration. */
final class HtmlView extends BaseHtmlView
{
    public array $providers=[];
    public bool $canEditPlugins=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $this->providers=InstalledMethodPlugins::list($db,'fiscal');
        $this->canEditPlugins=$user->authorise('core.edit','com_plugins')
            ||$user->authorise('core.admin','com_plugins');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_FISCAL_PROVIDERS_TITLE'));
        parent::display($tpl);
    }
}
