<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

final class OrderoptionsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $q = $db->getQuery(true)->select([
                'o.id','o.title','o.code','o.state','o.sort_order',
                '(SELECT COUNT(*) FROM ' . $db->quoteName('#__minimarket_order_option_values')
                    . ' AS v WHERE v.option_id = o.id) AS value_count'
            ])
            ->from($db->quoteName('#__minimarket_order_options', 'o'));
        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%', false);
            $q->where('(' . $db->quoteName('o.title') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('o.code') . ' LIKE ' . $like
                . (ctype_digit($search) ? ' OR o.id = ' . (int) $search : '')
                . ')');
        }
        $published = (string) $this->getState('filter.published');
        if (in_array($published, ['0','1'], true)) $q->where('o.state = ' . (int)$published);
        $full = trim((string) $this->getState('list.ordering', 'o.sort_order') . ' '
            . (string) $this->getState('list.direction', 'ASC'));
        $allowed = ['o.sort_order ASC','o.title ASC','o.title DESC','o.id ASC','o.id DESC'];
        $q->order(in_array($full, $allowed, true) ? $full : 'o.sort_order ASC')
            ->order('o.id ASC');
        return $q;
    }

    protected function populateState($ordering = 'o.sort_order', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }
}
