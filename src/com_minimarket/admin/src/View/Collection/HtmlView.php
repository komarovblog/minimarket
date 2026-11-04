<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Collection;
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public $form; public $item;
    public function display($tpl=null)
    {
        $this->form=$this->get('Form'); $this->item=$this->get('Item');
        if (!$this->form) throw new \RuntimeException('MiniMarket collection form unavailable');
        Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('form.validate');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_COLLECTION_EDIT'));
        ToolbarHelper::apply('collection.apply');
        ToolbarHelper::save('collection.save');
        ToolbarHelper::cancel('collection.cancel');
        parent::display($tpl);
    }
}
