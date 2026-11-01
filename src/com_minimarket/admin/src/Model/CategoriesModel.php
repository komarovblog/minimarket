<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

final class CategoriesModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();

        return $db->getQuery(true)
            ->select([
                $db->quoteName('c.id'),
                $db->quoteName('c.title'),
                $db->quoteName('c.alias'),
                $db->quoteName('c.parent_id'),
                $db->quoteName('c.state'),
                $db->quoteName('c.sort_order'),
                $db->quoteName('c.image'),
                $db->quoteName('c.image_alt'),
                $db->quoteName('p.title', 'parent_title'),
            ])
            ->from($db->quoteName('#__minimarket_categories', 'c'))
            ->join(
                'LEFT',
                $db->quoteName('#__minimarket_categories', 'p')
                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('c.parent_id')
            )
            ->order($db->quoteName('c.id') . ' ASC');
    }
    /** All categories are required to assemble the tree, not a single flat page. */
    public function getItems()
    {
        $db = $this->getDatabase();
        $db->setQuery($this->getListQuery());

        return (array) $db->loadObjectList();
    }

    /** One GROUP BY replaces one product-count query for every category. */
    public function getProductCounts(): array
    {
        $db = $this->getDatabase();
        $q = $db->getQuery(true)
            ->select([$db->quoteName('category_id'), 'COUNT(*) AS total'])
            ->from($db->quoteName('#__minimarket_products'))
            ->group($db->quoteName('category_id'));
        $db->setQuery($q);
        $counts = [];
        foreach ((array) $db->loadObjectList() as $row) {
            $counts[(int) $row->category_id] = (int) $row->total;
        }

        return $counts;
    }
}
