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
                . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('v.attribute_id'));

        $attributeId = (int) $this->getState('filter.attribute_id');
        if ($attributeId > 0) {
            $query->where($db->quoteName('v.attribute_id') . ' = ' . $attributeId);
        }

        $status = (string) $this->getState('filter.published');
        if (in_array($status, ['0', '1'], true)) {
            $query->where($db->quoteName('v.state') . ' = ' . (int) $status);
        }

        $search = trim((string) $this->getState('filter.search', ''));
        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%', false);
            $terms = ['v.title LIKE ' . $like, 'v.code LIKE ' . $like,
                'a.title LIKE ' . $like];
            if (ctype_digit($search)) {
                $terms[] = 'v.id=' . (int) $search;
            }
            $query->where('(' . implode(' OR ', $terms) . ')');
        }

        $fullOrdering = trim(
            (string) $this->getState('list.ordering', 'v.sort_order') . ' '
            . (string) $this->getState('list.direction', 'ASC')
        );
        $allowed = [
            'v.sort_order ASC', 'v.sort_order DESC', 'v.title ASC', 'v.title DESC',
            'v.id ASC', 'v.id DESC',
        ];
        $query->order(in_array($fullOrdering, $allowed, true)
            ? $fullOrdering : 'v.sort_order ASC')->order('v.id ASC');

        return $query;
    }

    protected function populateState($ordering = 'v.sort_order', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
        // Keep old links from a characteristic's Values button functional.
        $legacyId = Factory::getApplication()->input->getInt('attribute_id', 0);
        $postedFilters = Factory::getApplication()->input->post->get('filter', [], 'array');
        if ($legacyId > 0
            && !array_key_exists('attribute_id', is_array($postedFilters) ? $postedFilters : [])
            && (int) $this->getState('filter.attribute_id') === 0
        ) {
            $this->setState('filter.attribute_id', $legacyId);
        }
    }
}
