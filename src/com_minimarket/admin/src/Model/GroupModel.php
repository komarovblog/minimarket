<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;

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
        try {
            $ids = ProductGroups::normalizeIds($data['member_ids'] ?? []);
        } catch (\InvalidArgumentException $e) {
            $this->setError(Text::_('COM_MINIMARKET_GROUP_MEMBERS_INVALID'));
            return false;
        }
        unset($data['member_ids'], $data['members_present']);
        $db = $this->getDatabase();
        $db->transactionStart();
        try {
            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }
            $id = (int) $this->getState($this->getName() . '.id');
            ProductGroups::replaceForGroup($db, $id, $ids);
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            $db->transactionRollback();
            $this->setError(Text::_('COM_MINIMARKET_GROUP_MEMBERS_INVALID'));
            return false;
        }
    }

    /** Refuse deletion while a group has members: no silent unlinking. */
    public function delete(&$pks)
    {
        try {
            $ids = ProductGroups::normalizeIds($pks);
            foreach ($ids as $id) {
                if (ProductGroups::memberIds($this->getDatabase(), $id)) {
                    $this->setError(Text::_('COM_MINIMARKET_GROUP_IN_USE'));
                    return false;
                }
            }
        } catch (\InvalidArgumentException $e) {
            $this->setError(Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID'));
            return false;
        }
        return parent::delete($pks);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.group.data', []);
        return empty($data) ? $this->getItem() : $data;
    }
}
