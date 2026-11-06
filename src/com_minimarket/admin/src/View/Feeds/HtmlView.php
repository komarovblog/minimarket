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
    public function display($tpl=null)
    {
        $user=Factory::getApplication()->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $this->feeds=FeedConfig::all(Factory::getContainer()->get(DatabaseInterface::class));
        ToolbarHelper::title(Text::_('COM_MINIMARKET_FEEDS_TITLE'),'share-alt');
        parent::display($tpl);
    }
}
