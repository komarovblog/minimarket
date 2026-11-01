<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;

$image = explode('#', (string) ($this->product->image ?? ''), 2)[0];
$safeImage = str_starts_with($image, 'images/') && !str_contains($image, '..')
    && !preg_match('/[\x00-\x1f<>]/', $image);
?>
<nav aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BREADCRUMB')); ?>">
    <a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CATALOG_MENU_TITLE'); ?></a>
    <span> / </span>
    <a href="<?php echo $this->escape(RouteHelper::category((int) $this->product->category_id)); ?>"><?php echo $this->escape($this->product->category_title); ?></a>
</nav>
<article class="com-minimarket-product">
    <h1><?php echo $this->escape($this->product->title); ?></h1>
    <?php if ($safeImage) : ?>
        <img src="<?php echo $this->escape(Uri::root() . $image); ?>"
             alt="<?php echo $this->escape((string) ($this->product->image_alt ?: $this->product->title)); ?>"
             loading="lazy" class="com-minimarket-product__cover">
    <?php endif; ?>
    <?php if (trim((string) ($this->product->introtext ?? '')) !== '') : ?>
        <div class="com-minimarket-product__intro">
            <?php echo InputFilter::getInstance()->clean((string) $this->product->introtext, 'HTML'); ?>
        </div>
    <?php endif; ?>
    <dl>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></dt>
        <dd><?php echo $this->escape($this->product->sku); ?></dd>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_PRICE'); ?></dt>
        <dd><?php echo $this->escape((string) $this->product->price); ?></dd>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></dt>
        <dd><?php echo (int) $this->product->stock; ?></dd>
    </dl>
    <?php if (trim((string) ($this->product->description ?? '')) !== '') : ?>
        <div class="com-minimarket-product__description">
            <?php echo InputFilter::getInstance()->clean((string) $this->product->description, 'HTML'); ?>
        </div>
    <?php endif; ?>
    <p><?php echo Text::_('COM_MINIMARKET_CART_NOT_READY'); ?></p>
</article>
