<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;

/** Native Joomla dropdown for independently linked, SEO-addressable products. */
final class ProductgroupField extends ListField
{
    protected $type = 'Productgroup';

    protected function getOptions()
    {
        $options=parent::getOptions();
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        foreach (ProductGroups::options($db) as $group) {
            $options[]=HTMLHelper::_('select.option',(int)$group['id'],(string)$group['title']);
        }
        return $options;
    }
}
