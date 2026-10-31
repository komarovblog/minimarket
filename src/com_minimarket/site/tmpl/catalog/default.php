<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
?>
<section class="com-minimarket-catalog">
    <h1><?php echo $this->escape($this->catalogTitle); ?></h1>
    <?php if ($this->categories) : ?>
        <ul class="list-unstyled">
        <?php foreach ($this->categories as $category) : ?>
            <li><a href="<?php echo $this->escape(RouteHelper::category((int) $category->id)); ?>">
                <?php echo $this->escape($category->title); ?>
            </a></li>
        <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?php echo Text::_('COM_MINIMARKET_CATALOG_PLACEHOLDER'); ?></p>
    <?php endif; ?>
</section>
