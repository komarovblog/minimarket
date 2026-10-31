<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
?>
<nav aria-label="<?php echo $this->escape(Text::_('COM_MINIMARKET_BREADCRUMB')); ?>">
    <a href="<?php echo $this->escape(RouteHelper::catalog()); ?>"><?php echo Text::_('COM_MINIMARKET_CATALOG_MENU_TITLE'); ?></a>
    <span> / </span>
    <a href="<?php echo $this->escape(RouteHelper::category((int) $this->product->category_id)); ?>"><?php echo $this->escape($this->product->category_title); ?></a>
</nav>
<article class="com-minimarket-product">
    <h1><?php echo $this->escape($this->product->title); ?></h1>
    <dl>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></dt>
        <dd><?php echo $this->escape($this->product->sku); ?></dd>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_PRICE'); ?></dt>
        <dd><?php echo $this->escape((string) $this->product->price); ?></dd>
        <dt><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></dt>
        <dd><?php echo (int) $this->product->stock; ?></dd>
    </dl>
    <p><?php echo Text::_('COM_MINIMARKET_CART_NOT_READY'); ?></p>
</article>
