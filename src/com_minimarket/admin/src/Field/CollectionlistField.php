<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

/** Joomla native multiple collection checkboxes for Product. */
final class CollectionlistField extends CheckboxesField
{
    protected $type = 'Collectionlist';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)
            ->select(['id', 'title', 'state'])
            ->from($db->quoteName('#__minimarket_collections'))
            ->order('title ASC, id ASC');
        $db->setQuery($q);
        foreach ((array)$db->loadObjectList() as $item) {
            // All existing records remain selectable so unpublished assignments
            // are not silently erased when editing an existing product.
            $options[] = HTMLHelper::_('select.option', (int)$item->id,
                (string)$item->title);
        }
        return $options;
    }
}
