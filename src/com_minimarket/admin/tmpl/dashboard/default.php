<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks;

$sections = [
    ['categories', 'COM_MINIMARKET_CATEGORIES'],
    ['products', 'COM_MINIMARKET_PRODUCTS'],
    ['groups', 'COM_MINIMARKET_PRODUCT_TAB_GROUPS'],
    ['attributes', 'COM_MINIMARKET_ATTRIBUTES'],
    ['orderoptions', 'COM_MINIMARKET_ORDER_OPTIONS'],
    ['import', 'COM_MINIMARKET_IMPORT_TITLE'],
];
?>
<div class="com-minimarket-dashboard mm-dashboard card card-body mm-admin-panel">
    <h2 class="h4 mb-3"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_HEADING'); ?></h2>
    <p class="text-muted mb-4"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_DESCRIPTION'); ?></p>
    <h3 class="h5 mb-3"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_SECTIONS'); ?></h3>
    <div class="list-group mm-dashboard__sections mb-4">
        <?php foreach ($sections as [$view, $label]) : ?>
            <a class="list-group-item list-group-item-action"
               href="<?php echo AdminEntityLinks::view($view); ?>">
                <?php echo htmlspecialchars(Text::_($label), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <a class="btn btn-primary mm-dashboard__open"
       href="<?php echo htmlspecialchars(Uri::root() . 'index.php?option=com_minimarket&view=catalog', ENT_QUOTES, 'UTF-8'); ?>"
       target="_blank" rel="noopener noreferrer">
        <?php echo Text::_('COM_MINIMARKET_OPEN_SITE'); ?>
    </a>
</div>
