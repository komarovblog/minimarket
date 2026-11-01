<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
?>
<section class="com-minimarket-catalog">
    <h1><?php echo $this->escape($this->catalogTitle); ?></h1>

    <?php if ($this->showCategories) : ?>
        <section class="com-minimarket-catalog__categories">
            <h2><?php echo Text::_('COM_MINIMARKET_CATEGORIES_ALL'); ?></h2>
            <?php if ($this->categories) : ?>
                <ul class="list-unstyled">
                    <?php foreach ($this->categories as $category) : ?>
                        <li>
                            <a href="<?php echo $this->escape(RouteHelper::category((int) $category->id)); ?>">
                                <?php echo $this->escape($category->title); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php echo Text::_('COM_MINIMARKET_CATEGORIES_EMPTY'); ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($this->showProducts) : ?>
        <section class="com-minimarket-catalog__products">
            <h2><?php echo Text::_('COM_MINIMARKET_PRODUCTS_ALL'); ?></h2>
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
                <p><?php echo Text::_('COM_MINIMARKET_PRODUCTS_EMPTY'); ?></p>
            <?php endif; ?>

            <?php if ($this->productPageCount > 1) : ?>
                <nav aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PAGINATION_LABEL')); ?>">
                    <span>
                        <?php echo $this->escape(Text::sprintf(
                            'COM_MINIMARKET_PAGINATION_STATUS',
                            $this->productPage,
                            $this->productPageCount
                        )); ?>
                    </span>
                    <?php if ($this->productPage > 1) : ?>
                        <a rel="prev" href="<?php echo $this->escape(RouteHelper::catalogPage(
                            $this->productPage - 1,
                            $this->shopItemId
                        )); ?>"><?php echo Text::_('JPREV'); ?></a>
                    <?php endif; ?>
                    <?php if ($this->productPage < $this->productPageCount) : ?>
                        <a rel="next" href="<?php echo $this->escape(RouteHelper::catalogPage(
                            $this->productPage + 1,
                            $this->shopItemId
                        )); ?>"><?php echo Text::_('JNEXT'); ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
