<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

final class CategoryTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_categories', 'id', $db);
    }

    public function check()
    {
        $this->title = trim((string) $this->title);
        $this->alias = trim((string) $this->alias);

        if ($this->title === '') {
            $this->setError('Category title cannot be empty');

            return false;
        }

        // Category IDs, not nested aliases, are used by the forthcoming SEF router.
        $this->alias = OutputFilter::stringURLSafe($this->alias !== '' ? $this->alias : $this->title);

        if ($this->alias === '') {
            $this->setError('Category alias must contain letters or numbers');

            return false;
        }

        // Hierarchical editing starts in M1b; avoid unsupported parent loops in M1a.
        $this->parent_id = 0;

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_categories'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote($this->alias))
            ->where($db->quoteName('id') . ' <> ' . (int) $this->id);

        $db->setQuery($query);

        if ((int) $db->loadResult() > 0) {
            $this->setError('Category alias already exists');

            return false;
        }

        $crossQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_products'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote($this->alias));
        $db->setQuery($crossQuery);

        if ((int) $db->loadResult() > 0) {
            $this->setError('Category aliases must differ from product aliases');

            return false;
        }

        return parent::check();
    }
}
