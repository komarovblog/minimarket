<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Import;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public ?array $pending = null;
    public ?array $last = null;
    public ?array $fullPending = null;
    public ?array $fullLast = null;
    public bool $canFull = false;

    public function display($tpl = null)
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.create', 'com_minimarket')
            || !$user->authorise('core.edit', 'com_minimarket')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->canFull = $user->authorise('core.admin','com_minimarket');
        if ($this->canFull) {
            $this->fullPending=Factory::getApplication()->getUserState('com_minimarket.import.full.pending');
            $this->fullLast=Factory::getApplication()->getUserState('com_minimarket.import.full.last');
        }
        $this->pending = Factory::getApplication()->getUserState('com_minimarket.import.pending');
        $this->last = Factory::getApplication()->getUserState('com_minimarket.import.last');
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_minimarket.import-layout',
                'media/com_minimarket/css/import-layout.css');
        ToolbarHelper::title(Text::_('COM_MINIMARKET_IMPORT_TITLE'));
        parent::display($tpl);
    }
}
