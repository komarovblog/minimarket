<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

final class AttributesModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.code'),
                $db->quoteName('a.input_type'),
                $db->quoteName('a.is_filterable'),
                $db->quoteName('a.state'),
                $db->quoteName('a.sort_order'),
            ])
            ->from($db->quoteName('#__minimarket_attributes', 'a'))
; 
        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%', false);
            $query->where('(' . $db->quoteName('a.title') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('a.code') . ' LIKE ' . $like
                . (ctype_digit($search) ? ' OR ' . $db->quoteName('a.id') . ' = ' . (int) $search : '')
                . ')');
        }
        $published = (string) $this->getState('filter.published');
        if ($published === '1' || $published === '0') {
            $query->where($db->quoteName('a.state') . ' = ' . (int) $published);
        }
        $order = trim((string) $this->getState('list.ordering', 'a.sort_order')
            . ' ' . (string) $this->getState('list.direction', 'ASC'));
        $allowed = ['a.sort_order ASC','a.title ASC','a.title DESC','a.id ASC','a.id DESC'];
        $query->order(in_array($order, $allowed, true) ? $order : 'a.sort_order ASC')
            ->order('a.id ASC');
        return $query;
    }
    protected function populateState($ordering = 'a.sort_order', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }
}
