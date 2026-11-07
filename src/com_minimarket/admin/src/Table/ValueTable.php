<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

final class ValueTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_attribute_values', 'id', $db);
        // Joomla bulk publish expects the standard 'published' alias.
        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        $this->title = trim((string) $this->title);
        $this->code = strtolower(trim((string) $this->code));
        $this->attribute_id = (int) $this->attribute_id;

        if ($this->title === '' || mb_strlen($this->title) > 255) {
            $this->setError(Text::_('COM_MINIMARKET_VALUE_TITLE_INVALID'));

            return false;
        }

        if (!preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $this->code)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_CODE_INVALID'));

            return false;
        }

        if (!\in_array((int) $this->state, [0, 1], true)) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_FLAGS_INVALID'));

            return false;
        }

        $this->state = (int) $this->state;
        $sort = trim((string) $this->sort_order);

        if (!preg_match('/^(0|[1-9][0-9]*)$/D', $sort)
            || strlen($sort) > 10
            || (strlen($sort) === 10 && strcmp($sort, '4294967295') > 0)
        ) {
            $this->setError(Text::_('COM_MINIMARKET_ATTRIBUTE_SORT_INVALID'));

            return false;
        }

        $this->sort_order = (int) $sort;

        if ($this->attribute_id < 1) {
            $this->setError(Text::_('COM_MINIMARKET_VALUE_ATTRIBUTE_INVALID'));

            return false;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('value_source'))
            ->from($db->quoteName('#__minimarket_attributes'))
            ->where($db->quoteName('id') . ' = ' . $this->attribute_id);
        $db->setQuery($query);

        if ((string) $db->loadResult() !== 'preset') {
            $this->setError(Text::_('COM_MINIMARKET_VALUE_ATTRIBUTE_INVALID'));

            return false;
        }

        $duplicateQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_attribute_values'))
            ->where($db->quoteName('attribute_id') . ' = ' . $this->attribute_id)
            ->where($db->quoteName('code') . ' = ' . $db->quote($this->code))
            ->where($db->quoteName('id') . ' <> ' . (int) $this->id);
        $db->setQuery($duplicateQuery);

        if ((int) $db->loadResult() > 0) {
            $this->setError(Text::_('COM_MINIMARKET_VALUE_CODE_DUPLICATE'));

            return false;
        }

        return parent::check();
    }
}
