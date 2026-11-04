<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryOrderOptions;

/**
 * Reverse category editor: the same category_order_options table and modes
 * as Category → Options. No separate relationship store.
 */
final class OptioncategoriesField extends FormField
{
    protected $type = 'Optioncategories';

    protected function getInput(): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)
            ->select(['id', 'parent_id', 'title', 'alias', 'sort_order', 'state'])
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q);
        $categories = CategoryAdminTree::flatten((array) $db->loadObjectList());
        $esc = static fn(string $value): string =>
            htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (!$categories) {
            return '<p class="text-muted">' .
                $esc(Text::_('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_EMPTY_CATEGORIES')) . '</p>';
        }
        $optionId = (int) ($this->form->getValue('id') ?? 0);
        $own = is_array($this->value)
            ? $this->value : CategoryOrderOptions::directForOption($db, $optionId);
        $hierarchy = CategoryOrderOptions::hierarchy($db);
        $html = '<p class="text-muted">' .
            $esc(Text::_('COM_MINIMARKET_ORDER_OPTION_CATEGORIES_HELP')) .
            '</p><table class="table table-striped table-sm align-middle mm-rules-table">' .
            '<thead><tr><th scope="col">' . $esc(Text::_('COM_MINIMARKET_CATEGORIES')) .
            '</th><th scope="col">' .
            $esc(Text::_('COM_MINIMARKET_CATEGORY_ORDER_OPTION_RULE')) .
            '</th></tr></thead><tbody>';
        foreach ($categories as $row) {
            $cat = $row['item'];
            $categoryId = (int) $cat->id;
            $mode = (int) ($own[$categoryId] ?? -1);
            if (!in_array($mode, [-1,0,1,2], true)) $mode = -1;
            $parentId = (int) ($cat->parent_id ?? 0);
            $inherited = CategoryOrderOptions::resolveModes(
                $parentId, $hierarchy['parents'], $hierarchy['rules']
            );
            $label = str_repeat('— ', min(12, (int) $row['depth'])) .
                (string) $cat->title;
            $id = $esc($this->id . '_' . $categoryId);
            $name = $esc($this->name . '[' . $categoryId . ']');
            $html .= '<tr><th scope="row"><label for="' . $id . '">' .
                $esc($label) . '</label>';
            if ((int) $cat->state !== 1) {
                $html .= '<small class="d-block text-muted">' .
                    $esc(Text::_('JUNPUBLISHED')) . '</small>';
            }
            if ($mode === -1 && isset($inherited[$optionId])) {
                $html .= '<small class="d-block text-muted">' .
                    $esc(Text::_((int) $inherited[$optionId] === 2
                        ? 'COM_MINIMARKET_CATEGORY_ORDER_OPTION_INHERITED_REQUIRED'
                        : 'COM_MINIMARKET_CATEGORY_ORDER_OPTION_INHERITED_OPTIONAL')) .
                    '</small>';
            }
            $html .= '</th><td><select class="form-select form-select-sm" name="' .
                $name . '" id="' . $id . '">';
            foreach ([
                -1 => 'COM_MINIMARKET_CATATTR_MODE_INHERIT',
                 0 => 'COM_MINIMARKET_CATATTR_MODE_DISABLE',
                 1 => 'COM_MINIMARKET_CATATTR_MODE_ENABLE',
                 2 => 'COM_MINIMARKET_CATATTR_MODE_REQUIRED',
            ] as $value => $languageKey) {
                $html .= '<option value="' . $value . '"' .
                    ($mode === $value ? ' selected' : '') . '>' .
                    $esc(Text::_($languageKey)) . '</option>';
            }
            $html .= '</select></td></tr>';
        }
        return $html . '</tbody></table>';
    }
}
