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

        $order = trim((string) ($this->sort_order ?? 0));
        if (!preg_match('/^(0|[1-9][0-9]*)$/D', $order)
            || strlen($order) > 10
            || (strlen($order) === 10 && strcmp($order, '4294967295') > 0)) {
            $this->setError(Text::_('COM_MINIMARKET_CATEGORY_ORDER_INVALID'));
            return false;
        }
        $this->sort_order = (int) $order;
        foreach (['show_children', 'show_products'] as $field) {
            $flag = (int) ($this->$field ?? -1);
            if (!in_array($flag, [-1, 0, 1], true)) {
                $this->setError(Text::_('COM_MINIMARKET_CATEGORY_DISPLAY_INVALID'));
                return false;
            }
            $this->$field = $flag;
        }
        $this->image = trim((string) ($this->image ?? ''));
        $this->image_alt = trim((string) ($this->image_alt ?? ''));
        if (mb_strlen($this->image) > 1024 || mb_strlen($this->image_alt) > 255
            || ($this->image !== '' && (!str_starts_with($this->image, 'images/')
                || str_contains($this->image, '..')
                || preg_match('/[\x00-\x1f<>]/', $this->image)))) {
            $this->setError(Text::_('COM_MINIMARKET_CATEGORY_IMAGE_INVALID'));
            return false;
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
