<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;
use MiniMarket\Component\Minimarket\Administrator\Service\GroupEditor;

final class GroupModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_minimarket.group', 'group',
            ['control' => 'jform', 'load_data' => $loadData]);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item) {
            $item->member_ids = ProductGroups::memberIds($this->getDatabase(), (int) ($item->id ?? 0));
        }
        return $item;
    }

    /** Group metadata and members are saved in one DB transaction. */
    public function save($data)
    {
        $isEditor = array_key_exists('member_editor', $data);
        $editorRows = [];
        try {
            $ids = $isEditor ? [] :
                ProductGroups::normalizeIds($data['member_ids'] ?? []);
            if ($isEditor) {
                $editorRows = GroupEditor::normalize($data['member_editor'], 'group');
            }
        } catch (\InvalidArgumentException $e) {
            $this->setError(Text::_('COM_MINIMARKET_GROUP_MEMBERS_INVALID'));
            return false;
        }
        unset($data['member_ids'], $data['members_present'], $data['member_editor']);
        $db = $this->getDatabase();
        $db->transactionStart();
        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }
            $id = (int) $this->getState($this->getName() . '.id');
            if ($isEditor) {
                GroupEditor::saveGroup($db, $id, $editorRows);
            } else {
                ProductGroups::replaceForGroup($db, $id, $ids);
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_GROUP_MEMBERS_INVALID'));
            return false;
        }
    }

    /**
     * Explicit Joomla bulk deletion unlinks members first, then deletes only
     * relationship records. Products, URLs, SKU and stock remain untouched.
     * A failing FK or permission check rolls back the entire operation.
     */
    public function delete(&$pks)
    {
        try {
            $ids = ProductGroups::normalizeIds($pks);
            if (!$ids) {
                throw new \InvalidArgumentException('COM_MINIMARKET_PRODUCT_GROUP_INVALID');
            }
        } catch (\InvalidArgumentException $error) {
            $this->setError(Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID'));
            return false;
        }

        $db = $this->getDatabase();
        $db->transactionStart();
        try {
            foreach ($ids as $id) {
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__minimarket_product_group_members'))
                    ->where('group_id = ' . $id);
                $db->setQuery($query);
                $db->execute();
            }
            if (!parent::delete($pks)) {
                $db->transactionRollback();
                return false;
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID'));
            return false;
        }
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.group.data', []);
        return empty($data) ? $this->getItem() : $data;
    }
}
