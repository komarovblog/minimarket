<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;

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

        unset($data['category_rules'], $data['categories_present']);
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
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_ATTRCAT_SAVE_FAILED'));
            return false;
        }
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.attribute.data', []);

        return empty($data) ? $this->getItem() : $data;
    }
}
