<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;

/**
 * Reverse editor for exactly the same category-attribute rules.
 * View/show all categories, including unpublished, so assignments are not lost.
 */
final class AttributecategoriesField extends FormField
{
    protected $type = 'Attributecategories';

    protected function getInput()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)
            ->select([
                $db->quoteName('id'), $db->quoteName('parent_id'),
                $db->quoteName('title'), $db->quoteName('alias'),
                $db->quoteName('sort_order'), $db->quoteName('state'),
            ])
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($q);
        $categories = CategoryAdminTree::flatten((array) $db->loadObjectList());
        if (!$categories) {
            return '<p>' . htmlspecialchars(Text::_('COM_MINIMARKET_ATTRCAT_EMPTY'),
                ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $attributeId = (int) ($this->form->getValue('id') ?? 0);
        $own = is_array($this->value)
            ? $this->value
            : CategoryAttributes::loadForAttribute($db, $attributeId);
        $hierarchy = CategoryAttributes::loadHierarchy($db);
        $escape = static fn (string $v): string =>
            htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = '<p class="text-muted">' . $escape(Text::_('COM_MINIMARKET_ATTRCAT_HELP')) . '</p>'
            . '<div class="table-responsive"><table class="table table-striped align-middle">'
            . '<thead><tr><th scope="col">' . $escape(Text::_('COM_MINIMARKET_CATEGORIES'))
            . '</th><th scope="col" class="w-25">' . $escape(Text::_('COM_MINIMARKET_CATATTR_OVERRIDE'))
            . '</th></tr></thead><tbody>';

        foreach ($categories as $row) {
            $cat = $row['item'];
            $id = (int) $cat->id;
            $mode = isset($own[$id]) ? (int) $own[$id] : -1;
            if (!in_array($mode, [-1, 0, 1, 2], true)) {
                $mode = -1;
            }
            $parentId = (int) ($cat->parent_id ?? 0);
            $effective = CategoryAttributes::resolve(
                $parentId, $hierarchy['parents'], $hierarchy['rules']
            );
            $sourceId = (int) ($effective[$attributeId] ?? 0);
            $parentModes = CategoryAttributes::resolveModes($parentId, $hierarchy['parents'], $hierarchy['rules']);
            $inherit = $sourceId
                ? Text::sprintf(($parentModes[$attributeId] ?? 1) === 2
                    ? 'COM_MINIMARKET_CATATTR_REQUIRED_FROM_PARENT' : 'COM_MINIMARKET_CATATTR_FROM_PARENT', $sourceId)
                : Text::_('COM_MINIMARKET_CATATTR_NOT_INHERITED');
            $idAttr = $escape($this->id . '_' . $id);
            $name = $escape($this->name . '[' . $id . ']');
            $label = $escape((string) $cat->title);
            if ((int) $cat->state !== 1) {
                $label .= ' <span class="badge bg-secondary">'
                    . $escape(Text::_('JUNPUBLISHED')) . '</span>';
            }
            $indent = min(16, (int) $row['depth']) * 16;
            $html .= '<tr><th scope="row" style="padding-inline-start:' . $indent
                . 'px"><label for="' . $idAttr . '">' . $label
                . '</label><small class="d-block text-muted">' . $escape($inherit)
                . '</small></th><td><select class="form-select form-select-sm" id="' . $idAttr
                . '" name="' . $name . '">';
            foreach ([
                -1 => 'COM_MINIMARKET_CATATTR_MODE_INHERIT',
                1 => 'COM_MINIMARKET_CATATTR_MODE_ENABLE',
                2 => 'COM_MINIMARKET_CATATTR_MODE_REQUIRED',
                0 => 'COM_MINIMARKET_CATATTR_MODE_DISABLE',
            ] as $value => $key) {
                $html .= '<option value="' . $value . '"'
                    . ((int) $value === $mode ? ' selected' : '')
                    . '>' . $escape(Text::_($key)) . '</option>';
            }
            $html .= '</select></td></tr>';
        }
        return $html . '</tbody></table></div>';
    }
}
