<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings;
use MiniMarket\Component\Minimarket\Administrator\Service\TextPresetRows;

final class AttributeTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_attributes', 'id', $db);
        // Joomla bulk publish expects the standard 'published' alias.
        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        $this->title = trim((string) $this->title);
        $this->code = strtolower(trim((string) $this->code));
        $this->input_type = (string) $this->input_type;

        if ($this->title === '' || mb_strlen($this->title) > 255) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TITLE_INVALID'));

            return false;
        }

        if (!preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $this->code)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_CODE_INVALID'));

            return false;
        }

        if ((int) ($this->id ?? 0) === 0
            && !\in_array($this->input_type, AttributeSettings::NEW_TYPES, true)) {
            $this->setError(Text::_('COM_MINIMARKET_LEGACY_TYPE_NEW_FORBIDDEN'));
            return false;
        }

        if (!\in_array($this->input_type, AttributeSettings::TYPES, true)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE_INVALID'));

            return false;
        }

        // When switching from List to another type the hidden List-specific
        // cardinality control must not leave an invalid flag behind.
        if (($this->value_source ?? 'legacy') === 'legacy'
            && !in_array($this->input_type, ['list','color'], true)
            && !($this->input_type === 'text' && $this->input_mode === 'presets')) {
            $this->is_multiple = 0;
        }

        foreach (['is_multiple', 'is_filterable', 'state'] as $field) {
            if (!\in_array((int) $this->$field, [0, 1], true)) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_FLAGS_INVALID'));

                return false;
            }

            $this->$field = (int) $this->$field;
        }

        $settingError = AttributeSettings::normalize($this);
        if ($settingError !== null) {
            $this->setError(Text::_($settingError));
            return false;
        }

        $sort = trim((string) $this->sort_order);

        if (!preg_match('/^(0|[1-9][0-9]*)$/D', $sort)
            || strlen($sort) > 10
            || (strlen($sort) === 10 && strcmp($sort, '4294967295') > 0)
        ) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_SORT_INVALID'));

            return false;
        }

        $this->sort_order = (int) $sort;

        $db = $this->getDbo();
        // Switching an existing free-number attribute to preset-only input
        // must not strand saved values outside the newly allowed set.
        if ((int) $this->id > 0 && ($this->value_source ?? 'legacy') === 'legacy'
            && $this->input_type === 'number' && $this->input_mode === 'presets') {
            $numbers = AttributeSettings::numericOptions($this->number_options);
            $presetQuery = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_product_attribute_scalars'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id)
                ->where($db->quoteName('value_number') . ' IS NOT NULL')
                ->where($db->quoteName('value_number') . ' NOT IN (' . implode(',', $numbers) . ')');
            $db->setQuery($presetQuery);
            if ((int) $db->loadResult() > 0) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_PRESET_IN_USE'));
                return false;
            }
        }

        if ((int) $this->id > 0 && ($this->value_source ?? 'legacy') === 'legacy'
            && $this->input_type === 'text' && $this->input_mode === 'presets') {
            $items = TextPresetRows::values((string) $this->text_options);
            $q = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_product_attribute_scalars'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id)
                ->where($db->quoteName('value_text') . ' IS NOT NULL');
            if ($items) {
                $q->where($db->quoteName('value_text') . ' NOT IN (' .
                    implode(',', array_map([$db,'quote'], $items)) . ')');
            }
            $db->setQuery($q);
            if ((int) $db->loadResult() > 0) {
                $this->setError(Text::_('COM_MINIMARKET_TEXT_PRESETS_IN_USE'));
                return false;
            }
            // Removed preset values must also be checked against multi-selections.
            $q = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_product_attribute_multi_values'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id);
            if ($items) {
                $q->where($db->quoteName('value_text') . ' NOT IN (' .
                    implode(',', array_map([$db,'quote'], $items)) . ')');
            }
            $db->setQuery($q);
            if ((int) $db->loadResult() > 0) {
                $this->setError(Text::_('COM_MINIMARKET_TEXT_PRESETS_IN_USE'));
                return false;
            }
        }

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_attributes'))
            ->where($db->quoteName('code') . ' = ' . $db->quote($this->code))
            ->where($db->quoteName('id') . ' <> ' . (int) $this->id);
        $db->setQuery($query);

        if ((int) $db->loadResult() > 0) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_CODE_DUPLICATE'));

            return false;
        }

        // Do not silently discard additional product choices when an
        // administrator changes a multi-select list to single-select.
        if ((int) $this->id > 0 && (($this->value_source ?? 'legacy') !== 'manual')
            && $this->is_multiple === 0) {
            $multipleQuery = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_product_attribute_options'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id)
                ->group($db->quoteName('product_id'))
                ->having('COUNT(*) > 1');
            $db->setQuery($multipleQuery);
            if ((int) $db->loadResult() > 0) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_MULTIPLE_IN_USE'));
                return false;
            }
        }

        // Changing Text presets to single or manual must not erase multiple selections.
        if ((int) $this->id > 0 && $this->is_multiple === 0) {
            $q = $db->getQuery(true)->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_product_attribute_multi_values'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id)
                ->group($db->quoteName('product_id'))->having('COUNT(*) > 1');
            $db->setQuery($q);
            if ((int) $db->loadResult() > 0) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_MULTIPLE_IN_USE'));
                return false;
            }
        }

        // A source change is not an implicit migration of product values.
        // Legacy List/Color => preset is safe because both use the same value IDs.
        if ((int) $this->id > 0) {
            $q = $db->getQuery(true)->select('value_source')
                ->from($db->quoteName('#__minimarket_attributes'))
                ->where($db->quoteName('id') . ' = ' . (int) $this->id);
            $db->setQuery($q);
            $oldSource = (string) ($db->loadResult() ?? 'legacy');
            if (!in_array($oldSource, ['legacy','manual','preset'], true)) {
                $oldSource = 'legacy';
            }
            $q = $db->getQuery(true)->select('input_type')
                ->from($db->quoteName('#__minimarket_attributes'))
                ->where($db->quoteName('id') . ' = ' . (int) $this->id);
            $db->setQuery($q);
            $oldType = (string) ($db->loadResult() ?? '');
            $newSource = (string) ($this->value_source ?? 'legacy');
            if ($oldSource !== $newSource) {
                $safe = $oldSource === 'legacy' && $newSource === 'preset'
                    && in_array($oldType, ['list','color'], true)
                    && $oldType === $this->input_type;
                if (!$safe) {
                    foreach (['#__minimarket_product_attribute_scalars',
                        '#__minimarket_product_attribute_options',
                        '#__minimarket_product_attribute_multi_values',
                        '#__minimarket_product_attribute_free_values'] as $table) {
                        $q = $db->getQuery(true)->select('COUNT(*)')
                            ->from($db->quoteName($table))
                            ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id);
                        $db->setQuery($q);
                        if ((int) $db->loadResult() > 0) {
                            $this->setError(Text::_('COM_MINIMARKET_UNIFIED_SOURCE_IN_USE'));
                            return false;
                        }
                    }
                }
            }
        }

        if ((int) $this->id > 0 && (int) $this->is_multiple === 0) {
            $q = $db->getQuery(true)->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_product_attribute_free_values'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id)
                ->group($db->quoteName('product_id'))->having('COUNT(*) > 1');
            $db->setQuery($q);
            if ((int) $db->loadResult() > 0) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_MULTIPLE_IN_USE'));
                return false;
            }
        }

        // Never reinterpret an attribute with live saved values, regardless of
        // which source or target type is selected (scalar, list, or multi).
        if ((int) $this->id > 0) {
            $q = $db->getQuery(true)->select($db->quoteName('input_type'))
                ->from($db->quoteName('#__minimarket_attributes'))
                ->where($db->quoteName('id') . ' = ' . (int) $this->id);
            $db->setQuery($q);
            $oldType = (string) ($db->loadResult() ?? '');
            if ($oldType !== '' && $oldType !== $this->input_type) {
                foreach (['#__minimarket_product_attribute_scalars',
                    '#__minimarket_product_attribute_options',
                    '#__minimarket_product_attribute_multi_values',
                    '#__minimarket_product_attribute_free_values'] as $table) {
                    $q = $db->getQuery(true)->select('COUNT(*)')
                        ->from($db->quoteName($table))
                        ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id);
                    $db->setQuery($q);
                    if ((int) $db->loadResult() > 0) {
                        $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE_HAS_VALUES'));
                        return false;
                    }
                }
            }
        }

        // Existing list dictionaries must not become uneditable or orphaned
        // when an admin changes the input type to text/number/boolean.
        if ((int) $this->id > 0 && ($this->value_source ?? 'legacy') === 'legacy'
            && !in_array($this->input_type, ['list','color'], true)) {
            $valuesQuery = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_attribute_values'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id);
            $db->setQuery($valuesQuery);

            if ((int) $db->loadResult() > 0) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE_HAS_VALUES'));

                return false;
            }
        }

        return parent::check();
    }
}
