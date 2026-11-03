<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

/** Native multi-select of independently editable products. */
final class GroupmembersField extends ListField
{
    protected $type = 'Groupmembers';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)->select(['id','title','state'])
            ->from($db->quoteName('#__minimarket_products'))
            ->order('title ASC, id ASC');
        $db->setQuery($q);
        foreach ((array) $db->loadAssocList() as $product) {
            $label = (string) $product['title'] . ' (#' . (int) $product['id'] . ')';
            if ((int) $product['state'] !== 1) $label .= ' — ' . Text::_('JUNPUBLISHED');
            $options[] = HTMLHelper::_('select.option', (int) $product['id'], $label);
        }
        return $options;
    }
}
