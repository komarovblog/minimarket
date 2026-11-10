<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$colors=['new'=>'info','in_transit'=>'primary','delivered'=>'success','cancelled'=>'secondary'];
?>
<section class="mm-admin-panel mm-admin-list-panel">
 <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
  <h2 class="h4 mb-0"><?php echo Text::_('COM_MINIMARKET_DASHBOARD_DELIVERIES'); ?></h2>
  <?php if($this->canCreate): ?><a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=delivery&layout=create',false)); ?>"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_DELIVERY_NEW'); ?></a><?php endif; ?>
 </div>
 <form method="get" action="index.php" class="d-flex flex-wrap align-items-end gap-2 mb-3">
  <input type="hidden" name="option" value="com_minimarket"><input type="hidden" name="view" value="deliveries">
  <div><label class="form-label" for="mm-delivery-status"><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?></label>
   <select class="form-select" name="filter_status" id="mm-delivery-status">
    <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
    <?php foreach ($colors as $status=>$color): ?>
     <option value="<?php echo $status; ?>"<?php echo $this->status===$status?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS_'.strtoupper($status)); ?></option>
    <?php endforeach; ?>
   </select></div>
  <button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
  <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries',false)); ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
 </form>
 <div class="table-responsive">
  <table class="table table-striped mm-admin-list">
   <thead><tr>
    <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?></th>
   </tr></thead><tbody>
   <?php foreach($this->deliveries as $delivery): ?>
    <tr>
     <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=delivery&id='.(int)$delivery->id,false)); ?>">#<?php echo (int)$delivery->id; ?></a></td>
     <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$delivery->order_id,false)); ?>">#<?php echo (int)$delivery->order_id; ?></a></td>
     <td><?php echo $this->escape($delivery->created_at); ?></td>
     <td><?php echo $this->escape($delivery->customer_name); ?></td>
     <td><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER_'.strtoupper((string)$delivery->carrier)); ?></td>
     <td><?php echo $this->escape((string)$delivery->tracking_number); ?></td>
     <td><?php echo $this->escape((string)$delivery->delivery_city); ?></td>
     <td><span class="badge bg-<?php echo $colors[(string)$delivery->status]??'secondary'; ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS_'.strtoupper((string)$delivery->status)); ?></span></td>
    </tr>
   <?php endforeach; ?>
   <?php if(!$this->deliveries): ?><tr><td colspan="8"><?php echo Text::_('COM_MINIMARKET_DELIVERIES_NONE'); ?></td></tr><?php endif; ?>
   </tbody>
  </table>
 </div>
 <nav class="d-flex gap-3">
  <?php if($this->offset>0): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries&filter_status='.rawurlencode($this->status).'&limitstart='.max(0,$this->offset-$this->pageSize),false)); ?>"><?php echo Text::_('JPREV'); ?></a><?php endif; ?>
  <?php if($this->offset+$this->pageSize<$this->total): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries&filter_status='.rawurlencode($this->status).'&limitstart='.($this->offset+$this->pageSize),false)); ?>"><?php echo Text::_('JNEXT'); ?></a><?php endif; ?>
 </nav>
</section>
