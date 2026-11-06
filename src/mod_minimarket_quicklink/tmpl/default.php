<?php
/**
 * Match the native Joomla Atum header modules (mod_frontend, mod_version).
 * Placement immediately before mod_version is determined by module ordering,
 * not via template overrides, browser JS or CSS layout hacks.
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$label = trim((string) $params->get('label', 'MiniMarket'));
if ($label === '') {
    $label = 'MiniMarket';
}
$encode = static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$href = Route::_('index.php?option=com_minimarket&view=dashboard', false);
?>
<a href="<?php echo $encode($href); ?>"
   class="header-item-content mm-quicklink"
   title="<?php echo $encode(Text::_('MOD_MINIMARKET_QUICKLINK_OPEN')); ?>"
   aria-label="<?php echo $encode(Text::_('MOD_MINIMARKET_QUICKLINK_OPEN')); ?>">
    <?php if ($showIcon) : ?>
        <span class="header-item-icon">
            <span class="icon-cart" aria-hidden="true"></span>
        </span>
    <?php endif; ?>
    <?php if ($showLabel) : ?>
        <span class="header-item-text mm-quicklink-label"><?php echo $encode($label); ?></span>
    <?php endif; ?>
</a>
