<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

final class ProductsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();

        return $db->getQuery(true)
            ->select([
                $db->quoteName('p.id'),
                $db->quoteName('p.title'),
                $db->quoteName('p.sku'),
                $db->quoteName('p.price'),
                $db->quoteName('p.stock'),
                $db->quoteName('p.state'),
                $db->quoteName('c.title', 'category_title'),
            ])
            ->from($db->quoteName('#__minimarket_products', 'p'))
            ->join('INNER', $db->quoteName('#__minimarket_categories', 'c') . ' ON ' . $db->quoteName('p.category_id') . ' = ' . $db->quoteName('c.id'))
            ->order($db->quoteName('p.id') . ' DESC');
    }
}
