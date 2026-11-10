<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;
use Joomla\CMS\Form\Field\EditorField;
use Joomla\CMS\Form\Field\MediaField;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;
use Joomla\CMS\Editor\Editor;

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
        $hierarchy = CategoryAttributes::loadHierarchy($db);
        $map = ProductAttributes::categoryMap($hierarchy, $definitions);
        $categoryId = (int) ($this->form->getValue('category_id') ?? 0);
        $selected = is_array($this->value) ? $this->value : [];
        $escape = static fn (string $text): string =>
            htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $initial = array_fill_keys($map[$categoryId] ?? [], true);
        $requiredByCategory = [];
        foreach (array_keys($map) as $cat) {
            $requiredByCategory[(int) $cat] = CategoryAttributes::requiredForCategory(
                (int) $cat, $hierarchy['parents'], $hierarchy['rules']
            );
        }
        $required = array_fill_keys($requiredByCategory[$categoryId] ?? [], true);
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
            . '" data-mm-category-required="'
            . $escape((string) json_encode($requiredByCategory, JSON_THROW_ON_ERROR))
            . '"><p class="text-muted">' . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_HELP'))
            . '</p><p data-mm-attr-empty class="text-muted">'
            . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_SELECT_CATEGORY')) . '</p>';

        foreach ($definitions as $id => $def) {
            if (!isset($used[$id])) {
                continue;
            }
            $type = $def['type'];
            $source = (string) ($def['value_source'] ?? 'manual');
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
                . $escape($def['title'])
                . ' <span class="text-danger'
                . (isset($required[$id]) ? '' : ' d-none')
                . '" data-mm-required-indicator aria-label="'
                . $escape(Text::_('COM_MINIMARKET_CATATTR_MODE_REQUIRED'))
                . '">*</span></label>';
            if ($def['state'] !== 1) {
                $html .= ' <small class="badge bg-secondary">'
                    . $escape(Text::_('JUNPUBLISHED')) . '</small>';
            }
            if ($source === 'preset') {
                // An empty dictionary is not an empty product field bug.
                // Point the editor to the source dictionary, without copying
                // values into each individual product.
                if (!$def['values']) {
                    $html .= '<p class="text-muted">'
                        . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_NO_VALUES'))
                        . '</p>';
                }
            }
            // Every attribute type has the SAME direct edit link. Values and
            // presets belong to the attribute editor, never to the product.
            $html .= '<p class="small"><a href="' . AdminEntityLinks::edit('attribute', $id) . '">'
                . $escape(Text::_('COM_MINIMARKET_EDIT_ATTRIBUTE')) . '</a></p>';
            $html .= '<div class="text-warning small' . ($isApplicable ? ' d-none' : '')
                . '" data-mm-attribute-incompatible>'
                . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INCOMPATIBLE')) . '</div>';

            if ($source === 'preset') {
                $html .= '<select class="form-select" id="' . $escape($inputId)
                    . '" name="' . $escape($name) . '"><option value="">'
                    . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_UNSET')) . '</option>';
                foreach ($def['values'] as $optionId => $option) {
                    $checked = $singleValue === (string) $optionId && $hasStored;
                    $label = (string) $option['title'];
                    if ($type === 'color' && ($def['color_display'] ?? 'both') === 'swatch') {
                        $label = (string) ($option['color_hex'] ?: $option['title']);
                    }
                    $html .= '<option value="' . (int) $optionId . '"'
                        . ($checked ? ' selected' : '') . '>' . $escape($label) . '</option>';
                }
                $html .= '</select>';
            } elseif ($source === 'manual' && $type === 'color') {
                $color = preg_match('/^#[A-Fa-f0-9]{6}$/D', $singleValue)
                    ? strtoupper($singleValue) : '';
                $html .= '<div class="d-flex gap-2 align-items-center" data-mm-color-pair>'
                    . '<input type="color" class="form-control form-control-color"'
                    . ' value="' . $escape($color === '' ? '#000000' : $color)
                    . '" aria-label="' . $escape($def['title']) . '" data-mm-color-picker>'
                    . '<input type="text" class="form-control" name="' . $escape($name) . '"'
                    . ' value="' . $escape($color) . '" placeholder="#RRGGBB"'
                    . ' pattern="#[a-fA-F0-9]{6}" data-mm-color-text></div>';
            } elseif ($type === 'editor' || $type === 'document') {
                $class = $type === 'editor' ? EditorField::class : MediaField::class;
                $field = new $class($this->form);
                $fieldXml = new \SimpleXMLElement('<field name="' . (int) $id
                    . '" type="' . ($type === 'editor' ? 'editor' : 'media')
                    . '" label="' . $escape($def['title'])
                    . '" ' . ($type === 'editor'
                        ? 'filter="safehtml" buttons="true"'
                        : 'directory="images" types="documents" preview="false" filter="string"')
                    . '/>');
                $field->setup($fieldXml, is_scalar($value) ? (string) $value : '', 'attribute_values');
                $html .= $field->getInput();
            } elseif ($type === 'boolean') {
                $html .= '<select class="form-select" id="' . $escape($inputId)
                    . '" name="' . $escape($name) . '"><option value="">'
                    . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_UNSET')) . '</option>';
                foreach (['1' => Text::_('JYES'), '0' => Text::_('JNO')] as $key => $label) {
                    $html .= '<option value="' . $key . '"'
                        . ($singleValue === (string) $key && $hasStored ? ' selected' : '') . '>'
                        . $escape($label) . '</option>';
                }
                $html .= '</select>';
            } else {
                $inputType = $type === 'number' ? 'number' : 'text';
                $extra = '';
                if ($type === 'number') {
                    $extra = ' step="' . $escape((string) ($def['number_step'] ?? '0.0001')) . '"';
                    if (($def['number_min'] ?? null) !== null) {
                        $extra .= ' min="' . $escape((string) $def['number_min']) . '"';
                    }
                    if (($def['number_max'] ?? null) !== null) {
                        $extra .= ' max="' . $escape((string) $def['number_max']) . '"';
                    }
                } elseif ($type === 'text') {
                    $extra = ' maxlength="1024"';
                }
                $html .= '<input class="form-control" type="' . $inputType
                    . '" id="' . $escape($inputId) . '" name="' . $escape($name) . '" value="'
                    . $escape($type === 'text' ? $singleValue : (is_scalar($value) ? (string) $value : '')) . '"' . $extra . '>';
            }
            if ($type === 'number' && trim((string) ($def['number_unit'] ?? '')) !== '') {
                $html .= ' <small class="text-muted">'
                    . $escape((string) $def['number_unit']) . '</small>';
            }
            $html .= '</div>';
        }

        return $html . '</div>';
    }
}
