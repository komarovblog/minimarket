<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
?>
<div class="com-minimarket-dashboard">
    <p><a class="btn btn-primary" href="index.php?option=com_minimarket&amp;view=categories"><?php echo Text::_('COM_MINIMARKET_CATEGORIES'); ?></a>
    <a class="btn btn-outline-secondary" href="index.php?option=com_minimarket&amp;view=products"><?php echo Text::_('COM_MINIMARKET_PRODUCTS'); ?></a></p>
    <h2><?php echo Text::_('COM_MINIMARKET_DASHBOARD_HEADING'); ?></h2>
    <p><?php echo Text::_('COM_MINIMARKET_DASHBOARD_DESCRIPTION'); ?></p>
    <p>
        <a class="btn btn-outline-primary"
           href="<?php echo htmlspecialchars(Uri::root() . 'index.php?option=com_minimarket', ENT_QUOTES, 'UTF-8'); ?>"
           target="_blank" rel="noopener noreferrer">
            <?php echo Text::_('COM_MINIMARKET_OPEN_SITE'); ?>
        </a>
    </p>
</div>
