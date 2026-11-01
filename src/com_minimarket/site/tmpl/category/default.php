<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
?>
<nav aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BREADCRUMB')); ?>">
    <a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CATALOG_MENU_TITLE'); ?></a>
</nav>
<section class="com-minimarket-category">
    <h1><?php echo $this->escape($this->category->title); ?></h1>
    <?php if ((string) $this->category->description !== '') : ?>
        <p><?php echo $this->escape(strip_tags((string) $this->category->description)); ?></p>
    <?php endif; ?>

    <?php if ($this->children) : ?>
        <section class="com-minimarket-category__children">
            <h2><?php echo Text::_('COM_MINIMARKET_CHILD_CATEGORIES'); ?></h2>
            <ul class="list-unstyled">
                <?php foreach ($this->children as $child) : ?>
                    <li><a href="<?php echo $this->escape(RouteHelper::category((int) $child->id)); ?>">
                        <?php echo $this->escape($child->title); ?>
                    </a></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($this->products) : ?>
        <ul class="list-unstyled">
        <?php foreach ($this->products as $product) : ?>
            <li>
                <a href="<?php echo $this->escape(RouteHelper::product((int) $product->id)); ?>">
                    <?php echo $this->escape($product->title); ?>
                </a>
                <span><?php echo $this->escape((string) $product->price); ?></span>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?php echo Text::_('COM_MINIMARKET_CATEGORY_EMPTY'); ?></p>
    <?php endif; ?>
</section>
