<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

final class CollectionfilterField extends ListField
{
    protected $type = 'Collectionfilter';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $q=$db->getQuery(true)->select(['id','title'])
            ->from($db->quoteName('#__minimarket_collections'))->order('title ASC,id ASC');
        $db->setQuery($q);
        foreach ((array)$db->loadObjectList() as $row) {
            $options[]=HTMLHelper::_('select.option',(int)$row->id,(string)$row->title);
        }
        return $options;
    }
}
