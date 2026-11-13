<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryOrderOptions;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryListVisibility;
use MiniMarket\Component\Minimarket\Administrator\Service\BulkDeleteGuard;
use MiniMarket\Component\Minimarket\Site\Service\CategoryRecommendationRules;

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
            $item->attribute_rules = CategoryAttributes::loadDirectWithVisibility(
                $this->getDatabase(), (int) ($item->id ?? 0)
            );
            $item->order_option_rules = CategoryOrderOptions::directWithVisibility(
                $this->getDatabase(), (int) ($item->id ?? 0)
            );
            foreach(CategoryRecommendationRules::raw($this->getDatabase(),(int)($item->id??0))
                    as $key=>$value)$item->$key=$value;
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
        $hasOrderOptions = array_key_exists('order_option_rules', $data)
            || !empty($data['order_options_present']);
        $hasRecommendations=array_key_exists('similar_scope',$data)
            ||array_key_exists('together_scope',$data);
        $recData=$hasRecommendations?array_intersect_key($data,array_flip([
            'similar_scope','similar_categories','similar_attributes',
            'together_scope','together_categories'
        ])):[];
        $db = $this->getDatabase();

        try {
            $allowedAttributes=$hasAttributes?CategoryAttributes::allowedIds($db):[];
            $rules = $hasAttributes
                ? CategoryAttributes::normalizeRules(
                    CategoryListVisibility::modesOnly($data['attribute_rules'] ?? []),
                    $allowedAttributes
                ) : [];
            $attributeListFlags=$hasAttributes
                ? CategoryListVisibility::flags($data['attribute_rules']??[],$allowedAttributes):[];
        } catch (\InvalidArgumentException $error) {
            $this->setError(Text::_('COM_MINIMARKET_CATATTR_INVALID'));
            return false;
        }

        try {
            $allowedOptions=$hasOrderOptions?CategoryOrderOptions::allowedIds($db):[];
            $orderOptionRules = $hasOrderOptions
                ? CategoryOrderOptions::normalize(
                    CategoryListVisibility::modesOnly($data['order_option_rules']??[]),
                    $allowedOptions
                ) : [];
            $optionListFlags=$hasOrderOptions
                ? CategoryListVisibility::flags($data['order_option_rules']??[],$allowedOptions):[];
        } catch (\InvalidArgumentException $error) {
            $this->setError(Text::_('COM_MINIMARKET_CATEGORY_ORDER_OPTIONS_INVALID'));
            return false;
        }

        unset($data['attribute_rules'], $data['attributes_present'],
            $data['order_option_rules'], $data['order_options_present'],
            $data['similar_scope'], $data['similar_categories'], $data['similar_attributes'],
            $data['together_scope'], $data['together_categories']);
        $db->transactionStart();
        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }
            $categoryId = (int) $this->getState($this->getName() . '.id');
            if ($hasAttributes) {
                CategoryAttributes::replaceDirect($db, $categoryId, $rules, $attributeListFlags);
            }
            if ($hasOrderOptions) {
                CategoryOrderOptions::replace($db, $categoryId, $orderOptionRules, $optionListFlags);
            }
            if ($hasRecommendations) {
                CategoryRecommendationRules::save($db,$categoryId,$recData);
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError($error instanceof \DomainException
                ?Text::_('COM_MINIMARKET_RECOMM_RULE_INVALID')
                :Text::_('COM_MINIMARKET_CATATTR_SAVE_FAILED'));
            return false;
        }
    }

    /**
     * Never orphan subcategories. Products are protected by their category FK.
     * Validate every selected ID before deleting any category.
     */
    public function delete(&$pks)
    {
        $ids = BulkDeleteGuard::normalizeIds($pks);
        if (!$ids) {
            $this->setError(Text::_('COM_MINIMARKET_BULK_EMPTY'));
            return false;
        }
        try {
            BulkDeleteGuard::assertNoDependencies($this->getDatabase(), 'category', $ids);
        } catch (\RuntimeException $error) {
            $this->setError(Text::_('COM_MINIMARKET_BULK_CATEGORY_IN_USE'));
            return false;
        }
        return parent::delete($pks);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.category.data', []);

        return empty($data) ? $this->getItem() : $data;
    }
}
