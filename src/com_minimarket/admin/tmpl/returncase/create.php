<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$mmReference=[
 'title'=>Text::_('COM_MINIMARKET_ORDER_NUMBER'),
 'input'=>'order_id','endpoint'=>'orders','multiple'=>false,
 'selected'=>$this->selectedOrder,'placeholder'=>Text::_('COM_MINIMARKET_ORDER_PICKER_SEARCH'),
 'help'=>Text::_('COM_MINIMARKET_DELIVERY_ORDER_PICKER_HELP'),
 'filters'=>[
  ['name'=>'date_from','type'=>'date','label'=>Text::_('COM_MINIMARKET_REF_DATE_FROM')],
  ['name'=>'date_to','type'=>'date','label'=>Text::_('COM_MINIMARKET_REF_DATE_TO')]
 ],
];
?>
<div class="btn-toolbar d-flex gap-2 flex-wrap mb-3" role="toolbar">
 <button type="submit" form="mm-return-create-form" class="btn btn-success"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
 <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=returns',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<div id="j-main-container" class="j-main-container">
 <form id="mm-return-create-form" method="post"
  action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=returncase.create',false)); ?>">
  <div class="card card-body mm-admin-panel">
   <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_RETURN_NEW'); ?></h2>
   <div class="mb-3"><?php require dirname(__DIR__).'/shared/reference-picker.php'; ?></div>
   <div class="row g-3">
    <div class="col-12 col-md-6">
     <label for="mm-return-reason" class="form-label"><?php echo Text::_('COM_MINIMARKET_RETURN_REASON'); ?></label>
     <select id="mm-return-reason" name="reason" class="form-select">
      <?php foreach(['defective','damaged','wrong_item','not_fit','other'] as $reason): ?>
       <option value="<?php echo $reason; ?>"><?php echo Text::_('COM_MINIMARKET_RETURN_REASON_'.strtoupper($reason)); ?></option>
      <?php endforeach; ?>
     </select>
    </div>
    <?php if($this->preselectedOrder>0): ?>
    <div class="col-12 col-md-6">
     <label for="mm-return-ship" class="form-label"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?></label>
     <select id="mm-return-ship" name="shipment_id" class="form-select">
      <option value="0"><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
      <?php foreach($this->shipments as $shipment): ?>
       <option value="<?php echo (int)$shipment->id; ?>">#<?php echo (int)$shipment->id; ?> · <?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON_'.strtoupper((string)$shipment->reason)); ?></option>
      <?php endforeach; ?>
     </select>
    </div>
    <div class="col-12 col-md-9">
     <label for="mm-return-line" class="form-label"><?php echo Text::_('COM_MINIMARKET_RETURN_ITEM'); ?></label>
     <select name="order_item_id" id="mm-return-line" class="form-select">
      <option value="0"><?php echo Text::_('COM_MINIMARKET_RETURN_ITEM_ADD_LATER'); ?></option>
      <?php foreach($this->orderItems as $line): ?>
       <option value="<?php echo (int)$line->id; ?>"><?php echo $this->escape((string)$line->title.' · '.(string)$line->sku.' · ×'.(int)$line->quantity); ?></option>
      <?php endforeach; ?>
     </select>
    </div>
    <div class="col-12 col-md-3">
     <label for="mm-return-qty" class="form-label"><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?></label>
     <input id="mm-return-qty" type="number" class="form-control" name="quantity" min="1" max="999" value="1">
    </div>
    <?php endif; ?>
    <div class="col-12">
     <label for="mm-return-note" class="form-label"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NOTE'); ?></label>
     <textarea name="note" id="mm-return-note" class="form-control" rows="3" maxlength="2000"></textarea>
    </div>
   </div>
  </div>
  <?php echo HTMLHelper::_('form.token'); ?>
 </form>
</div>
