<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryListVisibility;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;

/**
 * One three-state control per global attribute, without copying dictionaries.
 * -1 = inherit, 1 = explicitly include, 0 = explicitly exclude.
 */
final class CategoryattributesField extends FormField
{
    protected $type = 'Categoryattributes';

    protected function getInput()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)
            ->select([
                $db->quoteName('id'), $db->quoteName('title'),
                $db->quoteName('code'), $db->quoteName('state'),
            ])
            ->from($db->quoteName('#__minimarket_attributes'))
            ->order($db->quoteName('sort_order') . ' ASC, ' . $db->quoteName('id') . ' ASC');
        $db->setQuery($q);
        $attributes = (array) $db->loadObjectList();

        if (!$attributes) {
            return '<p class="text-muted">' .
                Text::_('COM_MINIMARKET_CATATTR_EMPTY') . '</p>';
        }

        $categoryId = (int) ($this->form->getValue('id') ?? 0);
        $parentId = (int) ($this->form->getValue('parent_id') ?? 0);
        $hierarchy = CategoryAttributes::loadHierarchy($db);
        $parentRules = CategoryAttributes::resolve($parentId, $hierarchy['parents'], $hierarchy['rules']);
        $parentModes = CategoryAttributes::resolveModes($parentId, $hierarchy['parents'], $hierarchy['rules']);

        // On validation failure, honor posted form values instead of silently
        // restoring the last persisted rule selection.
        $ownRules = is_array($this->value)
            ? $this->value
            : CategoryAttributes::loadDirectWithVisibility($db, $categoryId);

        $escape = static fn (string $text): string =>
            htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = '<p class="text-muted">' . $escape(Text::_('COM_MINIMARKET_CATATTR_HELP')) . '</p>'
            . '<div class="table-responsive mm-category-rules-viewport"><table class="table table-striped table-sm align-middle mm-rules-table mm-rules-table--state">'
            . '<thead><tr><th scope="col">' . $escape(Text::_('COM_MINIMARKET_CATATTR_ATTRIBUTE'))
            . '</th><th scope="col">' . $escape(Text::_('COM_MINIMARKET_CATATTR_OVERRIDE'))
            . '</th><th scope="col">' . $escape(Text::_('JSTATUS'))
            . '</th></tr></thead><tbody>';

        foreach ($attributes as $attr) {
            $id = (int) $attr->id;
            $mode = isset($ownRules[$id]) ? (int) $ownRules[$id] : -1;
            if (!in_array($mode, [-1, 0, 1, 2], true)) {
                $mode = -1;
            }
            $sourceId = (int) ($parentRules[$id] ?? 0);
            $inherited = $sourceId > 0
                ? Text::sprintf(($parentModes[$id] ?? 1) === 2
                    ? 'COM_MINIMARKET_CATATTR_REQUIRED_FROM_PARENT' : 'COM_MINIMARKET_CATATTR_FROM_PARENT', $sourceId)
                : Text::_('COM_MINIMARKET_CATATTR_NOT_INHERITED');
            $url = AdminEntityLinks::edit('attribute', $id);
            $label = '<a class="d-inline-block" href="' . $url . '">'
                . $escape((string) $attr->title) . '</a>'
                . '<small class="d-block text-muted">' . $escape((string) $attr->code) . '</small>';
            $published = (int) $attr->state === 1;

            $inputId = $escape($this->id . '_' . $id);
            $inputName = $escape($this->name . '[' . $id . ']');
            $html .= '<tr><th scope="row">' . $label
                . ($sourceId > 0
                    ? '<small class="d-block text-muted">' . $escape($inherited) . '</small>'
                    : '')
                . '</th><td><select class="form-select form-select-sm" id="' . $inputId
                . '" name="' . $inputName . '" data-mm-category-list-mode>';
            foreach ([
                -1 => 'COM_MINIMARKET_CATATTR_MODE_INHERIT',
                 1 => 'COM_MINIMARKET_CATATTR_MODE_ENABLE',
                2 => 'COM_MINIMARKET_CATATTR_MODE_REQUIRED',
                 0 => 'COM_MINIMARKET_CATATTR_MODE_DISABLE',
            ] as $value => $languageKey) {
                $html .= '<option value="' . $value . '"'
                    . ($mode === (int) $value ? ' selected' : '')
                    . '>' . $escape(Text::_($languageKey)) . '</option>';
            }
            $html .= '</select>'
                . '</td><td class="mm-rules-state"><span class="'
                . ($published ? 'text-success' : 'text-muted') . '">'
                . $escape(Text::_($published ? 'JPUBLISHED' : 'JUNPUBLISHED'))
                . '</span></td></tr>';
        }

        $html .= '</tbody></table></div>';
        $html .= '<p class="text-muted">' . $escape(Text::_('COM_MINIMARKET_CATATTR_PARENT_CHANGE')) . '</p>';

        return $html;
    }
}
