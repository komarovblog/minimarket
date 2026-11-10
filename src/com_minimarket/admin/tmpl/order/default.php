<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$order=$this->order;
?>
<section class="mm-admin-panel card card-body">
    <p><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=orders',false)); ?>">
        <?php echo Text::_('COM_MINIMARKET_ORDERS_TITLE'); ?></a></p>
    <h2><?php echo Text::sprintf('COM_MINIMARKET_ORDER_HEADING',(int)$order->id); ?></h2>
    <dl class="row">
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape($order->created_at); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape($order->customer_name); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_PHONE'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape($order->phone); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_EMAIL'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape($order->email); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_COMMENT'); ?></dt>
        <dd class="col-sm-9"><?php echo nl2br($this->escape($order->customer_comment)); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'); ?></dt>
        <dd class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_'.strtoupper((string)($order->delivery_method??'pickup'))); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape((string)($order->delivery_city??'')); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_ADDRESS'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape((string)($order->delivery_address??'')); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_METHOD'); ?></dt>
        <dd class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_'.strtoupper((string)($order->payment_method??'on_receipt'))); ?></dd>
    </dl>
    <table class="table table-striped mm-admin-list">
        <thead><tr>
            <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th>
            <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CART_UNIT_PRICE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_CART_TOTAL'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($this->items as $line) : ?>
            <tr>
                <td><?php echo $this->escape($line->sku); ?></td>
                <td><?php echo $this->escape($line->title); ?></td>
                <td><?php echo number_format((int)$line->unit_cents/100,2,'.',' '); ?> ₽</td>
                <td><?php echo (int)$line->quantity; ?></td>
                <td><?php echo number_format((int)$line->line_cents/100,2,'.',' '); ?> ₽</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <dl class="row">
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_ITEMS_TOTAL'); ?></dt>
        <dd class="col-sm-9"><?php echo number_format((int)($order->items_total_cents??$order->total_cents)/100,2,'.',' '); ?> ₽</dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_FEE'); ?></dt>
        <dd class="col-sm-9"><?php echo number_format((int)($order->delivery_fee_cents??0)/100,2,'.',' '); ?> ₽</dd>
    </dl>
    <p><strong><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?>:
        <?php echo number_format((int)$order->total_cents/100,2,'.',' '); ?> ₽</strong></p>
    <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_ORDER_NO_PAYMENT'); ?></p>
    <?php if ($this->canUpdate) : ?>
        <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=order.status',false)); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$order->id; ?>">
            <label for="mm-order-status" class="form-label"><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></label>
            <select name="status" id="mm-order-status" class="form-select">
                <?php foreach (['new','confirmed','completed','cancelled'] as $status) : ?>
                    <option value="<?php echo $status; ?>"<?php echo $order->status===$status?' selected':''; ?>>
                        <?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper($status)); ?></option>
                <?php endforeach; ?>
            </select>
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn btn-primary mt-2"><?php echo Text::_('JSAVE'); ?></button>
        </form>
    <?php else : ?>
        <p><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper((string)$order->status)); ?></p>
    <?php endif; ?>
</section>
