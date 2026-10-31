<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_minimarket&view=products'); ?>" method="post"
      name="adminForm" id="adminForm">
    <table class="table">
        <thead>
        <tr>
            <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_PRICE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_STOCK'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CATEGORIES'); ?></th>
            <th><?php echo Text::_('JSTATUS'); ?></th>
            <th>ID</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->items as $item) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_minimarket&task=product.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->title); ?></a></td>
                <td><?php echo $this->escape($item->sku); ?></td>
                <td><?php echo $this->escape($item->price); ?></td>
                <td><?php echo (int) $item->stock; ?></td>
                <td><?php echo $this->escape($item->category_title); ?></td>
                <td><?php echo (int) $item->state === 1 ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
                <td><?php echo (int) $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
            <tr><td colspan="7"><?php echo Text::_('COM_MINIMARKET_NO_PRODUCTS'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
