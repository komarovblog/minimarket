<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use MiniMarket\Component\Minimarket\Administrator\Service\OrderOptionCatalog;
use MiniMarket\Component\Minimarket\Administrator\Service\BulkDeleteGuard;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryOrderOptions;

final class OrderoptionModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_minimarket.orderoption', 'orderoption',
            ['control' => 'jform', 'load_data' => $loadData]);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item) {
            $item->category_rules = CategoryOrderOptions::directForOption(
                $this->getDatabase(), (int) ($item->id ?? 0)
            );
            $item->option_values = OrderOptionCatalog::formRows(
                $this->getDatabase(), (int) ($item->id ?? 0)
            );
        }
        return $item;
    }

    public function save($data)
    {
        $hasValues = array_key_exists('option_values', $data)
            || !empty($data['values_present']);
        $hasCategories = array_key_exists('category_rules', $data)
            || !empty($data['categories_present']);
        $db = $this->getDatabase();
        $old = [];
        $rows = [];
        if ($hasValues) {
            try {
                $old = OrderOptionCatalog::values($db, (int) ($data['id'] ?? 0));
                $rows = OrderOptionCatalog::normalize($data['option_values'] ?? [], $old);
            } catch (\InvalidArgumentException | \JsonException $error) {
                $this->setError(Text::_('COM_MINIMARKET_ORDER_OPTION_VALUES_INVALID'));
                return false;
            }
        }
        try {
            $categoryRules = $hasCategories
                ? CategoryOrderOptions::normalize(
                    $data['category_rules'] ?? [],
                    CategoryOrderOptions::categoryIds($db)
                ) : [];
        } catch (\InvalidArgumentException $error) {
            $this->setError(Text::_('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID'));
            return false;
        }
        unset($data['option_values'], $data['values_present'],
            $data['category_rules'], $data['categories_present']);
        $db->transactionStart();
        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }
            $id = (int) $this->getState($this->getName() . '.id');
            if ($hasValues) {
                OrderOptionCatalog::replace($db, $id, $old, $rows);
            }
            if ($hasCategories) {
                CategoryOrderOptions::replaceForOption($db, $id, $categoryRules);
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_ORDER_OPTION_SAVE_FAILED'));
            return false;
        }
    }

    /** Do not implicitly delete category assignments or an option's values. */
    public function delete(&$pks)
    {
        try {
            $ids = BulkDeleteGuard::normalizeIds($pks);
            if (!$ids) throw new \RuntimeException('COM_MINIMARKET_ORDER_OPTION_IN_USE');
            $db = $this->getDatabase();
            foreach (['#__minimarket_order_option_values',
                      '#__minimarket_category_order_options'] as $table) {
                $q = $db->getQuery(true)->select('COUNT(*)')
                    ->from($db->quoteName($table))
                    ->where('option_id IN (' . implode(',', $ids) . ')');
                $db->setQuery($q);
                if ((int) $db->loadResult() > 0) {
                    throw new \RuntimeException('COM_MINIMARKET_ORDER_OPTION_IN_USE');
                }
            }
        } catch (\Throwable $error) {
            $this->setError(Text::_('COM_MINIMARKET_ORDER_OPTION_IN_USE'));
            return false;
        }
        return parent::delete($pks);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.orderoption.data', []);
        return empty($data) ? $this->getItem() : $data;
    }
}
