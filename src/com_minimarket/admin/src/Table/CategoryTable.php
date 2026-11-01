<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
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

        // A category can have any existing parent except itself or its descendants.
        // Preserve the column on edit: never silently reset it to root.
        $this->parent_id = (int) ($this->parent_id ?? 0);

        if ($this->parent_id < 0) {
            $this->setError(Text::_('COM_MINIMARKET_CATEGORY_PARENT_INVALID'));

            return false;
        }

        if ($this->parent_id > 0) {
            $seen = [];
            $parentId = $this->parent_id;

            while ($parentId > 0) {
                if (isset($seen[$parentId]) || ($this->id > 0 && $parentId === (int) $this->id)) {
                    $this->setError(Text::_('COM_MINIMARKET_CATEGORY_PARENT_CYCLE'));

                    return false;
                }

                $seen[$parentId] = true;
                $parentQuery = $this->getDbo()->getQuery(true)
                    ->select($this->getDbo()->quoteName('parent_id'))
                    ->from($this->getDbo()->quoteName('#__minimarket_categories'))
                    ->where($this->getDbo()->quoteName('id') . ' = ' . $parentId);
                $this->getDbo()->setQuery($parentQuery);
                $next = $this->getDbo()->loadResult();

                if ($next === null) {
                    $this->setError(Text::_('COM_MINIMARKET_CATEGORY_PARENT_MISSING'));

                    return false;
                }

                $parentId = (int) $next;
            }
        }

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
