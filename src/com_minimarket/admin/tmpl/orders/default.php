<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$href='index.php?option=com_minimarket&view=orders'
    .'&filter_search='.rawurlencode($this->search)
    .'&filter_product='.rawurlencode($this->productSearch)
    .'&filter_status='.rawurlencode($this->statusFilter)
    .'&filter_delivery='.rawurlencode($this->deliveryFilter);
$statuses=['new'=>'info','confirmed'=>'primary','completed'=>'success','cancelled'=>'secondary'];
?>
<section class="mm-admin-panel card card-body">
    <div class="d-flex justify-content-between gap-2 flex-wrap align-items-center mb-3">
        <h2 class="h4 mb-0"><?php echo Text::_('COM_MINIMARKET_ORDERS_TITLE'); ?></h2>
        <?php if ($this->canCreate) : ?>
            <a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&layout=create',false)); ?>">
                <span class="icon-plus" aria-hidden="true"></span>
                <?php echo Text::_('COM_MINIMARKET_MANUAL_ORDER_TITLE'); ?></a>
        <?php endif; ?>
    </div>
    <form class="row g-2 align-items-end mb-3" method="get" action="index.php" role="search">
        <input type="hidden" name="option" value="com_minimarket">
        <input type="hidden" name="view" value="orders">
        <div class="col-12 col-md-3">
            <label class="form-label" for="mm-order-search"><?php echo Text::_('COM_MINIMARKET_ORDERS_FILTER_CUSTOMER'); ?></label>
            <input id="mm-order-search" class="form-control" type="search" name="filter_search"
                   maxlength="100" value="<?php echo $this->escape($this->search); ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="mm-order-filter-product"><?php echo Text::_('COM_MINIMARKET_ORDERS_FILTER_PRODUCT'); ?></label>
            <input id="mm-order-filter-product" class="form-control" type="search"
                   name="filter_product" maxlength="100" value="<?php echo $this->escape($this->productSearch); ?>">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label" for="mm-order-filter-status"><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></label>
            <select id="mm-order-filter-status" name="filter_status" class="form-select">
                <option value=""><?php echo Text::_('JOPTION_SELECT'); ?></option>
                <?php foreach ($statuses as $status=>$color) : ?>
                    <option value="<?php echo $status; ?>"<?php echo $this->statusFilter===$status?' selected':''; ?>>
                        <?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper($status)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label" for="mm-order-filter-delivery"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'); ?></label>
            <select id="mm-order-filter-delivery" name="filter_delivery" class="form-select">
                <option value=""><?php echo Text::_('JOPTION_SELECT'); ?></option>
                <?php foreach (['pickup','courier'] as $delivery) : ?>
                    <option value="<?php echo $delivery; ?>"<?php echo $this->deliveryFilter===$delivery?' selected':''; ?>>
                        <?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_'.strtoupper($delivery)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
            <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=orders',false)); ?>">
                <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
        </div>
    </form>
    <div class="table-responsive">
    <table class="table table-striped mm-admin-list">
        <thead><tr>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_PHONE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE'); ?></th>
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
            <td><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_'.strtoupper((string)$order->delivery_method)); ?></td>
            <td><span class="badge bg-<?php echo $statuses[(string)$order->status]??'secondary'; ?>">
                <?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper((string)$order->status)); ?></span></td>
            <td><?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE_'.strtoupper((string)$order->order_source)); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$this->orders) : ?>
            <tr><td colspan="8"><?php echo Text::_('COM_MINIMARKET_ORDERS_EMPTY'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    <nav class="d-flex gap-3">
        <?php if ($this->offset > 0) : ?>
            <a href="<?php echo $this->escape(Route::_($href.'&limitstart='.max(0,$this->offset-$this->pageSize),false)); ?>">
                <?php echo Text::_('JPREV'); ?></a>
        <?php endif; ?>
        <?php if ($this->offset+$this->pageSize < $this->total) : ?>
            <a href="<?php echo $this->escape(Route::_($href.'&limitstart='.($this->offset+$this->pageSize),false)); ?>">
                <?php echo Text::_('JNEXT'); ?></a>
        <?php endif; ?>
    </nav>
</section>
