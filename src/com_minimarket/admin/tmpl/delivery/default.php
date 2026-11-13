<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$s=$this->shipment;
?>
<div class="btn-toolbar d-flex gap-2 flex-wrap mb-3" role="toolbar">
 <button type="submit" form="mm-delivery-update-form" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
 <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<section class="mm-admin-panel mm-admin-list-panel">
 <h2 class="h4">#<?php echo (int)$s->id; ?> — <?php echo Text::_('COM_MINIMARKET_DASHBOARD_DELIVERIES'); ?></h2>
 <p><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$s->order_id,false)); ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?> #<?php echo (int)$s->order_id; ?></a> — <?php echo $this->escape((string)$s->customer_name); ?></p>
 <p><?php echo $this->escape($s->delivery_city.' / '.$s->delivery_address); ?></p>
 <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=delivery.update',false)); ?>" id="mm-delivery-update-form" class="row g-3">
  <input type="hidden" name="id" value="<?php echo (int)$s->id; ?>">
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-carrier"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></label>
    <select class="form-select" id="mm-carrier" name="carrier">
      <?php foreach(['own','cdek','other'] as $carrier): ?><option value="<?php echo $carrier; ?>" <?php echo $s->carrier===$carrier?'selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER_'.strtoupper($carrier)); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-reason"><?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON'); ?></label>
    <select id="mm-reason" name="reason" class="form-select">
     <?php foreach(['initial','redelivery','replacement','other'] as $reason): ?>
      <option value="<?php echo $reason; ?>"<?php echo ($s->reason??'initial')===$reason?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON_'.strtoupper($reason)); ?></option>
     <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-track"><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?></label>
    <input class="form-control" id="mm-track" name="tracking_number" maxlength="120" value="<?php echo $this->escape((string)$s->tracking_number); ?>">
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-state"><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?></label>
    <select class="form-select" id="mm-state" name="status">
      <?php
       $allowedStates=match((string)$s->status){
         'new','awaiting_assembly'=>['awaiting_assembly','assembling','ready','in_transit','delivered','cancelled'],
         'assembling'=>['assembling','ready','in_transit','delivered','cancelled'],
         'ready'=>['ready','in_transit','awaiting_pickup','delivered','cancelled'],
         'in_transit'=>['in_transit','awaiting_pickup','delivered','failed'],
         'awaiting_pickup'=>['awaiting_pickup','delivered','failed'],
         'failed'=>['failed','ready','in_transit','awaiting_pickup','delivered'],
         default=>[(string)$s->status]
       };
       foreach($allowedStates as $state): ?><option value="<?php echo $state; ?>" <?php echo $s->status===$state?'selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS_'.strtoupper($state)); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12">
   <label class="form-label" for="mm-note"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NOTE'); ?></label>
   <textarea class="form-control" id="mm-note" name="note" maxlength="2000" rows="4"><?php echo $this->escape((string)$s->note); ?></textarea>
  </div>
  <div class="col-12"><?php echo HTMLHelper::_('form.token'); ?></div>
 </form>
  <?php if($this->linkedReturns): ?>
  <div class="mt-3">
   <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_RETURNS_TITLE'); ?></h3>
   <?php foreach($this->linkedReturns as $r): ?>
    <a class="d-block" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=returncase&id='.(int)$r->id,false)); ?>">#<?php echo (int)$r->id; ?> — <?php echo Text::_('COM_MINIMARKET_RETURN_STATUS_'.strtoupper((string)$r->status)); ?></a>
   <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
