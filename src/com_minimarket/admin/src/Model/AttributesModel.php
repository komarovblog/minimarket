<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

final class AttributesModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();

        return $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.code'),
                $db->quoteName('a.input_type'),
                $db->quoteName('a.is_variant'),
                $db->quoteName('a.is_filterable'),
                $db->quoteName('a.state'),
                $db->quoteName('a.sort_order'),
            ])
            ->from($db->quoteName('#__minimarket_attributes', 'a'))
            ->order($db->quoteName('a.sort_order') . ' ASC, ' . $db->quoteName('a.id') . ' ASC');
    }
}
