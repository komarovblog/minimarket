<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\BulkDeleteGuard;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeValueRows;

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
            $item->category_rules = CategoryAttributes::loadForAttribute(
                $this->getDatabase(), (int) ($item->id ?? 0)
            );
            $item->values = AttributeValueRows::formRows(
                $this->getDatabase(), (int) ($item->id ?? 0)
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
        $hasValues = array_key_exists('values', $data)
            || !empty($data['values_present']);
        $oldValues = [];
        $newValues = [];
        if ($hasValues) {
            try {
                $attributeId = (int) ($data['id'] ?? 0);
                $oldValues = AttributeValueRows::load($db, $attributeId);
                $newValues = AttributeValueRows::normalize(
                    $data['values'] ?? [], $oldValues
                );
                if ((string) ($data['input_type'] ?? '') !== 'list'
                    && $newValues !== []) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_VALUE_ROWS_INVALID');
                }
            } catch (\InvalidArgumentException | \JsonException $error) {
                $this->setError(Text::_('COM_MINIMARKET_VALUE_ROWS_INVALID'));
                return false;
            }
        }

        unset($data['category_rules'], $data['categories_present'],
            $data['values'], $data['values_present'],
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
            if ($hasValues && (string) ($data['input_type'] ?? '') === 'list') {
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
