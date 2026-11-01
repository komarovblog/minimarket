<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
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
    <?php elseif ($this->displayProducts && !$this->children) : ?>
        <p><?php echo Text::_('COM_MINIMARKET_CATEGORY_EMPTY'); ?></p>
    <?php endif; ?>
</section>
