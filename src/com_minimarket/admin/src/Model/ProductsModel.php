<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;

final class ProductsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $input = Factory::getApplication()->input;

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

        $categoryId = $input->getInt('mm_category', 0);
        if ($categoryId > 0) {
            $query->where($db->quoteName('p.category_id') . ' = ' . $categoryId);
        }

        $state = $input->getCmd('mm_state', 'all');
        if ($state === '0' || $state === '1') {
            $query->where($db->quoteName('p.state') . ' = ' . (int) $state);
        }

        $search = trim($input->getString('mm_search', ''));
        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%', false);
            $query->where('(' . $db->quoteName('p.title') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('p.sku') . ' LIKE ' . $like
                . (ctype_digit($search) ? ' OR ' . $db->quoteName('p.id') . ' = ' . (int) $search : '')
                . ')');
        }

        $sort = $input->getCmd('mm_sort', 'newest');
        $orders = [
            'newest' => 'p.id DESC',
            'title' => 'p.title ASC, p.id ASC',
            'price_asc' => 'p.price ASC, p.id ASC',
            'price_desc' => 'p.price DESC, p.id ASC',
            'order' => 'p.sort_order ASC, p.id ASC',
        ];
        $query->order($orders[$sort] ?? $orders['newest']);

        return $query;
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
