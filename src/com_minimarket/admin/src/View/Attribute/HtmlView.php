<?php
namespace MiniMarket\Component\Minimarket\Administrator\View\Attribute;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public array $dictionaryValues = [];

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if (!$this->form) {
            throw new \RuntimeException('MiniMarket attribute form unavailable');
        }

        // Read the dictionary only for an already-saved List attribute.
        // This is the same data used by the standalone Values list, not a copy.
        if ((int) ($this->item->id ?? 0) > 0
            && (string) ($this->item->input_type ?? '') === 'list') {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $q = $db->getQuery(true)
                ->select(['id','title','code','state','sort_order'])
                ->from($db->quoteName('#__minimarket_attribute_values'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->item->id)
                ->order($db->quoteName('sort_order') . ' ASC, ' . $db->quoteName('id') . ' ASC');
            $db->setQuery($q);
            $this->dictionaryValues = (array) $db->loadObjectList();
        }

        Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('form.validate');
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript(
                'com_minimarket.attribute-values-sort',
                'media/com_minimarket/js/attribute-values-sort.js',
                [],
                ['defer' => true]
            );
        ToolbarHelper::title(Text::_('COM_MINIMARKET_ATTRIBUTE_EDIT_TITLE'));
        ToolbarHelper::apply('attribute.apply');
        ToolbarHelper::save('attribute.save');
        ToolbarHelper::cancel('attribute.cancel');

        parent::display($tpl);
    }
}
