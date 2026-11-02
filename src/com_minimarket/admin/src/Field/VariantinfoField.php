<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductVariants;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductLocalDimensions;

final class VariantinfoField extends FormField
{
    protected $type = 'Variantinfo';

    protected function getInput(): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $productId=(int)Factory::getApplication()->input->getInt('id',0);
        if (!ProductLocalDimensions::hasLegacyVariants($db,$productId)) {
            return '';
        }
        $definitions = ProductAttributes::definitions($db);
        $hierarchy = CategoryAttributes::loadHierarchy($db);
        $map = ProductAttributes::categoryMap($hierarchy, $definitions);
        $config = [];
        foreach ($map as $categoryId => $allowedIds) {
            $config[(string) $categoryId] = ProductVariants::axes((int) $categoryId, $definitions, $map);
        }
        $escape = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $attributesUrl = Route::_('index.php?option=com_minimarket&view=attributes');
        return '<div data-mm-variant-config="' . $escape(json_encode($config,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR))
            . '" data-mm-no-axes="' . $escape(Text::_('COM_MINIMARKET_VARIANTS_NO_AXES_HELP'))
            . '" data-mm-too-many-axes="' . $escape(Text::_('COM_MINIMARKET_VARIANTS_TOO_MANY_AXES'))
            . '">'
            . '<p class="text-muted">' . $escape(Text::_('COM_MINIMARKET_VARIANTS_HELP')) . '</p>'
            . '<p class="small text-warning" role="status" data-mm-variant-config-warning></p>'
            . '<a data-mm-variant-setup-link href="' . $escape($attributesUrl)
            . '" class="btn btn-outline-secondary btn-sm">'
            . $escape(Text::_('COM_MINIMARKET_VARIANTS_SETUP_ATTRIBUTES')) . '</a></div>';
    }
}
