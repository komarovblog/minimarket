<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;

/** Parent picker with visible nesting; descendants of the edited item are omitted. */
final class CategoryparentField extends ListField
{
    protected $type = 'Categoryparent';

    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select(['id', 'parent_id', 'title', 'alias', 'sort_order', 'state'])
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($query);
        $rows = CategoryAdminTree::flatten((array) $db->loadObjectList());
        $editingId = (int) ($this->form?->getValue('id') ?? 0);
        $excluded = [];
        $options = parent::getOptions();

        foreach ($rows as $row) {
            $id = (int) $row['item']->id;
            $parentIndex = $row['parent_index'];
            $blocked = $id === $editingId;
            while (!$blocked && $parentIndex !== null) {
                $ancestor = $rows[$parentIndex] ?? null;
                if ($ancestor === null) {
                    break;
                }
                if ((int) $ancestor['item']->id === $editingId) {
                    $blocked = true;
                }
                $parentIndex = $ancestor['parent_index'];
            }
            if (!$blocked) {
                $label = str_repeat('— ', min(20, $row['depth'])) . (string) $row['item']->title;
                $options[] = HTMLHelper::_('select.option', $id, $label);
            }
        }

        return $options;
    }
}
