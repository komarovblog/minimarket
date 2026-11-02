<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

final class ValuesModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('v.id'),
                $db->quoteName('v.attribute_id'),
                $db->quoteName('v.title'),
                $db->quoteName('v.code'),
                $db->quoteName('v.state'),
                $db->quoteName('v.sort_order'),
                $db->quoteName('a.title', 'attribute_title'),
            ])
            ->from($db->quoteName('#__minimarket_attribute_values', 'v'))
            ->join('INNER', $db->quoteName('#__minimarket_attributes', 'a')
                . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('v.attribute_id'))
            ->order($db->quoteName('a.title') . ' ASC');

        $attributeId = Factory::getApplication()->input->getInt('attribute_id', 0);

        if ($attributeId > 0) {
            $query->where($db->quoteName('v.attribute_id') . ' = ' . $attributeId);
        }

        // Explicit allow-list; no arbitrary ORDER BY accepted from the URL.
        $sort = Factory::getApplication()->input->getCmd('sort', 'order');
        $direction = strtolower(Factory::getApplication()->input->getCmd('direction', 'asc'));
        $sort = $sort === 'title' ? 'v.title' : 'v.sort_order';
        $direction = $direction === 'desc' ? 'DESC' : 'ASC';
        $query->order($db->quoteName($sort) . ' ' . $direction)
            ->order($db->quoteName('v.id') . ' ASC');

        return $query;
    }
}
