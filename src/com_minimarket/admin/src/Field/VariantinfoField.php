<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductAttributes;
use MiniMarket\Component\Minimarket\Administrator\Service\ProductVariants;
use MiniMarket\Component\Minimarket\Administrator\Service\CategoryAttributes;

final class VariantinfoField extends FormField
{
    protected $type = 'Variantinfo';

    protected function getInput(): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $definitions = ProductAttributes::definitions($db);
        $hierarchy = CategoryAttributes::loadHierarchy($db);
        $map = ProductAttributes::categoryMap($hierarchy, $definitions);
        $config = [];
        foreach ($map as $categoryId => $allowedIds) {
            $config[(string) $categoryId] = ProductVariants::axes((int) $categoryId, $definitions, $map);
        }
        $escape = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<div data-mm-variant-config="' . $escape(json_encode($config,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR)) . '">'
            . '<p class="text-muted">' . $escape(Text::_('COM_MINIMARKET_VARIANTS_HELP')) . '</p>'
            . '<p class="small text-warning" data-mm-variant-config-warning></p></div>';
    }
}
