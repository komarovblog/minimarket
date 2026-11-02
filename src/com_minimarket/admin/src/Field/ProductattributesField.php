<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Form\Field\EditorField;
use Joomla\CMS\Form\Field\MediaField;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\TextPresetRows;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings;
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
        $required = array_fill_keys(CategoryAttributes::requiredForCategory(
            $categoryId, $hierarchy['parents'], $hierarchy['rules']
        ), true);
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
                . $escape($def['title'])
                . (isset($required[$id]) ? ' <span class="text-danger" aria-label="'
                    . $escape(Text::_('COM_MINIMARKET_CATATTR_MODE_REQUIRED'))
                    . '">*</span>' : '') . '</label>';
            if ($def['state'] !== 1) {
                $html .= ' <small class="badge bg-secondary">'
                    . $escape(Text::_('JUNPUBLISHED')) . '</small>';
            }
            if (in_array($type, ['list','color'], true) && $def['is_multiple']) {
                $html .= ' <small class="text-muted">'
                    . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_VARIANT_NOTICE')) . '</small>';
            }
            if (in_array($type, ['list','color'], true)) {
                // An empty dictionary is not an empty product field bug.
                // Point the editor to the source dictionary, without copying
                // values into each individual product.
                if (!$def['values']) {
                    $html .= '<p class="text-muted">'
                        . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_NO_VALUES'))
                        . '</p>';
                }
                $html .= '<p class="small"><a href="' . $escape(Route::_(
                    'index.php?option=com_minimarket&view=attribute&layout=edit&id=' . $id
                )) . '">' . $escape(Text::_('COM_MINIMARKET_EDIT_VALUES')) . '</a></p>';
            }
            $html .= '<div class="text-warning small' . ($isApplicable ? ' d-none' : '')
                . '" data-mm-attribute-incompatible>'
                . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INCOMPATIBLE')) . '</div>';

            $mode = (string) ($def['input_mode'] ?? 'auto');
            if (in_array($type, ['list','color'], true)) {
                $mode = $mode === 'auto'
                    ? ($def['is_multiple'] ? 'checkboxes' : 'dropdown') : $mode;
                if ($mode === 'checkboxes' || $mode === 'radio') {
                    $counter = 0;
                    if ($mode === 'radio') {
                        $html .= '<div class="form-check"><input class="form-check-input"'
                            . ' type="radio" id="' . $escape($inputId . '_unset')
                            . '" name="' . $escape($name) . '" value=""'
                            . (!$hasStored ? ' checked' : '') . '>'
                            . '<label class="form-check-label" for="'
                            . $escape($inputId . '_unset') . '">'
                            . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_UNSET'))
                            . '</label></div>';
                    }
                    foreach ($def['values'] as $optionId => $option) {
                        $multiple = $mode === 'checkboxes';
                        $checked = in_array($optionId, $values, true) && $hasStored;
                        $html .= '<div class="form-check"><input class="form-check-input" type="'
                            . ($multiple ? 'checkbox' : 'radio')
                            . '" id="' . $escape($inputId . '_' . $counter) . '" name="'
                            . $escape($name . ($multiple ? '[]' : '')) . '" value="' . $optionId . '"'
                            . ($checked ? ' checked' : '') . '>'
                            . '<label class="form-check-label" for="'
                            . $escape($inputId . '_' . $counter) . '">'
                            . (!empty($option['sample_image'])
                                ? '<img src="' . $escape(\Joomla\CMS\Uri\Uri::root() . $option['sample_image'])
                                    . '" alt="" loading="lazy" style="width:24px;height:24px;object-fit:cover;vertical-align:middle;margin-right:.4em">' : '')
                            . ($type === 'color' && empty($option['sample_image']) && preg_match('/^#[a-fA-F0-9]{6}$/D', (string) ($option['color_hex'] ?? ''))
                                ? '<span aria-hidden="true" style="display:inline-block;width:1.25em;height:1.25em;border:1px solid currentColor;vertical-align:middle;margin-right:.4em;background:'
                                    . $escape($option['color_hex']) . '"></span>' : '')
                            . $escape($option['title'])
                            . ($option['state'] !== 1
                                ? ' (' . $escape(Text::_('JUNPUBLISHED')) . ')' : '')
                            . '</label></div>';
                        ++$counter;
                    }
                } else {
                    $multiple = $def['is_multiple'];
                    $html .= '<select class="form-select" id="' . $escape($inputId)
                        . '" name="' . $escape($name . ($multiple ? '[]' : '')) . '"'
                        . ($multiple ? ' multiple size="5"' : '') . '>';
                    if (!$multiple) {
                        $html .= '<option value="">'
                            . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_UNSET')) . '</option>';
                    }
                    foreach ($def['values'] as $optionId => $option) {
                        $checked = in_array($optionId, $values, true) && $hasStored;
                        $html .= '<option value="' . $optionId . '"'
                            . ($checked ? ' selected' : '') . '>'
                            . $escape($option['title'])
                            . ($option['state'] !== 1
                                ? ' (' . $escape(Text::_('JUNPUBLISHED')) . ')' : '')
                            . '</option>';
                    }
                    $html .= '</select>';
                }
            } elseif ($type === 'text' && $mode === 'presets') {
                $presets = TextPresetRows::rows((string) ($def['text_options'] ?? ''));
                $selectedTexts = is_array($value) ? array_map('strval', $value)
                    : ($hasStored ? [(string) $value] : []);
                if ($def['is_multiple']) {
                    foreach ($presets as $i => $preset) {
                        $choice = $preset['value'];
                        $checked = in_array($choice, $selectedTexts, true);
                        $html .= '<div class="form-check"><input type="checkbox" class="form-check-input" id="'
                            . $escape($inputId . '_' . $i) . '" name="' . $escape($name . '[]')
                            . '" value="' . $escape($choice) . '"' . ($checked ? ' checked' : '') . '>'
                            . '<label class="form-check-label" for="' . $escape($inputId . '_' . $i) . '">';
                        if ($preset['image'] !== '') {
                            $html .= '<img src="' . $escape(\Joomla\CMS\Uri\Uri::root() . $preset['image'])
                                . '" alt="" loading="lazy" style="width:32px;height:32px;object-fit:cover;margin-right:.4em">';
                        }
                        $html .= $escape($choice) . '</label></div>';
                    }
                } else {
                    $html .= '<select class="form-select" id="' . $escape($inputId)
                        . '" name="' . $escape($name) . '"><option value="">'
                        . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_UNSET')) . '</option>';
                    foreach ($presets as $preset) {
                        $choice = $preset['value'];
                        $html .= '<option value="' . $escape($choice) . '"'
                            . (in_array($choice, $selectedTexts, true) ? ' selected' : '') . '>'
                            . $escape($choice) . '</option>';
                    }
                    $html .= '</select>';
                }
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
            } elseif ($type === 'number' && $mode === 'presets') {
                $html .= '<select class="form-select" id="' . $escape($inputId)
                    . '" name="' . $escape($name) . '"><option value="">'
                    . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_UNSET')) . '</option>';
                $numbers = AttributeSettings::numericOptions((string) ($def['number_options'] ?? ''));
                // An old number that no longer belongs to the preset set remains
                // visible rather than silently disappearing from the editor.
                if ($hasStored && is_scalar($value)
                    && !in_array(AttributeSettings::canonicalDecimal((string) $value), $numbers, true)) {
                    $html .= '<option value="' . $escape((string) $value) . '" selected>'
                        . $escape((string) $value) . ' ('
                        . $escape(Text::_('COM_MINIMARKET_PRODUCT_ATTRIBUTES_INCOMPATIBLE')) . ')</option>';
                }
                foreach ($numbers as $n) {
                    $html .= '<option value="' . $escape($n) . '"'
                        . ($hasStored && is_scalar($value)
                            && AttributeSettings::canonicalDecimal((string) $value) === $n
                            ? ' selected' : '') . '>'
                        . $escape($n) . '</option>';
                }
                $html .= '</select>';
            } elseif ($type === 'textarea') {
                $html .= '<textarea class="form-control" rows="5" maxlength="10000"'
                    . ' id="' . $escape($inputId) . '" name="' . $escape($name) . '">'
                    . $escape(is_scalar($value) ? (string) $value : '') . '</textarea>';
            } else {
                $inputType = match ($type) {
                    'number' => 'number', 'date' => 'date', 'url' => 'url', default => 'text'
                };
                $extra = '';
                if ($type === 'number') {
                    $extra = ' step="' . $escape((string) ($def['number_step'] ?? '0.0001')) . '"';
                    if (($def['number_min'] ?? null) !== null) {
                        $extra .= ' min="' . $escape((string) $def['number_min']) . '"';
                    }
                    if (($def['number_max'] ?? null) !== null) {
                        $extra .= ' max="' . $escape((string) $def['number_max']) . '"';
                    }
                } elseif ($type === 'text' || $type === 'url') {
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
