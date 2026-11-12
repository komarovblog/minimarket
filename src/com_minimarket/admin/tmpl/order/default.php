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
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h2><?php echo Text::sprintf('COM_MINIMARKET_ORDER_HEADING',(int)$order->id); ?></h2>
      <?php if ($this->shipmentId!==null): ?>
       <a class="btn btn-outline-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=delivery&id='.$this->shipmentId,false)); ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_OPEN'); ?></a>
      <?php elseif($this->canCreateDelivery && ($order->delivery_method??'')==='courier' && ($order->status??'')!=='cancelled' && empty($order->deleted_at)): ?>
       <a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=delivery&layout=create&order_id='.(int)$order->id,false)); ?>"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_DELIVERY_NEW'); ?></a>
      <?php endif; ?>
    </div>
    <dl class="row">
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE'); ?></dt>
        <dd class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE_'.strtoupper((string)($order->order_source??'site'))); ?></dd>
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
        <?php foreach($this->customFields as $field): ?>
            <dt class="col-sm-3"><?php echo $this->escape((string)$field->field_title); ?></dt>
            <dd class="col-sm-9"><?php echo nl2br($this->escape((string)$field->field_value)); ?></dd>
        <?php endforeach; ?>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'); ?></dt>
        <dd class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_'.strtoupper((string)($order->delivery_method??'pickup'))); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape((string)($order->delivery_city??'')); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_ADDRESS'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape((string)($order->delivery_address??'')); ?></dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_METHOD'); ?></dt>
        <dd class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_'.strtoupper((string)($order->payment_method??'on_receipt'))); ?></dd>
    </dl>
    <div class="table-responsive mm-editor-table-viewport">
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
    </div>
    <dl class="row">
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_ITEMS_TOTAL'); ?></dt>
        <dd class="col-sm-9"><?php echo number_format((int)($order->items_total_cents??$order->total_cents)/100,2,'.',' '); ?> ₽</dd>
        <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_FEE'); ?></dt>
        <dd class="col-sm-9"><?php echo number_format((int)($order->delivery_fee_cents??0)/100,2,'.',' '); ?> ₽</dd>
    </dl>
    <p><strong><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?>:
        <?php echo number_format((int)$order->total_cents/100,2,'.',' '); ?> ₽</strong></p>
    <p class="text-muted"><?php echo Text::_('COM_MINIMARKET_ORDER_NO_PAYMENT'); ?></p>
    <?php $statusColors=['new'=>'info','confirmed'=>'primary','completed'=>'success','cancelled'=>'secondary']; ?>
    <p><span class="badge bg-<?php echo $statusColors[(string)$order->status]??'secondary'; ?>">
        <?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper((string)$order->status)); ?></span></p>
    <?php if ($this->canDelete && ((string)$order->status==='cancelled' || !empty($order->deleted_at))) : ?>
        <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=order.'.(!empty($order->deleted_at)?'restore':'trash'),false)); ?>" class="mb-3"
              <?php if(empty($order->deleted_at)) : ?>data-confirm="<?php echo $this->escape(Text::_('COM_MINIMARKET_ORDER_TRASH_CONFIRM')); ?>" onsubmit="return confirm(this.dataset.confirm)"<?php endif; ?>>
            <input type="hidden" name="id" value="<?php echo (int)$order->id; ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn btn-outline-<?php echo empty($order->deleted_at)?'danger':'secondary'; ?>">
                <?php echo Text::_(empty($order->deleted_at)?'COM_MINIMARKET_ORDER_TRASH':'COM_MINIMARKET_ORDER_RESTORE'); ?>
            </button>
        </form>
    <?php endif; ?>
    <?php if ($this->canUpdate && empty($order->deleted_at)) : ?>
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
