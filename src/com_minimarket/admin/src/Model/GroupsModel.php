<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class GroupsModel extends ListModel
{
    protected string $groupType='product';

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['g.id', 'g.title', 'g.state', 'g.sort_order',
                '(SELECT COUNT(*) FROM ' . $db->quoteName('#__minimarket_product_group_members')
                  . ' AS m WHERE m.group_id = g.id) AS member_count'])
            ->from($db->quoteName('#__minimarket_product_groups', 'g'))
            ->where($this->groupType==='bundle' ? "g.type='bundle'" : "g.type<>'bundle'");
        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%', false);
            $query->where('(' . $db->quoteName('g.title') . ' LIKE ' . $like
                . (ctype_digit($search) ? ' OR g.id = ' . (int) $search : '') . ')');
        }
        $published = (string) $this->getState('filter.published');
        if (in_array($published, ['0','1'], true)) {
            $query->where('g.state = ' . (int) $published);
        }
        $sort = trim((string) $this->getState('list.ordering', 'g.id') . ' '
            . (string) $this->getState('list.direction', 'DESC'));
        $allowed = ['g.id ASC', 'g.id DESC', 'g.title ASC', 'g.title DESC', 'g.sort_order ASC'];
        $query->order(in_array($sort, $allowed, true) ? $sort : 'g.id DESC');
        return $query;
    }

    protected function populateState($ordering = 'g.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
    }
}
