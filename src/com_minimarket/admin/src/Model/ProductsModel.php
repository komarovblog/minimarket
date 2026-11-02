<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;

final class ProductsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('p.id'),
                $db->quoteName('p.title'),
                $db->quoteName('p.sku'),
                $db->quoteName('p.price'),
                $db->quoteName('p.stock'),
                $db->quoteName('p.state'),
                $db->quoteName('p.sort_order'),
                $db->quoteName('p.image'),
                $db->quoteName('p.image_alt'),
                $db->quoteName('p.category_id'),
                $db->quoteName('c.title', 'category_title'),
            ])
            ->from($db->quoteName('#__minimarket_products', 'p'))
            ->join('INNER', $db->quoteName('#__minimarket_categories', 'c')
                . ' ON ' . $db->quoteName('p.category_id') . ' = ' . $db->quoteName('c.id'));

        $categoryId = (int) $this->getState('filter.category_id');
        if ($categoryId > 0) {
            $query->where($db->quoteName('p.category_id') . ' = ' . $categoryId);
        }

        $state = (string) $this->getState('filter.published');
        if ($state === '0' || $state === '1') {
            $query->where($db->quoteName('p.state') . ' = ' . (int) $state);
        }

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%', false);
            $query->where('(' . $db->quoteName('p.title') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('p.sku') . ' LIKE ' . $like
                . (ctype_digit($search) ? ' OR ' . $db->quoteName('p.id') . ' = ' . (int) $search : '')
                . ')');
        }

        $fullOrdering = trim(
            (string) $this->getState('list.ordering', 'p.id') . ' '
            . (string) $this->getState('list.direction', 'DESC')
        );
        $allowed = ['p.id ASC','p.id DESC','p.title ASC','p.title DESC',
            'p.price ASC','p.price DESC','p.sort_order ASC'];
        $query->order(in_array($fullOrdering, $allowed, true) ? $fullOrdering : 'p.id DESC')
            ->order('p.id ASC');

        return $query;
    }

    protected function populateState($ordering = 'p.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
    }

    /** One read for the category filter; no N+1 query for category ancestry. */
    public function getCategoryOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['id', 'parent_id', 'title', 'alias', 'sort_order', 'state'])
            ->from($db->quoteName('#__minimarket_categories'));
        $db->setQuery($query);

        return CategoryAdminTree::flatten((array) $db->loadObjectList());
    }
}
