<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Paymentmethods;
\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\InstalledMethodPlugins;

final class HtmlView extends BaseHtmlView
{
    public $params;
    public array $plugins=[];
    public bool $canEdit=false,$canEditPlugins=false;
    public string $method='';
    public int $zones=0;

    public function display($tpl=null)
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket')){
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $raw=$app->input->getCmd('method','');
        $this->method=in_array($raw,['on_receipt','bank_transfer'],true)?$raw:'';
        $this->canEdit=$user->authorise('core.options','com_minimarket')
            || $user->authorise('core.admin','com_minimarket');
        $this->canEditPlugins=$user->authorise('core.edit','com_plugins')
            || $user->authorise('core.admin','com_plugins');
        $this->params=ComponentHelper::getParams('com_minimarket');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $this->plugins=InstalledMethodPlugins::list($db,'payment');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_PAYMENT_METHODS_TITLE'));
        if($this->canEdit)ToolbarHelper::preferences('com_minimarket');
        parent::display($tpl);
    }
}
