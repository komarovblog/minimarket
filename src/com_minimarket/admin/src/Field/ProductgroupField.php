<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;

/** Native Joomla checkboxes for optional many-to-many product links. */
final class ProductgroupField extends CheckboxesField
{
    protected $type = 'Productgroup';

    protected function getOptions()
    {
        $options=parent::getOptions();
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        foreach (ProductGroups::options($db) as $group) {
            $label = (string) $group['title'];
            if ((int) $group['state'] !== 1) {
                $label .= ' — ' . \Joomla\CMS\Language\Text::_('JUNPUBLISHED');
            }
            $options[] = HTMLHelper::_('select.option', (int) $group['id'], $label);
        }
        // Joomla 6.2 checkboxes layout expects every option to expose
        // ->checked, including options added outside the XML definition.
        // Compare against the current persisted or posted values.
        $selected = array_map('strval', (array) ($this->value ?? []));
        foreach ($options as $option) {
            $option->checked = in_array((string) $option->value, $selected, true);
        }
        return $options;
    }
}
