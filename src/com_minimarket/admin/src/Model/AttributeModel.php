<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\BulkDeleteGuard;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeValueRows;
use MiniMarket\Component\Minimarket\Administrator\Service\AttributeSettings;

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
            $db=$this->getDatabase();
            $item->category_rules=CategoryAttributes::loadForAttribute($db,(int)($item->id??0));
            $item->unified_values=AttributeValueRows::formRows(
                $db,(int)($item->id??0),(string)($item->input_type??'text'));
        }
        return $item;
    }

    /**
     * Canonical attribute and dictionary values use one transaction.
     * No legacy mode, CSV or parallel value editor can overwrite selections.
     */
    public function save($data)
    {
        if (!is_array($data)) return false;
        $type=(string)($data['input_type']??'text');
        if ($type==='boolean') $data['value_source']='manual';
        $source=(string)($data['value_source']??'manual');
        $hasRules=array_key_exists('category_rules',$data)
            || !empty($data['categories_present']);
        $db=$this->getDatabase();
        try {
            $rules=$hasRules
                ? CategoryAttributes::normalizeRules(
                    $data['category_rules']??[],CategoryAttributes::categoryIds($db))
                : [];
        } catch (\InvalidArgumentException $error) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRCAT_INVALID'));
            return false;
        }
        $hasValues=array_key_exists('unified_values',$data)
            || !empty($data['values_present']);
        $oldValues=[];$newValues=[];
        if ($hasValues) {
            try {
                $attributeId=(int)($data['id']??0);
                $oldValues=AttributeValueRows::load($db,$attributeId);
                if ($source==='preset') {
                    $newValues=AttributeValueRows::normalize(
                        $data['unified_values']??[],$oldValues,$type);
                    if (!$newValues) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_UNIFIED_VALUES_REQUIRED');
                    }
                    if ($type==='number') {
                        foreach ($newValues as $row) {
                            if (!AttributeSettings::inRange(
                                (string)$row['value_payload'],
                                ($data['number_min']??'')===''?null:(string)$data['number_min'],
                                ($data['number_max']??'')===''?null:(string)$data['number_max']
                            )) {
                                throw new \InvalidArgumentException('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID');
                            }
                        }
                    }
                    if ($type==='color') {
                        foreach ($newValues as $row) {
                            if ($row['color_hex']==='') {
                                throw new \InvalidArgumentException('COM_MINIMARKET_COLOR_INVALID');
                            }
                        }
                    }
                }
            } catch (\InvalidArgumentException|\JsonException $error) {
                $key=$error->getMessage();
                $this->setError(Text::_(str_starts_with($key,'COM_MINIMARKET_')
                    ? $key : 'COM_MINIMARKET_VALUE_ROWS_INVALID'));
                return false;
            }
        }
        unset($data['category_rules'],$data['categories_present'],
            $data['unified_values'],$data['values_present']);
        $db->transactionStart();
        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }
            $id=(int)$this->getState($this->getName().'.id');
            if ($hasRules) CategoryAttributes::replaceForAttribute($db,$id,$rules);
            if ($hasValues) AttributeValueRows::apply($db,$id,$oldValues,$newValues);
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $key=$error->getMessage()==='COM_MINIMARKET_VALUE_ROWS_IN_USE'
                ? 'COM_MINIMARKET_VALUE_ROWS_IN_USE'
                : 'COM_MINIMARKET_ATTRCAT_SAVE_FAILED';
            $this->setError(Text::_($key));
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
