<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$selected=[];
foreach($this->orders as $order){
 $selected[(int)$order->id]='#'.(int)$order->id.' — '.$order->customer_name.' · '.$order->delivery_city;
}
$mmReference=[
 'title'=>Text::_('COM_MINIMARKET_DELIVERY_ORDER'),
 'input'=>'order_id','endpoint'=>'orders','multiple'=>false,'selected'=>$selected,
 'placeholder'=>Text::_('COM_MINIMARKET_ORDER_PICKER_SEARCH'),
 'help'=>Text::_('COM_MINIMARKET_DELIVERY_ORDER_PICKER_HELP'),
 'filters'=>[
  ['name'=>'date_from','type'=>'date','label'=>Text::_('COM_MINIMARKET_REF_DATE_FROM')],
  ['name'=>'date_to','type'=>'date','label'=>Text::_('COM_MINIMARKET_REF_DATE_TO')]
 ]
];
?>
<div class="d-flex gap-2 flex-wrap mb-3">
 <button type="submit" form="mm-delivery-form" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
 <a class="btn btn-danger" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<section class="mm-admin-panel">
 <div class="alert alert-info" role="note"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CREATE_HINT'); ?></div>
 <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=delivery.create',false)); ?>" id="mm-delivery-form" class="row g-3">
  <div class="col-12"><?php require dirname(__DIR__).'/shared/reference-picker.php'; ?></div>
  <div class="col-12 col-md-6">
   <label class="form-label" for="mm-shipment-carrier"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></label>
   <select class="form-select" id="mm-shipment-carrier" name="carrier">
    <?php foreach(['own','cdek','other'] as $carrier): ?>
     <option value="<?php echo $carrier; ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER_'.strtoupper($carrier)); ?></option>
    <?php endforeach; ?>
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
  <?php echo HTMLHelper::_('form.token'); ?>
 </form>
</section>