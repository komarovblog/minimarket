<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryOrderOptions;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryListVisibility;

/** One compact native Joomla dropdown per global order option. */
final class CategoryorderoptionsField extends FormField
{
    protected $type = 'Categoryorderoptions';

    protected function getInput(): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)->select(['id','title','code','state'])
            ->from($db->quoteName('#__minimarket_order_options'))
            ->order('sort_order ASC,id ASC');
        $db->setQuery($q);
        $options = (array) $db->loadAssocList();
        if (!$options) {
            $url = AdminEntityLinks::add('orderoption');
            return '<p class="text-muted">' . htmlspecialchars(
                Text::_('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_EMPTY'), ENT_QUOTES, 'UTF-8'
            ) . ' <a href="' . $url . '">' .
                htmlspecialchars(Text::_('COM_MINIMARKET_ORDER_OPTION_CREATE'), ENT_QUOTES, 'UTF-8') .
                '</a></p>';
        }
        $categoryId = (int) ($this->form->getValue('id') ?? 0);
        $parentId = (int) ($this->form->getValue('parent_id') ?? 0);
        $hierarchy = CategoryOrderOptions::hierarchy($db);
        $inherited = CategoryOrderOptions::resolveModes($parentId,
            $hierarchy['parents'], $hierarchy['rules']);
        $own = is_array($this->value)
            ? $this->value : CategoryOrderOptions::directWithVisibility($db, $categoryId);
        $esc = static fn(string $s): string =>
            htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<p class="text-muted">' . $esc(Text::_('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_HELP')) .
            '</p><div class="table-responsive mm-category-rules-viewport"><table class="table table-striped table-sm align-middle mm-rules-table mm-rules-table--state"><thead><tr><th>' .
            $esc(Text::_('COM_MINIMARKET_ORDER_OPTIONS')) . '</th><th>' .
            $esc(Text::_('COM_MINIMARKET_CATEGORY_ORDER_OPTION_RULE')) .
            '</th><th>' . $esc(Text::_('JSTATUS')) .
            '</th></tr></thead><tbody>';
        foreach ($options as $option) {
            $id = (int) $option['id'];
            $mode = isset($own[$id]) ? (int) $own[$id] : -1;
            if (!in_array($mode, [-1,0,1,2], true)) $mode = -1;
            $name = $esc($this->name . '[' . $id . ']');
            $fieldId = $esc($this->id . '_' . $id);
            $url = AdminEntityLinks::edit('orderoption', $id);
            $html .= '<tr><th scope="row"><a class="d-inline-block" href="' . $url
                . '">' . $esc((string) $option['title']) . '</a>'
                . '<small class="d-block text-muted">' . $esc((string) $option['code']) . '</small>';
            if (isset($inherited[$id]) && $mode === -1) {
                $html .= '<small class="d-block text-muted">' .
                    $esc(Text::_((int)$inherited[$id] === 2
                        ? 'COM_MINIMARKET_CATEGORY_ORDER_OPTION_INHERITED_REQUIRED'
                        : 'COM_MINIMARKET_CATEGORY_ORDER_OPTION_INHERITED_OPTIONAL')) . '</small>';
            }
            $published = (int) $option['state'] === 1;
            $html .= '</th><td><select class="form-select form-select-sm" name="' . $name
                . '" id="' . $fieldId . '" data-mm-category-list-mode>';
            foreach ([
                -1 => 'COM_MINIMARKET_CATATTR_MODE_INHERIT',
                 0 => 'COM_MINIMARKET_CATATTR_MODE_DISABLE',
                 1 => 'COM_MINIMARKET_CATATTR_MODE_ENABLE',
                 2 => 'COM_MINIMARKET_CATATTR_MODE_REQUIRED',
            ] as $value => $key) {
                $html .= '<option value="' . $value . '"' . ($mode === $value ? ' selected' : '')
                    . '>' . $esc(Text::_($key)) . '</option>';
            }
            $html .= '</select>'
                . '</td><td class="mm-rules-state"><span class="'
                . ($published ? 'text-success' : 'text-muted') . '">'
                . $esc(Text::_($published ? 'JPUBLISHED' : 'JUNPUBLISHED'))
                . '</span></td></tr>';
        }
        return $html . '</tbody></table></div>';
    }
}
