<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section class="mm-admin-panel card card-body">
    <h2><?php echo Text::_('COM_MINIMARKET_ORDERS_TITLE'); ?></h2>
    <table class="table table-striped mm-admin-list">
        <thead><tr>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_PHONE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($this->orders as $order) : ?>
        <tr>
            <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$order->id,false)); ?>">
                #<?php echo (int)$order->id; ?></a></td>
            <td><?php echo $this->escape($order->created_at); ?></td>
            <td><?php echo $this->escape($order->customer_name); ?></td>
            <td><?php echo $this->escape($order->phone); ?></td>
            <td><?php echo number_format(((int)$order->total_cents)/100,2,'.',' '); ?> ₽</td>
            <td><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper((string)$order->status)); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$this->orders) : ?>
            <tr><td colspan="6"><?php echo Text::_('COM_MINIMARKET_ORDERS_EMPTY'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <nav class="d-flex gap-3">
        <?php if ($this->offset > 0) : ?>
            <a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=orders&limitstart='.max(0,$this->offset-$this->pageSize),false)); ?>">
                <?php echo Text::_('JPREV'); ?></a>
        <?php endif; ?>
        <?php if ($this->offset+$this->pageSize < $this->total) : ?>
            <a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=orders&limitstart='.($this->offset+$this->pageSize),false)); ?>">
                <?php echo Text::_('JNEXT'); ?></a>
        <?php endif; ?>
    </nav>
</section>
