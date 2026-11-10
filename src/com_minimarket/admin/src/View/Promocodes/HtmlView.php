<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Promocodes;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $codes=[];
    public bool $canCreate=false;
    public bool $canUpdate=false;
    public function display($tpl=null)
    {
        $app=Factory::getApplication();$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->canCreate=$user->authorise('core.create','com_minimarket');
        $this->canUpdate=$user->authorise('core.edit.state','com_minimarket');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['id','code','amount_cents','state','created_at'])
            ->from($db->quoteName('#__minimarket_promo_codes'))->order('id DESC');
        $db->setQuery($q,0,5000);$this->codes=(array)$db->loadObjectList();
        ToolbarHelper::title(Text::_('COM_MINIMARKET_PROMOCODES_TITLE'));
        parent::display($tpl);
    }
}
