<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

/**
 * Matrix preview/actions for native Joomla options and SKU subforms.
 * All edited values live in the two repeatable Subforms, not in a second
 * hidden JSON model that might silently overwrite user edits.
 */
final class VariantbuilderField extends FormField
{
    protected $type = 'Variantbuilder';

    protected function getInput(): string
    {
        $escape = static fn(string $value): string =>
            htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $labels = [];
        foreach ([
            'preview' => 'COM_MINIMARKET_LOCAL_PREVIEW',
            'confirm' => 'COM_MINIMARKET_LOCAL_CONFIRM',
            'created' => 'COM_MINIMARKET_LOCAL_CREATED',
            'incomplete' => 'COM_MINIMARKET_LOCAL_INCOMPLETE',
            'empty' => 'COM_MINIMARKET_LOCAL_EMPTY',
            'limit' => 'COM_MINIMARKET_LOCAL_LIMIT',
            'tooMany' => 'COM_MINIMARKET_LOCAL_TOO_MANY',
            'addFailed' => 'COM_MINIMARKET_LOCAL_ADD_FAILED',
            'nativeUnavailable' => 'COM_MINIMARKET_LOCAL_SUBFORM_MISSING',
            'combination' => 'COM_MINIMARKET_VARIANT_COMBINATION',
            'selectAll' => 'JGLOBAL_CHECK_ALL',
            'addSku' => 'COM_MINIMARKET_SKU_ADD_POSITION',
            'copySku' => 'COM_MINIMARKET_SKU_COPY_POSITION',
            'chooseSku' => 'COM_MINIMARKET_SKU_SELECT_POSITION',
            'allSku' => 'COM_MINIMARKET_SKU_SELECT_ALL',
            'bulkPrice' => 'COM_MINIMARKET_SKU_BULK_PRICE',
            'bulkStock' => 'COM_MINIMARKET_SKU_BULK_STOCK',
            'bulkApply' => 'COM_MINIMARKET_SKU_BULK_APPLY',
            'bulkRequired' => 'COM_MINIMARKET_SKU_BULK_REQUIRED',
            'bulkSelection' => 'COM_MINIMARKET_SKU_BULK_SELECTION',
            'bulkInvalid' => 'COM_MINIMARKET_SKU_BULK_INVALID',
            'bulkDone' => 'COM_MINIMARKET_SKU_BULK_DONE',
            'copyDone' => 'COM_MINIMARKET_SKU_COPY_DONE',
            'manualDone' => 'COM_MINIMARKET_SKU_MANUAL_DONE',
        ] as $key => $languageKey) {
            $labels[$key] = Text::_($languageKey);
        }

        return '<div data-mm-local-builder data-mm-max="200" data-mm-labels="'
            . $escape(json_encode($labels, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG))
            . '">'
            . '<p class="text-muted">' . $escape(Text::_('COM_MINIMARKET_LOCAL_VARIANTS_HELP')) . '</p>'
            . '<div class="d-flex flex-wrap gap-2 align-items-end mb-3" data-mm-sku-actions>'
            . '<button type="button" class="btn btn-outline-primary btn-sm" data-mm-add-sku>'
            . $escape(Text::_('COM_MINIMARKET_SKU_ADD_POSITION')) . '</button>'
            . '<label class="form-check mb-1 ms-2"><input class="form-check-input" type="checkbox" data-mm-sku-all>'
            . '<span class="form-check-label">' . $escape(Text::_('COM_MINIMARKET_SKU_SELECT_ALL')) . '</span></label>'
            . '<label class="form-label mb-0">' . $escape(Text::_('COM_MINIMARKET_SKU_BULK_PRICE'))
            . '<input type="number" class="form-control form-control-sm" min="0" max="9999999999999.99" step="0.01"'
            . ' data-mm-sku-price></label>'
            . '<label class="form-label mb-0">' . $escape(Text::_('COM_MINIMARKET_SKU_BULK_STOCK'))
            . '<input type="number" class="form-control form-control-sm" min="0" max="4294967295" step="1"'
            . ' data-mm-sku-stock></label>'
            . '<button type="button" class="btn btn-outline-secondary btn-sm" data-mm-sku-apply>'
            . $escape(Text::_('COM_MINIMARKET_SKU_BULK_APPLY')) . '</button>'
            . '</div>'
            . '<button type="button" class="btn btn-primary mb-3" data-mm-generate>'
            . $escape(Text::_('COM_MINIMARKET_LOCAL_GENERATE')) . '</button>'
            . '<div data-mm-matrix-preview class="mb-3" aria-live="polite"></div>'
            . '<p data-mm-local-feedback role="status" class="small text-muted" aria-live="polite"></p>'
            . '</div>';
    }
}
