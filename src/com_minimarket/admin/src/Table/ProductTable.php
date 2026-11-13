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
        // Joomla bulk publish expects the standard 'published' alias.
        $this->setColumnAlias('published', 'state');
    }

    public function check()
    {
        $this->title = trim((string) $this->title);
        $this->alias = trim((string) $this->alias);
        $this->sku = trim((string) $this->sku);
        $this->product_type=(string)($this->product_type ?? 'simple');
        // How the item is delivered belongs to the product, not the store.
        $this->delivery_type=(string)($this->delivery_type??'physical');
        if(!in_array($this->delivery_type,['physical','digital'],true)
            ||($this->product_type==='bundle' && $this->delivery_type==='digital')){
            $this->setError(Text::_('COM_MINIMARKET_DELIVERY_TYPE_INVALID'));
            return false;
        }
        $this->digital_delivery_mode=(string)($this->digital_delivery_mode??'file');
        if(!in_array($this->digital_delivery_mode,['file','license','cloud','manual'],true)){
            $this->setError(Text::_('COM_MINIMARKET_DIGITAL_MODE_INVALID'));
            return false;
        }
        $this->digital_delivery_note=trim(strip_tags((string)($this->digital_delivery_note??'')));
        if(mb_strlen($this->digital_delivery_note)>2000
            ||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u',$this->digital_delivery_note)){
            $this->setError(Text::_('COM_MINIMARKET_DIGITAL_MODE_INVALID'));
            return false;
        }
        if (!in_array($this->product_type,['simple','bundle'],true)) {
            $this->setError(Text::_('COM_MINIMARKET_BUNDLE_TYPE_INVALID'));
            return false;
        }
        $this->bundle_price_mode=(string)($this->bundle_price_mode??'fixed');
        $discount=(string)($this->bundle_discount_pct??'0');
        if (!in_array($this->bundle_price_mode,['fixed','discount'],true)
            || !preg_match('/^(?:[0-9]{1,2}|100)(?:\.[0-9]{1,2})?$/D',$discount)
            || (float)$discount>100) {
            $this->setError(Text::_('COM_MINIMARKET_BUNDLE_PRICE_INVALID'));
            return false;
        }
        $this->bundle_discount_pct=number_format((float)$discount,2,'.','');

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

        // Unknown procurement cost is NULL, never silently substituted with zero.
        $costPrice=trim((string)($this->cost_price??''));
        if($costPrice==='') {
            $this->cost_price=null;
        } elseif(!preg_match('/^(0|[1-9][0-9]{0,12})(?:\\.[0-9]{1,2})?$/D',$costPrice)) {
            $this->setError(Text::_('COM_MINIMARKET_COST_PRICE_INVALID'));
            return false;
        } else {
            $this->cost_price=$costPrice;
        }

        // Comparison price is optional and never changes the actual selling price.
        $oldPrice=trim((string)($this->old_price??''));
        if($oldPrice==='') {
            $this->old_price=null;
        } elseif(!preg_match('/^(0|[1-9][0-9]{0,12})(?:\.[0-9]{1,2})?$/D',$oldPrice)
            || (float)$oldPrice<=0) {
            $this->setError(Text::_('COM_MINIMARKET_BADGE_OLD_PRICE_INVALID'));
            return false;
        } else {
            $this->old_price=$oldPrice;
        }

        foreach(['badge_sale','badge_new','badge_popular','badge_benefit'] as $flag) {
            $value=(string)($this->$flag??0);
            if(!in_array($value,['0','1'],true)) {
                $this->setError(Text::_('COM_MINIMARKET_BADGE_INVALID'));
                return false;
            }
            $this->$flag=(int)$value;
        }
        foreach (['badge_sale_display' => 'percent',
            'badge_benefit_display' => 'money'] as $field => $default) {
            $mode = (string) ($this->$field ?? $default);
            if (!in_array($mode, ['money', 'percent'], true)) {
                $this->setError(Text::_('COM_MINIMARKET_BADGE_INVALID'));
                return false;
            }
            $this->$field = $mode;
        }
        foreach(['badge_new_days','badge_popular_days'] as $duration) {
            $days=trim((string)($this->$duration??''));
            if($days==='') {
                $this->$duration=null;
            } elseif(!preg_match('/^[1-9][0-9]{0,4}$/D',$days)
                || (int)$days>36500) {
                $this->setError(Text::_('COM_MINIMARKET_BADGE_DAYS_INVALID'));
                return false;
            } else {
                $this->$duration=(int)$days;
            }
        }
        // The two price strategies have mutually exclusive promotional flags.
        if($this->product_type==='bundle' && $this->bundle_price_mode==='discount'
            && $this->badge_sale===1) {
            $this->setError(Text::_('COM_MINIMARKET_BADGE_SALE_FIXED_ONLY'));
            return false;
        }
        if($this->badge_benefit===1
            && ($this->product_type!=='bundle'||$this->bundle_price_mode!=='discount')) {
            $this->setError(Text::_('COM_MINIMARKET_BADGE_BENEFIT_INVALID'));
            return false;
        }
        // Dynamic bundle prices may change when components are edited;
        // storefront recomputes comparison against the derived price.
        if($this->badge_sale===1 && ($this->old_price===null
            || ($this->product_type!=='bundle' || $this->bundle_price_mode==='fixed')
                && (float)$this->old_price<=(float)$price)) {
            $this->setError(Text::_('COM_MINIMARKET_BADGE_SALE_INVALID'));
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
        $stockMode = (string) ($this->option_stock_mode ?? '0');
        if (!in_array($stockMode,['0','1'],true)) {
            $this->setError(Text::_('COM_MINIMARKET_OPTION_STOCK_INVALID'));
            return false;
        }
        $this->option_stock_mode = (int)$stockMode;
        foreach(['shipping_weight_grams','shipping_volume_cm3'] as $field) {
            $raw=trim((string)($this->$field??'0'));
            if(!preg_match('/^(0|[1-9][0-9]{0,9})$/D',$raw)
                || (strlen($raw)===10&&strcmp($raw,'4294967295')>0)){
                $this->setError(Text::_('COM_MINIMARKET_SHIPPING_METRICS_INVALID'));
                return false;
            }
            $this->$field=(int)$raw;
        }
        $this->category_id = (int) $this->category_id;
        $brandId = (int)($this->brand_id ?? 0);
        if ($brandId < 0) {
            $this->setError(Text::_('COM_MINIMARKET_PRODUCT_BRAND_INVALID'));
            return false;
        }
        $this->brand_id = $brandId > 0 ? $brandId : null;

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

        if ($brandId > 0) {
            $brandQuery = $db->getQuery(true)->select('COUNT(*)')
                ->from($db->quoteName('#__minimarket_brands'))
                ->where($db->quoteName('id') . ' = ' . $brandId);
            $db->setQuery($brandQuery);
            if ((int)$db->loadResult() !== 1) {
                $this->setError(Text::_('COM_MINIMARKET_PRODUCT_BRAND_INVALID'));
                return false;
            }
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
