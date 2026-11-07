<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\BulkDeleteGuard;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeValueRows;
use MiniMarket\Component\Minimarket\Administrator\Service\NumericPresetRows;
use MiniMarket\Component\Minimarket\Administrator\Service\TextPresetRows;

final class AttributeModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_minimarket.attribute',
            'attribute',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item) {
            // Keep database value IDs and product scalar rows intact.
            // The editor only exposes Manual / Predefined selection.
            $source = (string) ($item->value_source ?? 'manual');
            $type = (string) ($item->input_type ?? '');
            $mode = (string) ($item->input_mode ?? 'auto');
            $item->value_source_ui = $source === 'legacy'
                ? ((in_array($type, ['list','color'], true)
                    || $mode === 'presets') ? 'preset' : 'manual')
                : $source;
            $item->category_rules = CategoryAttributes::loadForAttribute(
                $this->getDatabase(), (int) ($item->id ?? 0)
            );
            $item->values = AttributeValueRows::formRows(
                $this->getDatabase(), (int) ($item->id ?? 0)
            );
            $item->color_values = $item->values;
            $item->unified_values = AttributeValueRows::formRows(
                $this->getDatabase(), (int) ($item->id ?? 0), (string) ($item->input_type ?? '')
            );
            $item->number_preset_rows = NumericPresetRows::formRows(
                (string) ($item->number_options ?? '')
            );
            $item->text_preset_rows = TextPresetRows::formRows(
                (string) ($item->text_options ?? '')
            );
        }
        return $item;
    }

    /**
     * Attribute + its reverse category assignments have one transaction.
     * Other attributes' category rules must never be erased here.
     */
    public function save($data)
    {
        unset($data['value_source_ui']); // UI-only, never a database column.
        // The Joomla repeatable numeric values are a UI for the existing typed
        // number_options column. No new IDs or SQL tables are introduced.
        if (($data['value_source'] ?? 'legacy') === 'legacy'
            && ($data['input_type'] ?? '') === 'number'
            && ($data['input_mode'] ?? 'auto') === 'presets') {
            try {
                $data['number_options'] = NumericPresetRows::normalize(
                    $data['number_preset_rows'] ?? []
                );
            } catch (\InvalidArgumentException $error) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID'));
                return false;
            }
        }
        unset($data['number_preset_rows']);
        if (($data['value_source'] ?? 'legacy') === 'legacy'
            && ($data['input_type'] ?? '') === 'text'
            && ($data['input_mode'] ?? 'auto') === 'presets') {
            try {
                $data['text_options'] = TextPresetRows::encode(
                    $data['text_preset_rows'] ?? []
                );
            } catch (\InvalidArgumentException | \JsonException $error) {
                $this->setError(Text::_('COM_MINIMARKET_TEXT_PRESETS_INVALID'));
                return false;
            }
        }
        unset($data['text_preset_rows']);
        $hasRules = array_key_exists('category_rules', $data)
            || !empty($data['categories_present']);
        $db = $this->getDatabase();
        try {
            $rules = $hasRules
                ? CategoryAttributes::normalizeRules(
                    $data['category_rules'] ?? [],
                    CategoryAttributes::categoryIds($db)
                ) : [];
        } catch (\InvalidArgumentException $error) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRCAT_INVALID'));
            return false;
        }

        // The native Subform provides rows in drag/drop order. Missing rows
        // with the hidden sentinel mean explicit removal of the final row.
        $source = (string) ($data['value_source'] ?? 'legacy');
        $isColor = (string) ($data['input_type'] ?? '') === 'color' && $source === 'legacy';
        $isPreset = $source === 'preset';
        $hasValues = array_key_exists('values', $data)
            || array_key_exists('color_values', $data)
            || array_key_exists('unified_values', $data)
            || !empty($data['values_present']);
        $oldValues = [];
        $newValues = [];
        if ($hasValues) {
            try {
                $attributeId = (int) ($data['id'] ?? 0);
                $oldValues = AttributeValueRows::load($db, $attributeId);
                $newValues = AttributeValueRows::normalize(
                    ($isPreset ? ($data['unified_values'] ?? [])
                        : ($isColor ? ($data['color_values'] ?? []) : ($data['values'] ?? []))),
                    $oldValues, $isPreset ? (string) ($data['input_type'] ?? '') : null
                );
                if (!$isPreset && !in_array((string) ($data['input_type'] ?? ''), ['list','color'], true)
                    && $newValues !== []) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ROWS_INVALID');
                }
            } catch (\InvalidArgumentException | \JsonException $error) {
                $this->setError(Text::_('COM_MINIMARKET_VALUE_ROWS_INVALID'));
                return false;
            }
        }

        if ($isPreset && $newValues === []) {
            $this->setError(Text::_('COM_MINIMARKET_UNIFIED_VALUES_REQUIRED'));
            return false;
        }
        if ($isPreset && (string) ($data['input_type'] ?? '') === 'number') {
            foreach ($newValues as $row) {
                if (!\MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings::inRange(
                    $row['value_payload'], ($data['number_min'] ?? '') === '' ? null : (string) $data['number_min'],
                    ($data['number_max'] ?? '') === '' ? null : (string) $data['number_max']
                )) {
                    $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID'));
                    return false;
                }
            }
        }
        if ($isColor) {
            foreach ($newValues as $row) {
                if ($row['color_hex'] === '') {
                    $this->setError(Text::_('COM_MINIMARKET_COLOR_INVALID'));
                    return false;
                }
            }
        }

        // Informational attributes cannot define order options.
        unset($data['is_variant']);

        unset($data['category_rules'], $data['categories_present'],
            $data['color_values'], $data['values'], $data['unified_values'], $data['values_present'],
            $data['values_order'], $data['values_order_present']);
        $db->transactionStart();
        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }
            if ($hasRules) {
                $id = (int) $this->getState($this->getName() . '.id');
                CategoryAttributes::replaceForAttribute($db, $id, $rules);
            }
            if ($hasValues && ($isPreset || in_array((string) ($data['input_type'] ?? ''), ['list','color'], true))) {
                $attributeId = (int) $this->getState($this->getName() . '.id');
                AttributeValueRows::apply($db, $attributeId, $oldValues, $newValues);
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $message = $error->getMessage() === 'COM_MINIMARKET_VALUE_ROWS_IN_USE'
                ? 'COM_MINIMARKET_VALUE_ROWS_IN_USE'
                : 'COM_MINIMARKET_ATTRCAT_SAVE_FAILED';
            $this->setError(Text::_($message));
            return false;
        }
    }

    /**
     * Delete only unused attributes. Keep all values and category assignments
     * intact if any selected attribute is in use.
     */
    public function delete(&$pks)
    {
        $ids = BulkDeleteGuard::normalizeIds($pks);
        if (!$ids) {
            $this->setError(Text::_('COM_MINIMARKET_BULK_EMPTY'));
            return false;
        }
        try {
            BulkDeleteGuard::assertNoDependencies($this->getDatabase(), 'attribute', $ids);
        } catch (\RuntimeException $error) {
            $this->setError(Text::_('COM_MINIMARKET_BULK_ATTRIBUTE_IN_USE'));
            return false;
        }
        return parent::delete($pks);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.attribute.data', []);

        return empty($data) ? $this->getItem() : $data;
    }
}
