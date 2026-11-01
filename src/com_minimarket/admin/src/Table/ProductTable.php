<?php
namespace MiniMarket\Component\Minimarket\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\MediaPath;

final class ProductTable extends Table
{
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__minimarket_products', 'id', $db);
    }

    public function check()
    {
        $this->title = trim((string) $this->title);
        $this->alias = trim((string) $this->alias);
        $this->sku = trim((string) $this->sku);

        if ($this->title === '') {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_TITLE_REQUIRED'));

            return false;
        }

        if ($this->sku === '' || mb_strlen($this->sku) > 100) {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_SKU_REQUIRED'));

            return false;
        }

        $this->alias = OutputFilter::stringURLSafe($this->alias !== '' ? $this->alias : $this->title);

        if ($this->alias === '') {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_ALIAS_REQUIRED'));

            return false;
        }

        $price = trim((string) $this->price);

        // DECIMAL(15,2): 13 integral digits, no rounding of invalid input.
        if (!preg_match('/^(0|[1-9][0-9]{0,12})(\.[0-9]{1,2})?$/D', $price)) {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_PRICE_INVALID'));

            return false;
        }

        $stock = trim((string) $this->stock);

        if (!preg_match('/^(0|[1-9][0-9]*)$/D', $stock)
            || strlen($stock) > 10
            || (strlen($stock) === 10 && strcmp($stock, '4294967295') > 0)
        ) {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_STOCK_INVALID'));

            return false;
        }

        $this->price = $price;
        $this->stock = (int) $stock;
        $this->category_id = (int) $this->category_id;

        if ($this->category_id < 1) {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_CATEGORY_INVALID'));

            return false;
        }

        // The current release keeps the existing simple product SKU/price/stock.
        // Variant records become the source of truth only in the later atomic
        // SKU migration; do not add a second editable price or stock column.
        $order = trim((string) ($this->sort_order ?? '0'));
        if (!preg_match('/^(0|[1-9][0-9]*)$/D', $order)
            || strlen($order) > 10
            || (strlen($order) === 10 && strcmp($order, '4294967295') > 0)) {
            $this->setError(Text::_('COM_MINIMARKET_CATEGORY_ORDER_INVALID'));
            return false;
        }
        $this->sort_order = (int) $order;

        foreach (['image', 'og_image'] as $field) {
            $path = MediaPath::normalize((string) ($this->$field ?? ''));
            if ($path === null) {
                $this->setError(Text::_('COM_MINIMARKET_PRODUCT_MEDIA_INVALID'));
                return false;
            }
            $this->$field = $path;
        }

        foreach ([
            'image_alt' => 255, 'meta_title' => 255, 'meta_description' => 320,
            'og_title' => 255, 'og_description' => 320, 'og_image_alt' => 255,
        ] as $field => $limit) {
            $value = trim((string) ($this->$field ?? ''));
            if (mb_strlen($value) > $limit || preg_match('/[\x00-\x1f<>]/', $value)) {
                $this->setError(Text::_('COM_MINIMARKET_PRODUCT_SEO_INVALID'));
                return false;
            }
            $this->$field = $value;
        }

        $this->meta_robots = trim((string) ($this->meta_robots ?? ''));
        if (!in_array($this->meta_robots,
            ['', 'index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            $this->setError(Text::_('COM_MINIMARKET_PRODUCT_SEO_INVALID'));
            return false;
        }

        $db = $this->getDbo();
        $categoryQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_categories'))
            ->where($db->quoteName('id') . ' = ' . $this->category_id);
        $db->setQuery($categoryQuery);

        if ((int) $db->loadResult() !== 1) {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_CATEGORY_INVALID'));

            return false;
        }

        $aliasQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_products'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote($this->alias))
            ->where($db->quoteName('id') . ' <> ' . (int) $this->id);
        $db->setQuery($aliasQuery);

        if ((int) $db->loadResult() > 0) {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_PRODUCT_ALIAS_DUPLICATE'));

            return false;
        }

        if (!\in_array((int) $this->state, [0, 1], true)) {
            $this->setError(Text::_('COM_MINIMARKET_ERROR_STATE_INVALID'));

            return false;
        }

        $crossQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__minimarket_categories'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote($this->alias));
        $db->setQuery($crossQuery);

        if ((int) $db->loadResult() > 0) {
            $this->setError('Product aliases must differ from category aliases');

            return false;
        }

        return parent::check();
    }
}
