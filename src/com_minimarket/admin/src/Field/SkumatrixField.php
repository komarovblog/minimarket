<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductCatalogAxes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductLocalDimensions;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductVariants;

/**
 * One editable SKU matrix, with a column for every category order option.
 * The existing physical variant table is the only persistence source.
 */
final class SkumatrixField extends FormField
{
    protected $type = 'Skumatrix';

    protected function getInput(): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $productId = (int) ($this->form->getValue('id')
            ?: Factory::getApplication()->input->getInt('id', 0));
        $config = [
            'catalog' => ProductCatalogAxes::forAllCategories($db),
            'existingAxes' => $productId
                ? ProductLocalDimensions::load($db, $productId) : [],
            'existingRows' => $productId
                ? array_values(ProductVariants::formRows($db, $productId)) : [],
            'productId' => $productId,
        ];
        $json = json_encode($config,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (strlen($json) > 2200000) {
            throw new \RuntimeException('COM_MINIMARKET_PRODUCT_OPTIONS_TOO_MANY');
        }
        $esc = static fn (string $v): string =>
            htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $labels = [
            'sku' => Text::_('COM_MINIMARKET_VARIANT_SKU'),
            'stock' => Text::_('COM_MINIMARKET_VARIANT_STOCK'),
            'active' => Text::_('COM_MINIMARKET_MATRIX_ACTIVE'),
            'empty' => Text::_('COM_MINIMARKET_MATRIX_NO_OPTIONS'),
            'limit' => Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_TOO_MANY'),
            'noVariants' => Text::_('COM_MINIMARKET_MATRIX_NO_VARIANTS'),
            'sharedStock' => Text::_('COM_MINIMARKET_OPTION_STOCK_SHARED'),
            'photoShow' => Text::_('COM_MINIMARKET_PHOTO_SHOW_SKUS'),
            'photoCover' => Text::_('COM_MINIMARKET_PHOTO_COVER_SKUS'),
            'photoVisibility' => Text::_('COM_MINIMARKET_PHOTO_VISIBILITY'),
            'everywhere' => Text::_('COM_MINIMARKET_PHOTO_EVERYWHERE'),
            'selected' => Text::_('COM_MINIMARKET_PHOTO_SELECTED_SKUS'),
            'coverHelp' => Text::_('COM_MINIMARKET_PHOTO_COVER_HELP'),
            'noActive' => Text::_('COM_MINIMARKET_PHOTO_NO_ACTIVE'),
            'inactive' => Text::_('COM_MINIMARKET_PHOTO_INACTIVE'),
            'categoryIntro' => Text::_('COM_MINIMARKET_MATRIX_CATEGORY_INTRO'),
            'categoryEmpty' => Text::_('COM_MINIMARKET_MATRIX_CATEGORY_EMPTY'),
            'matrix' => Text::_('COM_MINIMARKET_MATRIX_TITLE'),
            'invalidExisting' => Text::_('COM_MINIMARKET_VARIANTS_INVALID'),
        ];
        $labelJson = json_encode($labels,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return '<div data-mm-sku-matrix data-mm-config="' . $esc($json) .
            '" data-mm-matrix-labels="' . $esc($labelJson) . '">' .
            '<p class="small text-muted">' .
                $esc(Text::_('COM_MINIMARKET_MATRIX_HELP')) . '</p>' .
            '<div data-mm-matrix-table aria-live="polite"></div>' .
            '<input type="hidden" name="' . $esc($this->name) .
                '" id="' . $esc($this->id) .
                '" value="[]" data-mm-matrix-payload>' .
            '</div>';
    }
}
