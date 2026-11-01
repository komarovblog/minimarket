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
                $db->quoteName('p.title', 'parent_title'),
            ])
            ->from($db->quoteName('#__minimarket_categories', 'c'))
            ->join(
                'LEFT',
                $db->quoteName('#__minimarket_categories', 'p')
                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('c.parent_id')
            )
            ->order($db->quoteName('c.parent_id') . ' ASC, ' . $db->quoteName('c.sort_order') . ' ASC, ' . $db->quoteName('c.id') . ' ASC');
    }
}
