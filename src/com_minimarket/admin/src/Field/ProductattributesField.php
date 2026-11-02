<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;

/**
 * One editor for all possible attributes, with client-side category switching.
 * The backend validates again and never trusts JS or hidden inputs.
 */
final class ProductattributesField extends FormField
{
    protected $type = 'Productattributes';

    protected function getInput()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $definitions = ProductAttributes::definitions($db);
        $map = ProductAttributes::categoryMap(CategoryAttributes::loadHierarchy($db), $definitions);
        $categoryId = (int) ($this->form->getValue('category_id') ?? 0);
        $selected = is_array($this->value) ? $this->value : [];
        $escape = static fn (string $text): string =>
            htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $initial = array_fill_keys($map[$categoryId] ?? [], true);
        $used = [];
        foreach ($map as $ids) {
            foreach ($ids as $id) {
                $used[(int) $id] = true;
            }
        }
        // Even when the last category assignment is removed, preserve visible
        // access to previously stored values: do not silently erase them.
        foreach ($selected as $id => $value) {
            if (ctype_digit((string) $id) && isset($definitions[(int) $id])
                && $value !== '' && $value !== null && $value !== []) {
                $used[(int) $id] = true;
            }
        }

        $html = '<div data-mm-product-attributes data-mm-category-map="'
            . $escape((string) json_encode($map, JSON_THROW_ON_ERROR))
            . '"><p class="text-muted">' . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_HELP'))
            . '</p><p data-mm-attr-empty class="text-muted">'
            . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_SELECT_CATEGORY')) . '</p>';

        foreach ($definitions as $id => $def) {
            if (!isset($used[$id])) {
                continue;
            }
            $type = $def['type'];
            $value = $selected[$id] ?? ($selected[(string) $id] ?? '');
            $values = is_array($value) ? array_map('intval', $value) : [(int) $value];
            // List data is stored as value IDs even when the field is single-
            // select; restore the selected option instead of stringifying Array.
            $singleValue = is_array($value) ? (string) (reset($value) ?: '') : (string) $value;
            $name = $this->name . '[' . $id . ']';
            $inputId = $this->id . '_' . $id;
            $isApplicable = isset($initial[$id]);
            $hasStored = $value !== '' && $value !== null && $value !== [];
            $visible = $isApplicable || $hasStored;

            $html .= '<div class="mb-3 border-bottom pb-3' . ($visible ? '' : ' d-none')
                . '" data-mm-attribute-row="' . $id . '">'
                . '<label class="form-label fw-semibold" for="' . $escape($inputId) . '">'
                . $escape($def['title']) . '</label>';
            if ($def['state'] !== 1) {
                $html .= ' <small class="badge bg-secondary">'
                    . $escape(Text::_('JUNPUBLISHED')) . '</small>';
            }
            if ($def['is_variant']) {
                $html .= ' <small class="text-muted">'
                    . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_VARIANT_NOTICE')) . '</small>';
            }
            if ($type === 'list') {
                // An empty dictionary is not an empty product field bug.
                // Point the editor to the source dictionary, without copying
                // values into each individual product.
                if (!$def['values']) {
                    $html .= '<p class="text-muted">'
                        . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_NO_VALUES'))
                        . '</p>';
                }
                $html .= '<p class="small"><a href="' . $escape(Route::_(
                    'index.php?option=com_minimarket&view=values&attribute_id=' . $id
                )) . '">' . $escape(Text::_('COM_MINIMARKET_EDIT_VALUES')) . '</a></p>';
            }
            $html .= '<div class="text-warning small' . ($isApplicable ? ' d-none' : '')
                . '" data-mm-attribute-incompatible>'
                . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INCOMPATIBLE')) . '</div>';

            if ($type === 'list' && $def['is_variant']) {
                $n = 0;
                foreach ($def['values'] as $optionId => $option) {
                    $checked = in_array($optionId, $values, true) && $hasStored;
                    $html .= '<div class="form-check"><input class="form-check-input" type="checkbox"'
                        . ' id="' . $escape($inputId . '_' . $n) . '" name="' . $escape($name . '[]')
                        . '" value="' . $optionId . '"' . ($checked ? ' checked' : '')
                        . '>'
                        . '<label class="form-check-label" for="' . $escape($inputId . '_' . $n) . '">'
                        . $escape($option['title'])
                        . ($option['state'] !== 1
                            ? ' (' . $escape(Text::_('JUNPUBLISHED')) . ')' : '')
                        . '</label></div>';
                    ++$n;
                }
            } elseif ($type === 'list' || $type === 'boolean') {
                $html .= '<select class="form-select" id="' . $escape($inputId)
                    . '" name="' . $escape($name) . '"><option value="">'
                    . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_UNSET')) . '</option>';
                $options = $type === 'boolean'
                    ? ['1' => Text::_('JYES'), '0' => Text::_('JNO')]
                    : $def['values'];
                foreach ($options as $optionId => $option) {
                    $label = $type === 'boolean' ? $option : $option['title'];
                    $selectedOption = (string) $optionId === $singleValue && $hasStored;
                    $html .= '<option value="' . $escape((string) $optionId) . '"'
                        . ($selectedOption ? ' selected' : '')
                        . '>' . $escape((string) $label)
                        . ($type === 'list' && $option['state'] !== 1
                            ? ' (' . $escape(Text::_('JUNPUBLISHED')) . ')' : '')
                        . '</option>';
                }
                $html .= '</select>';
            } else {
                $inputType = $type === 'number' ? 'number' : 'text';
                $html .= '<input class="form-control" type="' . $inputType
                    . '" id="' . $escape($inputId) . '" name="' . $escape($name) . '" value="'
                    . $escape(is_scalar($value) ? (string) $value : '') . '"'
                    . ($type === 'number' ? ' step="0.0001"' : ' maxlength="1024"') . '>';
            }
            $html .= '</div>';
        }

        return $html . '</div>';
    }
}
