<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;

final class CategoryModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_minimarket.category',
            'category',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item) {
            $item->attribute_rules = CategoryAttributes::loadDirect(
                $this->getDatabase(), (int) ($item->id ?? 0)
            );
        }
        return $item;
    }

    /**
     * Category and its *direct* overrides save atomically. Inherited rules
     * are resolved dynamically and never duplicated in descendants.
     */
    public function save($data)
    {
        $hasAttributes = array_key_exists('attribute_rules', $data)
            || !empty($data['attributes_present']);
        $db = $this->getDatabase();

        try {
            $rules = $hasAttributes
                ? CategoryAttributes::normalizeRules(
                    $data['attribute_rules'] ?? [],
                    CategoryAttributes::allowedIds($db)
                ) : [];
        } catch (\InvalidArgumentException $error) {
            $this->setError(Text::_('COM_MINIMARKET_CATATTR_INVALID'));
            return false;
        }

        unset($data['attribute_rules'], $data['attributes_present']);
        $db->transactionStart();
        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }
            if ($hasAttributes) {
                $categoryId = (int) $this->getState($this->getName() . '.id');
                CategoryAttributes::replaceDirect($db, $categoryId, $rules);
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_CATATTR_SAVE_FAILED'));
            return false;
        }
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.category.data', []);

        return empty($data) ? $this->getItem() : $data;
    }
}
