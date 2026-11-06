<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Feed;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedConfig;
use MiniMarket\Component\Minimarket\Administrator\Service\FeedSelection;

final class HtmlView extends BaseHtmlView
{
    public array $feed=[];
    public array $settings=[];
    public array $categories=[];
    public array $products=[];
    public array $selected=[];
    public function display($tpl=null)
    {
        $user=Factory::getApplication()->getIdentity();
        if(!$user->authorise('core.manage','com_minimarket')
            || !$user->authorise('core.edit','com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $id=Factory::getApplication()->input->getInt('id');
        $stored=$id>0?FeedConfig::find($db,$id):null;
        if($id>0 && !$stored)throw new \RuntimeException(Text::_('COM_MINIMARKET_FEED_NOT_FOUND'),404);
        [$this->categories,$this->products]=FeedConfig::catalog($db);
        $this->feed=$stored??['id'=>0,'title'=>'','company'=>'',
            'provider'=>'yandex_direct','state'=>0];
        $this->settings=FeedConfig::settings($stored);
        if(!$stored) {
            $this->settings['category_ids']=FeedSelection::visibleCategories($this->categories);
        }
        $this->selected=FeedSelection::productIds($this->categories,$this->products,$this->settings);
        ToolbarHelper::title(Text::_($stored?'COM_MINIMARKET_FEED_EDIT':'COM_MINIMARKET_FEED_NEW'),'share-alt');
        parent::display($tpl);
    }
}
