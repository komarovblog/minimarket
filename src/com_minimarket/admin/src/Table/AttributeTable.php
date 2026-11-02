<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings;

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
        if (!in_array($this->input_type, ['list','color'], true)) {
            $this->is_multiple = 0;
        }

        foreach (['is_variant', 'is_multiple', 'is_filterable', 'state'] as $field) {
            if (!\in_array((int) $this->$field, [0, 1], true)) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_FLAGS_INVALID'));

                return false;
            }

            $this->$field = (int) $this->$field;
        }

        if ($this->is_variant === 1 && !in_array($this->input_type, ['list','color'], true)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_VARIANT_REQUIRES_LIST'));

            return false;
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
        if ((int) $this->id > 0 && $this->input_type === 'number'
            && $this->input_mode === 'presets') {
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

        if ((int) $this->id > 0 && $this->input_type === 'text'
            && $this->input_mode === 'presets') {
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
        if ((int) $this->id > 0 && in_array($this->input_type, ['list','color'], true)
            && $this->is_multiple === 0) {
            $multipleQuery = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_product_attribute_options'))
                ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id)
                ->group($db->quoteName('product_id'))
                ->having('COUNT(*) > 1');
            $db->setQuery($multipleQuery);
            if ((int) $db->loadResult() > 1) {
                $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_MULTIPLE_IN_USE'));
                return false;
            }
        }

        // Preserve stored scalar values: a type change may reinterpret values
        // (e.g. text -> date). Require explicit cleanup before changing type.
        if ((int) $this->id > 0 && !in_array($this->input_type, ['list','color'], true)) {
            $typeQuery = $db->getQuery(true)
                ->select($db->quoteName('input_type'))
                ->from($db->quoteName('#__minimarket_attributes'))
                ->where($db->quoteName('id') . ' = ' . (int) $this->id);
            $db->setQuery($typeQuery);
            $previousType = (string) ($db->loadResult() ?? '');
            if ($previousType !== '' && $previousType !== $this->input_type) {
                $existingScalar = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__minimarket_product_attribute_scalars'))
                    ->where($db->quoteName('attribute_id') . ' = ' . (int) $this->id);
                $db->setQuery($existingScalar);
                if ((int) $db->loadResult() > 0) {
                    $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_TYPE_HAS_VALUES'));
                    return false;
                }
            }
        }

        // Existing list dictionaries must not become uneditable or orphaned
        // when an admin changes the input type to text/number/boolean.
        if ((int) $this->id > 0 && $this->input_type !== 'list') {
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
