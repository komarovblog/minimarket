<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Group;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public $item;
    public $form;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        if (!$this->form) {
            throw new \RuntimeException('MiniMarket group form unavailable');
        }
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->useScript('form.validate')
            ->registerAndUseScript('com_minimarket.group-editor',
                'media/com_minimarket/js/group-editor.js', [], ['defer' => true]);
        ToolbarHelper::title(Text::_('COM_MINIMARKET_GROUP_EDIT'));
        ToolbarHelper::apply('group.apply');
        ToolbarHelper::save('group.save');
        ToolbarHelper::cancel('group.cancel');
        parent::display($tpl);
    }
}
