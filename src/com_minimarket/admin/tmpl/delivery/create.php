<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section class="mm-admin-panel mm-admin-list-panel">
 <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NEW'); ?></h2>
 <div class="alert alert-info" role="note"><span class="icon-info-circle" aria-hidden="true"></span>
 <?php echo Text::_('COM_MINIMARKET_DELIVERY_CREATE_HINT'); ?></div>
 <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=delivery.create',false)); ?>" class="row g-3">
  <div class="col-12">
    <label class="form-label" for="mm-shipment-order"><?php echo Text::_('COM_MINIMARKET_DELIVERY_ORDER'); ?> *</label>
    <select class="form-select" id="mm-shipment-order" name="order_id" required>
     <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
     <?php foreach($this->orders as $o): ?><option value="<?php echo (int)$o->id; ?>" <?php echo (int)$o->id===$this->preselectedOrder?'selected':''; ?>>#<?php echo (int)$o->id; ?> — <?php echo $this->escape($o->customer_name.' / '.$o->delivery_city); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-shipment-carrier"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></label>
    <select class="form-select" id="mm-shipment-carrier" name="carrier">
      <?php foreach(['own','cdek','other'] as $carrier): ?><option value="<?php echo $carrier; ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER_'.strtoupper($carrier)); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-shipment-track"><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?></label>
    <input class="form-control" id="mm-shipment-track" name="tracking_number" maxlength="120">
    <div class="form-text"><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK_HINT'); ?></div>
  </div>
  <div class="col-12">
    <label class="form-label" for="mm-shipment-note"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NOTE'); ?></label>
    <textarea class="form-control" id="mm-shipment-note" name="note" maxlength="2000" rows="3"></textarea>
  </div>
  <div class="col-12">
   <?php echo HTMLHelper::_('form.token'); ?>
   <button type="submit" class="btn btn-primary"><?php echo Text::_('JSAVE'); ?></button>
   <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
  </div>
 </form>
</section>
