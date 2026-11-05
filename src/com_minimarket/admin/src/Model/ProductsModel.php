<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAdminTree;
use MiniMarket\Component\Minimarket\Administrator\Service\BundleStock;

final class ProductsModel extends ListModel
{
    protected string $entityType = 'simple';
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
                $db->quoteName('p.product_type'),
                $db->quoteName('p.state'),
                $db->quoteName('p.sort_order'),
                $db->quoteName('p.image'),
                $db->quoteName('p.image_alt'),
                $db->quoteName('p.category_id'),
                $db->quoteName('p.brand_id'),
                $db->quoteName('b.title', 'brand_title'),
                $db->quoteName('c.title', 'category_title'),
                '(SELECT GROUP_CONCAT(co.title ORDER BY co.title SEPARATOR 0x2C20) FROM '
                    . $db->quoteName('#__minimarket_product_collections', 'pc')
                    . ' INNER JOIN ' . $db->quoteName('#__minimarket_collections', 'co')
                    . ' ON co.id = pc.collection_id WHERE pc.product_id = p.id) AS collection_titles',
                '(SELECT GROUP_CONCAT(g.title ORDER BY g.title SEPARATOR 0x2C20) FROM '
                    . $db->quoteName('#__minimarket_product_group_members', 'mg')
                    . ' INNER JOIN ' . $db->quoteName('#__minimarket_product_groups', 'g')
                    . ' ON g.id = mg.group_id WHERE mg.product_id = p.id) AS group_titles',
            ])
            ->from($db->quoteName('#__minimarket_products', 'p'))
            ->join('INNER', $db->quoteName('#__minimarket_categories', 'c')
                . ' ON ' . $db->quoteName('p.category_id') . ' = ' . $db->quoteName('c.id'))
            ->join('LEFT', $db->quoteName('#__minimarket_brands', 'b')
                . ' ON ' . $db->quoteName('p.brand_id') . ' = ' . $db->quoteName('b.id'));

        $query->where('p.product_type = ' . $db->quote($this->entityType));

        $categoryId = (int) $this->getState('filter.category_id');
        if ($categoryId > 0) {
            $query->where($db->quoteName('p.category_id') . ' = ' . $categoryId);
        }

        $brandId = (int)$this->getState('filter.brand_id');
        if ($brandId > 0) $query->where('p.brand_id = ' . $brandId);
        $collectionId = (int)$this->getState('filter.collection_id');
        if ($collectionId > 0) {
            $query->where('EXISTS (SELECT 1 FROM ' .
                $db->quoteName('#__minimarket_product_collections', 'pcs') .
                ' WHERE pcs.product_id=p.id AND pcs.collection_id=' . $collectionId . ')');
        }

        $type=(string)$this->getState('filter.product_type');
        if (in_array($type,['simple','bundle'],true)) {
            $query->where($db->quoteName('p.product_type').'='.$db->quote($type));
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

    public function getItems()
    {
        $items=parent::getItems();
        $snapshot=null;
        foreach($items as $item) {
            if (($item->product_type ?? 'simple')==='bundle') {
                $snapshot ??= BundleStock::snapshot($this->getDatabase());
                $item->stock=BundleStock::available((int)$item->id,$snapshot);
            }
        }
        return $items;
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
