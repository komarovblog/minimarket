<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Language\Text;

/**
 * Bridges older MiniMarket custom lists to Joomla 6's actual SearchTools.
 * No duplicate filter HTML, controls or custom pagination is rendered.
 *
 * For lists already backed by ListModel use getFilterForm/getPagination instead.
 */
final class NativeListTools
{
    /**
     * Joomla SearchTools posts filter[search], filter[status],
     * list[fullordering] and list[limit]. Older links using flat
     * filter_search/filter_status still work until bookmarks expire.
     */
    public static function importRequest(): void
    {
        $input = Factory::getApplication()->input;

        if (strtoupper((string) $input->getMethod()) !== 'POST') {
            return;
        }

        $filters = $input->post->get('filter', [], 'array');

        if (is_array($filters)) {
            foreach ($filters as $key => $value) {
                if (!preg_match('/^[a-z][a-z0-9_]{0,48}$/D', (string) $key)
                    || !is_scalar($value)
                ) {
                    continue;
                }

                $input->set('filter_' . $key, mb_substr((string) $value, 0, 200));
            }
        }

        $list = $input->post->get('list', [], 'array');

        if (is_array($list)) {
            if (isset($list['fullordering']) && is_scalar($list['fullordering'])) {
                $input->set('filter_sort', mb_substr((string) $list['fullordering'], 0, 100));
            }

            if (isset($list['limit']) && ctype_digit((string) $list['limit'])) {
                $input->set('limit', (int) $list['limit']);
            }
        }
    }

    /** Use real Joomla 6 Form fields + joomla.searchtools.default layout. */
    public static function render(array $config, int $total = 0): string
    {
        $view = (string) ($config['view'] ?? '');

        if (!preg_match('/^[a-z][a-z0-9]{1,40}$/D', $view)) {
            throw new \InvalidArgumentException('Invalid MiniMarket list view');
        }

        $esc = static fn($v): string => htmlspecialchars(
            (string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8'
        );

        $label = $esc($config['searchLabel'] ?? Text::_('JSEARCH_FILTER'));
        $xml = '<form><fields name="filter">'
            . '<field name="search" type="text" inputmode="search"'
            . ' label="' . $label . '" hint="JSEARCH_FILTER" />';

        $bound = ['search' => (string) ($config['query'] ?? '')];
        $active = [];

        foreach ((array) ($config['filters'] ?? []) as $filter) {
            $field = (string) ($filter['name'] ?? '');

            if (!preg_match('/^filter_([a-z][a-z0-9_]*)$/D', $field, $found)) {
                throw new \InvalidArgumentException('Invalid MiniMarket filter name');
            }

            $key = $found[1];
            $value = (string) ($filter['value'] ?? '');
            $default = (string) ($filter['default'] ?? '');
            $bound[$key] = $value;

            if ($value !== $default) {
                $active[$key] = $value;
            }

            $xml .= '<field name="' . $esc($key) . '"'
                . ' type="' . (($filter['type'] ?? '') === 'text' ? 'text' : 'list') . '"'
                . ' label="' . $esc($filter['label'] ?? $key) . '"'
                . ' class="js-select-submit-on-change"';

            if (($filter['type'] ?? '') === 'text') {
                $xml .= ' hint="JSEARCH_FILTER" />';
                continue;
            }

            $xml .= ' validate="options">';

            foreach ((array) ($filter['options'] ?? []) as $option => $optionLabel) {
                $xml .= '<option value="' . $esc($option) . '">'
                    . $esc($optionLabel) . '</option>';
            }

            $xml .= '</field>';
        }

        $xml .= '</fields><fields name="list">'
            . '<field name="fullordering" type="list" label="JGLOBAL_SORT_BY"'
            . ' class="js-select-submit-on-change" validate="options">';

        $sortOptions = (array) ($config['sortOptions'] ?? [
            'newest' => Text::_('COM_MINIMARKET_LIST_NEWEST'),
            'oldest' => Text::_('COM_MINIMARKET_LIST_OLDEST'),
        ]);

        foreach ($sortOptions as $option => $optionLabel) {
            $xml .= '<option value="' . $esc($option) . '">'
                . $esc($optionLabel) . '</option>';
        }

        $xml .= '</field><field name="limit" type="limitbox"'
            . ' class="js-select-submit-on-change" label="JGLOBAL_LIST_LIMIT"'
            . ' default="20" /></fields></form>';

        $form = Form::getInstance('com_minimarket.native_search.' . $view, $xml);
        $form->bind([
            'filter' => $bound,
            'list' => [
                'fullordering' => (string) ($config['sort'] ?? 'newest'),
                'limit' => (int) ($config['limit'] ?? 20),
            ],
        ]);

        // The native layout needs an object exposing the same fields as a
        // standard Joomla ListModel view, nothing else.
        $native = (object) [
            'filterForm' => $form,
            'activeFilters' => $active,
            'total' => $total,
        ];

        return LayoutHelper::render('joomla.searchtools.default', ['view' => $native]);
    }

    /** Core Pagination footer operates on the same POST adminForm. */
    public static function pagination(int $total, int $offset, int $limit): string
    {
        if ($total <= $limit) {
            return '';
        }

        $pagination = new Pagination($total, $offset, $limit);

        return $pagination->getListFooter();
    }
}
