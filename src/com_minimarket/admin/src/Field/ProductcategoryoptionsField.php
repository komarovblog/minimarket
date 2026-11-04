<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductCatalogAxes;

/**
 * All category option definitions are prepared in bounded queries, then
 * react to category selection without a second network call or page reload.
 * Linked option/value IDs are retained as stable foreign keys on save.
 */
final class ProductcategoryoptionsField extends FormField
{
    protected $type = 'Productcategoryoptions';

    protected function getInput(): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $catalog = ProductCatalogAxes::forAllCategories($db);
        $esc = static fn(string $s): string =>
            htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $json = json_encode($catalog, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG);
        if (strlen($json) > 1800000) {
            throw new \RuntimeException('COM_MINIMARKET_PRODUCT_OPTIONS_TOO_MANY');
        }
        $texts = [
            'saveCategory' => Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_SAVE_CATEGORY'),
            'empty' => Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_EMPTY'),
            'required' => Text::_('COM_MINIMARKET_CATATTR_MODE_REQUIRED'),
            'optional' => Text::_('COM_MINIMARKET_CATATTR_MODE_ENABLE'),
            'tooMany' => Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_TOO_MANY'),
        ];
        return '<div data-mm-category-catalog data-mm-catalog="' . $esc($json) .
            '" data-mm-catalog-labels="' . $esc(json_encode($texts,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)) . '">' .
            '<p class="small text-muted">' .
            $esc(Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_CATEGORY_HELP')) . '</p>' .
            '<div class="mm-option-list" data-mm-category-option-list aria-live="polite"></div>' .
            '<p class="small text-muted mt-2">' .
            $esc(Text::_('COM_MINIMARKET_PRODUCT_OPTIONS_AUTOMATIC')) .
            '</p></div>';
    }
}
