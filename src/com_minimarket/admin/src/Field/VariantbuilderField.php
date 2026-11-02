<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductLocalDimensions;

/**
 * Native-looking local SKU option builder. Uses the normal Joomla product form
 * and a single validated JSON form value; no global attributes prerequisite.
 */
final class VariantbuilderField extends FormField
{
    protected $type='Variantbuilder';

    protected function getInput(): string
    {
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $productId=(int)Factory::getApplication()->input->getInt('id',0);
        if ($productId > 0 && ProductLocalDimensions::hasLegacyVariants($db,$productId)) {
            return '<p class="alert alert-info">'
                . htmlspecialchars(Text::_('COM_MINIMARKET_LOCAL_VARIANTS_LEGACY'), ENT_QUOTES, 'UTF-8')
                . '</p>';
        }
        $esc=static fn(string $v):string=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $value=$this->value;
        if (is_array($value)) $value=json_encode($value,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
        if (!is_string($value) || $value==='') {
            $value=json_encode(ProductLocalDimensions::load($db,$productId),
                JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
        }
        return '<div data-mm-local-builder data-mm-max="200">'
            . '<input type="hidden" name="'.$esc($this->name).'" id="'.$esc($this->id).'"'
            . ' value="'.$esc($value).'">'
            . '<p class="text-muted">'.$esc(Text::_('COM_MINIMARKET_LOCAL_VARIANTS_HELP')).'</p>'
            . '<div data-mm-axes></div>'
            . '<div class="d-flex gap-2 flex-wrap mb-2">'
            . '<button type="button" class="btn btn-outline-primary" data-mm-add-axis>'
            . $esc(Text::_('COM_MINIMARKET_LOCAL_ADD_AXIS')).'</button>'
            . '<button type="button" class="btn btn-primary" data-mm-generate>'
            . $esc(Text::_('COM_MINIMARKET_LOCAL_GENERATE')).'</button></div>'
            . '<div data-mm-matrix-preview class="mb-2" aria-live="polite"></div>'
            . '<p data-mm-local-feedback role="status" class="text-muted small"></p></div>';
    }
}
