<?php
namespace MiniMarket\Component\Minimarket\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

/**
 * Standard Joomla list control; one shared JS asset manages dependencies.
 * Used by both Joomla component Options and the category editor.
 */
final class GridcardsField extends ListField
{
    protected $type='Gridcards';

    protected function getInput()
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseScript(
                'com_minimarket.category-grid-settings',
                'media/com_minimarket/js/category-grid-settings.js',
                [],
                ['defer'=>true]
            );
        return parent::getInput();
    }
}
