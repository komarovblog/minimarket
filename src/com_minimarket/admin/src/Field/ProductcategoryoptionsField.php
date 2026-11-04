<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryOrderOptions;

/**
 * Shows the catalog options inherited by this product's category.
 * An explicit action copies selected definitions into the one existing
 * SKU option editor; it never silently rewrites saved local SKU axes.
 */
final class ProductcategoryoptionsField extends FormField
{
    protected $type = 'Productcategoryoptions';

    protected function getInput(): string
    {
        $categoryId = (int) ($this->form->getValue('category_id') ?? 0);
        $esc = static fn (string $s): string =>
            htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($categoryId < 1) {
            return '<p class="text-muted">' .
                $esc(Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_SAVE_CATEGORY')) . '</p>';
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tree = CategoryOrderOptions::hierarchy($db);
        $modes = CategoryOrderOptions::resolveModes(
            $categoryId, $tree['parents'], $tree['rules']
        );
        if (!$modes) {
            return '<p class="text-muted">' .
                $esc(Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_EMPTY')) . '</p>';
        }
        $q = $db->getQuery(true)->select(['id', 'title', 'code', 'state'])
            ->from($db->quoteName('#__minimarket_order_options'))
            ->where('id IN (' . implode(',', array_map('intval', array_keys($modes))) . ')')
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $options = (array) $db->loadAssocList();
        $ids = array_map('intval', array_column($options, 'id'));
        $q = $db->getQuery(true)->select(['id','option_id','title','code','state'])
            ->from($db->quoteName('#__minimarket_order_option_values'))
            ->where('option_id IN (' . implode(',', $ids ?: [0]) . ')')
            ->order('sort_order ASC, id ASC');
        $db->setQuery($q);
        $values = [];
        foreach ((array) $db->loadAssocList() as $row) {
            if ((int) $row['state'] !== 1) continue;
            $values[(int) $row['option_id']][] = [
                'title' => (string) $row['title'],
                'code' => (string) $row['code'],
            ];
        }
        $axes = [];
        $html = '<div class="mm-category-option-picker"><p class="text-muted">' .
            $esc(Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_CATEGORY_HELP')) .
            '</p><div class="mm-option-list">';
        foreach ($options as $option) {
            if ((int) $option['state'] !== 1) continue;
            $id = (int) $option['id'];
            $choices = $values[$id] ?? [];
            if (!$choices) continue;
            $axes[] = [
                'code' => (string) $option['code'],
                'title' => (string) $option['title'],
                'values' => $choices,
            ];
            $html .= '<div class="mm-option-list__item"><strong>' .
                $esc((string) $option['title']) . '</strong> <span class="text-muted small">(' .
                $esc(Text::_($modes[$id] === 2
                    ? 'COM_MINIMARKET_CATATTR_MODE_REQUIRED'
                    : 'COM_MINIMARKET_CATATTR_MODE_ENABLE')) .
                ')</span><div class="small">' .
                $esc(implode(' · ', array_column($choices, 'title'))) .
                '</div></div>';
        }
        $html .= '</div>';
        if ($axes && count($axes) <= 4) {
            $total = 1;
            foreach ($axes as $axis) $total *= count($axis['values']);
            if ($total <= 200) {
                $encoded = $esc(json_encode($axes,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP));
                $html .= '<button type="button" class="btn btn-outline-primary btn-sm mt-2" ' .
                    'data-mm-copy-category-options data-mm-category="' . $categoryId .
                    '" data-mm-axes="' . $encoded . '">' .
                    $esc(Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_USE_CATEGORY')) .
                    '</button><p class="small text-muted mt-2">' .
                    $esc(Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_COPY_SAFE')) . '</p>';
            } else {
                $html .= '<p class="text-warning">' .
                    $esc(Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_TOO_MANY')) . '</p>';
            }
        }
        return $html . '</div>';
    }
}
