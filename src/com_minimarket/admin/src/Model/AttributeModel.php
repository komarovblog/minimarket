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
        return $this->loadForm('com_minimarket.attribute','attribute',
            ['control'=>'jform','load_data'=>$loadData]);
    }

    public function getItem($pk = null)
    {
        $item=parent::getItem($pk);
        if ($item) {
            $item->category_rules=CategoryAttributes::loadForAttribute(
                $this->getDatabase(), (int) ($item->id ?? 0));
            $item->unified_values=AttributeValueRows::formRows(
                $this->getDatabase(), (int) ($item->id ?? 0),
                (string) ($item->input_type ?? 'text'));
        }
        return $item;
    }

    /** Save one definition, its categories and dictionary values atomically. */
    public function save($data)
    {
        $hasRules=array_key_exists('category_rules',$data)
            || !empty($data['categories_present']);
        $db=$this->getDatabase();
        try {
            $rules=$hasRules?CategoryAttributes::normalizeRules(
                $data['category_rules'] ?? [],CategoryAttributes::categoryIds($db)):[];
        } catch (\InvalidArgumentException) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRCAT_INVALID'));
            return false;
        }
        $source=(string) ($data['value_source'] ?? 'manual');
        if (($data['input_type'] ?? '') === 'boolean') {
            $source='manual';
            $data['value_source']='manual';
        }
        $hasValues=array_key_exists('unified_values',$data)
            || !empty($data['values_present']);
        $oldValues=[];$newValues=[];
        if ($hasValues) {
            try {
                $oldValues=AttributeValueRows::load($db,(int) ($data['id'] ?? 0));
                $newValues=$source==='preset'
                    ? AttributeValueRows::normalize($data['unified_values'] ?? [],
                        $oldValues,(string) ($data['input_type'] ?? 'text'))
                    : [];
                if ($source==='preset' && !$newValues) {
                    throw new \InvalidArgumentException('COM_MINIMARKET_UNIFIED_VALUES_REQUIRED');
                }
                if ($source==='preset' && ($data['input_type'] ?? '')==='number') {
                    foreach($newValues as $row) {
                        if (!AttributeSettings::inRange($row['value_payload'],
                            ($data['number_min'] ?? '')===''?null:(string)$data['number_min'],
                            ($data['number_max'] ?? '')===''?null:(string)$data['number_max'])) {
                            throw new \InvalidArgumentException('COM_MINIMARKET_ATTRIBUTE_NUMERIC_INVALID');
                        }
                    }
                }
            } catch (\InvalidArgumentException | \JsonException $error) {
                $this->setError(Text::_($error->getMessage()));
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
            $this->setError(Text::_($error->getMessage()==='COM_MINIMARKET_VALUE_ROWS_IN_USE'
                ? 'COM_MINIMARKET_VALUE_ROWS_IN_USE' : 'COM_MINIMARKET_ATTRCAT_SAVE_FAILED'));
            return false;
        }
    }

    public function delete(&$pks)
    {
        $ids=BulkDeleteGuard::normalizeIds($pks);
        if (!$ids) {
            $this->setError(Text::_('COM_MINIMARKET_BULK_EMPTY'));
            return false;
        }
        try {
            BulkDeleteGuard::assertNoDependencies($this->getDatabase(),'attribute',$ids);
        } catch (\RuntimeException) {
            $this->setError(Text::_('COM_MINIMARKET_BULK_ATTRIBUTE_IN_USE'));
            return false;
        }
        return parent::delete($pks);
    }

    protected function loadFormData()
    {
        $data=Factory::getApplication()->getUserState('com_minimarket.edit.attribute.data',[]);
        return empty($data)?$this->getItem():$data;
    }
}
