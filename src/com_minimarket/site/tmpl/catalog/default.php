<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Helper\BadgeHelper;
?>
<section class="com-minimarket-catalog">
    <h1><?php echo $this->escape($this->catalogTitle); ?></h1>
    <p class="mm-cart__nav"><a href="<?php echo $this->escape(RouteHelper::cart()); ?>"><?php echo Text::_('COM_MINIMARKET_CART_TITLE'); ?></a>
     <?php if((int)\Joomla\CMS\Factory::getApplication()->getIdentity()->id>0): ?>
      <span class="mx-2">·</span>
      <a href="<?php echo $this->escape(\Joomla\CMS\Router\Route::_('index.php?option=com_minimarket&view=notificationpreferences')); ?>"><?php echo Text::_('COM_MINIMARKET_NOTIFY_PREFERENCES_LINK'); ?></a>
     <?php endif; ?>
    </p>

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
        <section class="com-minimarket-catalog__products" data-mm-root-content>
            <h2><?php echo Text::_('COM_MINIMARKET_PRODUCTS_ALL'); ?></h2>
            <form method="get" data-mm-root-sort-form class="mm-sort-bar d-flex align-items-center gap-2 flex-wrap my-3" action="<?php echo $this->escape(RouteHelper::catalogPage(1,$this->shopItemId)); ?>">
                <label for="mm-root-sort"><?php echo Text::_('COM_MINIMARKET_SORT_LABEL'); ?></label>
                <select class="form-select form-select-sm" id="mm-root-sort" name="mm_order" data-mm-root-sort>
                    <?php foreach ([
                        'newest'=>'COM_MINIMARKET_SORT_NEWEST',
                        'name'=>'COM_MINIMARKET_SORT_NAME',
                        'price_asc'=>'COM_MINIMARKET_SORT_PRICE_ASC',
                        'price_desc'=>'COM_MINIMARKET_SORT_PRICE_DESC'
                    ] as $key=>$label): ?>
                        <option value="<?php echo $this->escape($key); ?>"<?php echo $this->sortOrder===$key?' selected':''; ?>><?php echo Text::_($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-secondary btn-sm" type="submit"><?php echo Text::_('COM_MINIMARKET_FILTER_APPLY'); ?></button>
            </form>
            <?php if ($this->products) : ?>
                <ul class="list-unstyled" data-mm-root-list>
                    <?php foreach ($this->products as $product) : ?>
                        <li>
                            <?php
                            $photo = explode('#', (string) ($product->image ?? ''), 2)[0];
                            if (str_starts_with($photo, 'images/') && !str_contains($photo, '..')
                                && !preg_match('/[\x00-\x1f<>]/', $photo)) :
                            ?>
                                <img src="<?php echo $this->escape(Uri::root() . $photo); ?>"
                                     alt="<?php echo $this->escape((string) ($product->image_alt ?: $product->title)); ?>"
                                     width="96" height="96" loading="lazy" style="object-fit:contain">
                            <?php endif; ?>
                            <a href="<?php echo $this->escape(RouteHelper::product((int) $product->id)); ?>">
                                <?php echo $this->escape($product->title); ?>
                            </a>
                            <?php echo BadgeHelper::render($product); ?>
                <span><?php echo $this->escape((string) $product->price); ?></span>
                <?php echo BadgeHelper::oldPrice($product); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php echo Text::_('COM_MINIMARKET_PRODUCTS_EMPTY'); ?></p>
            <?php endif; ?>

            <?php if ($this->productPageCount > 1) : ?>
                <nav data-mm-root-pages aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_PAGINATION_LABEL')); ?>">
                    <span>
                        <?php echo $this->escape(Text::sprintf(
                            'COM_MINIMARKET_PAGINATION_STATUS',
                            $this->productPage,
                            $this->productPageCount
                        )); ?>
                    </span>
                    <?php if ($this->productPage > 1) : ?>
                        <a data-mm-root-page rel="prev" href="<?php echo $this->escape(RouteHelper::catalogPage(
                            $this->productPage - 1,
                            $this->shopItemId,
                            $this->sortOrder
                        )); ?>"><?php echo Text::_('JPREV'); ?></a>
                    <?php endif; ?>
                    <?php if ($this->productPage < $this->productPageCount) : ?>
                        <a data-mm-root-page data-mm-root-next rel="next" href="<?php echo $this->escape(RouteHelper::catalogPage(
                            $this->productPage + 1,
                            $this->shopItemId,
                            $this->sortOrder
                        )); ?>"><?php echo Text::_('JNEXT'); ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
