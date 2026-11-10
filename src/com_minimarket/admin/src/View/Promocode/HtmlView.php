<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Promocode;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public function display($tpl=null)
    {
        if(!Factory::getApplication()->getIdentity()->authorise('core.create','com_minimarket'))
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        ToolbarHelper::title(Text::_('COM_MINIMARKET_PROMOCODES_NEW'));
        parent::display($tpl);
    }
}
