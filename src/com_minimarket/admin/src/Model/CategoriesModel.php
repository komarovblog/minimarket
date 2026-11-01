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
                $db->quoteName('id'),
                $db->quoteName('title'),
                $db->quoteName('alias'),
                $db->quoteName('state'),
                $db->quoteName('parent_id'),
            ])
            ->from($db->quoteName('#__minimarket_categories'))
            ->order($db->quoteName('id') . ' DESC');
    }
}
