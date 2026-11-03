<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductGroups;

final class GroupTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_product_groups', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        $this->title = trim((string) ($this->title ?? ''));
        $this->type = (string) ($this->type ?? 'similar');
        $this->state = (int) ($this->state ?? 1);
        $this->sort_order = (int) ($this->sort_order ?? 0);
        if ($this->title === '' || mb_strlen($this->title) > 255
            || preg_match('/[\x00-\x1f<>]/u', $this->title)
            || !in_array($this->type, ProductGroups::TYPES, true)
            || !in_array($this->state, [0, 1], true)
            || $this->sort_order < 0) {
            $this->setError(Text::_('COM_MINIMARKET_PRODUCT_GROUP_INVALID'));
            return false;
        }
        $db = $this->getDbo();
        $q = $db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_product_groups'))
            ->where($db->quoteName('title') . ' = ' . $db->quote($this->title))
            ->where($db->quoteName('id') . ' <> ' . (int) ($this->id ?? 0));
        $db->setQuery($q);
        if ((int) $db->loadResult() > 0) {
            $this->setError(Text::_('COM_MINIMARKET_GROUP_DUPLICATE'));
            return false;
        }
        return parent::check();
    }
}
