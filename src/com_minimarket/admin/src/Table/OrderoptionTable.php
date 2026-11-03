<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

final class OrderoptionTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_order_options', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        $this->title = trim((string) ($this->title ?? ''));
        $this->code = strtolower(trim((string) ($this->code ?? '')));
        $this->state = (int) ($this->state ?? 1);
        $this->sort_order = (int) ($this->sort_order ?? 0);
        if ($this->code === '' && $this->title !== '') {
            $this->code = 'option-' . substr(hash('sha256', mb_strtolower($this->title)), 0, 14);
        }
        if ($this->title === '' || mb_strlen($this->title) > 255
            || preg_match('/[\x00-\x1f<>]/u', $this->title)
            || !preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $this->code)
            || !in_array($this->state, [0,1], true)
            || $this->sort_order < 0 || $this->sort_order > 4294967295) {
            $this->setError(Text::_('COM_MINIMARKET_ORDER_OPTION_INVALID'));
            return false;
        }
        $db = $this->getDbo();
        $q = $db->getQuery(true)->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_order_options'))
            ->where($db->quoteName('code') . ' = ' . $db->quote($this->code))
            ->where($db->quoteName('id') . ' <> ' . (int) ($this->id ?? 0));
        $db->setQuery($q);
        if ((int) $db->loadResult() > 0) {
            $this->setError(Text::_('COM_MINIMARKET_ORDER_OPTION_DUPLICATE'));
            return false;
        }
        return parent::check();
    }
}
