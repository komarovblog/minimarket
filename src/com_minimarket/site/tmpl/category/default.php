<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filter\InputFilter;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
use MiniMarket\Component\Minimarket\Site\Helper\BadgeHelper;
?>
<nav aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BREADCRUMB')); ?>">
    <a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CATALOG_MENU_TITLE'); ?></a>
</nav>
<section class="com-minimarket-category">
    <h1><?php echo $this->escape($this->category->title); ?></h1>
    <?php
    $image = explode('#', (string) ($this->category->image ?? ''), 2)[0];
    $safeImage = str_starts_with($image, 'images/') && !str_contains($image, '..')
        && !preg_match('/[\x00-\x1f<>]/', $image);
    ?>
    <?php if ($safeImage) : ?>
        <img src="<?php echo $this->escape(Uri::root() . $image); ?>"
             alt="<?php echo $this->escape((string) ($this->category->image_alt ?: $this->category->title)); ?>"
             loading="lazy" class="com-minimarket-category__image">
    <?php endif; ?>
    <?php if (trim((string) ($this->category->introtext ?? '')) !== '') : ?>
        <div class="com-minimarket-category__intro"><?php echo InputFilter::getInstance()->clean((string) $this->category->introtext, 'HTML'); ?></div>
    <?php endif; ?>
    <?php if ((string) $this->category->description !== '') : ?>
        <div class="com-minimarket-category__description"><?php echo InputFilter::getInstance()->clean((string) $this->category->description, 'HTML'); ?></div>
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
        <?php if ($this->facetData['facets']) : ?>
            <div class="mm-facet-layout" data-mm-facet-layout>
                <aside class="mm-facet-sidebar">
                    <?php include __DIR__ . '/filters.php'; ?>
                </aside>
                <div class="mm-facet-results">
        <?php endif; ?>
        <?php
        $grid = ($this->productListOptions['layout'] ?? 'list') === 'grid';
        $gridClasses = $grid
            ? \MiniMarket\Component\Minimarket\Site\Service\CategoryDisplayOptions::bootstrapGridClasses($this->productListOptions)
            : 'list-unstyled mm-product-list';
        ?>
        <ul class="<?php echo $this->escape($gridClasses); ?> list-unstyled mm-category-products" data-mm-category-layout="<?php echo $grid ? 'grid' : 'list'; ?>">
        <?php foreach ($this->products as $product) : ?>
            <li class="<?php echo $grid ? 'col' : 'mm-product-list__item'; ?>"
                data-mm-facet-product="<?php echo (int)$product->id; ?>"<?php echo ($this->facetMatches[(int)$product->id]??true) ? '' : ' hidden'; ?>>
                <div class="<?php echo $grid ? 'mm-product-card' : 'mm-product-list__inner'; ?>">
                    <?php
                    $photo = explode('#', (string) ($product->image ?? ''), 2)[0];
                    $hasPhoto = str_starts_with($photo, 'images/') && !str_contains($photo, '..')
                        && !preg_match('/[\x00-\x1f<>]/', $photo);
                    ?>
                    <?php if ($hasPhoto) : ?>
                        <a class="mm-product-card__media" href="<?php echo $this->escape(RouteHelper::product((int) $product->id)); ?>">
                            <img src="<?php echo $this->escape(Uri::root() . $photo); ?>"
                                alt="<?php echo $this->escape((string) ($product->image_alt ?: $product->title)); ?>"
                                loading="lazy" class="mm-product-card__image">
                        </a>
                    <?php endif; ?>
                    <div class="mm-product-card__info">
                        <a href="<?php echo $this->escape(RouteHelper::product((int) $product->id)); ?>">
                            <?php echo $this->escape($product->title); ?>
                        </a>
                        <?php echo BadgeHelper::render($product); ?>
                        <div class="mm-product-card__price">
                            <span><?php echo $this->escape((string) $product->price); ?></span>
                            <?php echo BadgeHelper::oldPrice($product); ?>
                        </div>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php if ($this->facetData['facets']) : ?>
                <p class="mm-facet-empty" data-mm-facet-empty<?php echo in_array(true,$this->facetMatches,true) ? ' hidden' : ''; ?>>
                    <?php echo Text::_('COM_MINIMARKET_FILTER_NO_MATCHES'); ?>
                </p>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif ($this->displayProducts && !$this->children) : ?>
        <p><?php echo Text::_('COM_MINIMARKET_CATEGORY_EMPTY'); ?></p>
    <?php endif; ?>
</section>
