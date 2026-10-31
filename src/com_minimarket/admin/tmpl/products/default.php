<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<p><?php echo Text::_('COM_MINIMARKET_PRODUCTS_READONLY_NOTICE'); ?></p>
<table class="table">
    <thead>
    <tr><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th><th><?php echo Text::_('COM_MINIMARKET_PRODUCT_PRICE'); ?></th><th><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></th><th><?php echo Text::_('COM_MINIMARKET_CATEGORIES'); ?></th></tr>
    </thead>
    <tbody>
    <?php foreach ($this->items as $item) : ?>
        <tr>
            <td><?php echo $this->escape($item->title); ?></td>
            <td><?php echo $this->escape($item->sku); ?></td>
            <td><?php echo $this->escape($item->price); ?></td>
            <td><?php echo (int) $item->stock; ?></td>
            <td><?php echo $this->escape($item->category_title); ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$this->items) : ?>
        <tr><td colspan="5"><?php echo Text::_('COM_MINIMARKET_NO_PRODUCTS'); ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
