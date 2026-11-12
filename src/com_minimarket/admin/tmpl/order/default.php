<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$o=$this->order;
$oid=(int)$o->id;
$active=empty($o->deleted_at)&&(string)$o->status!=='cancelled';
$canUpdate=$this->canUpdate&&$active;
$e=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$to=static fn(string $view,int $id):string=>Route::_('index.php?option=com_minimarket&view='.$view.'&id='.$id,false);
$money=static fn($cents):string=>number_format((int)$cents/100,2,'.',' ').' ₽';
?>
<div class="btn-toolbar d-flex align-items-center flex-wrap gap-2 mb-3" role="toolbar">
 <a class="btn btn-outline-secondary" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=orders',false)); ?>"><span class="icon-arrow-left" aria-hidden="true"></span> <?php echo Text::_('JTOOLBAR_BACK'); ?></a>
 <?php if($this->canCreateDelivery&&$active&&$o->delivery_method==='courier'): ?>
  <a class="btn btn-success" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=delivery&layout=create&order_id='.$oid,false)); ?>"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_DELIVERY_NEW'); ?></a>
 <?php endif; ?>
 <?php if($this->canCreateReturn&&empty($o->deleted_at)): ?>
  <a class="btn btn-success" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=returncase&layout=create&order_id='.$oid,false)); ?>"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_RETURN_NEW'); ?></a>
 <?php endif; ?>
</div>
<div id="j-main-container" class="j-main-container">
 <div class="row g-3 align-items-start">
  <div class="col-12 col-lg-7">
   <section class="card card-body mm-admin-panel mb-3">
    <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_BLOCK'); ?></h2>
    <p class="mb-3"><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper((string)$o->status)); ?></p>
    <?php if($this->canUpdate&&empty($o->deleted_at)): ?>
    <form method="post" action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=order.status',false)); ?>">
     <input type="hidden" name="id" value="<?php echo $oid; ?>">
     <div class="d-flex flex-wrap gap-2 align-items-end">
      <div class="flex-grow-1">
       <label for="mm-order-status" class="form-label"><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></label>
       <select name="status" id="mm-order-status" class="form-select">
        <?php foreach(['new','confirmed','completed','cancelled'] as $status): ?>
         <option value="<?php echo $status; ?>"<?php echo $o->status===$status?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper($status)); ?></option>
        <?php endforeach; ?>
       </select>
      </div>
      <?php echo HTMLHelper::_('form.token'); ?>
      <button class="btn btn-success" type="submit"><?php echo Text::_('JSAVE'); ?></button>
     </div>
    </form>
    <?php endif; ?>
    <div class="mt-3">
     <span class="text-muted"><?php echo Text::_('COM_MINIMARKET_FULFILLMENT_STATUS'); ?>:</span>
     <strong><?php echo Text::_('COM_MINIMARKET_FULFILLMENT_STATUS_'.strtoupper((string)($o->fulfillment_status??'pending'))); ?></strong>
    </div>
    <?php if($canUpdate && $o->delivery_method!=='digital'): ?>
    <form method="post" action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=order.fulfillment',false)); ?>" class="d-flex flex-wrap gap-2 align-items-end mt-2">
     <input type="hidden" name="id" value="<?php echo $oid; ?>">
     <div class="flex-grow-1">
      <label for="mm-fulfillment-status" class="form-label"><?php echo Text::_('COM_MINIMARKET_FULFILLMENT_STATUS'); ?></label>
      <select name="fulfillment_status" id="mm-fulfillment-status" class="form-select">
       <?php foreach(['pending','shipped','delivered'] as $status): ?>
        <option value="<?php echo $status; ?>"<?php echo ($o->fulfillment_status??'pending')===$status?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_FULFILLMENT_STATUS_'.strtoupper($status)); ?></option>
       <?php endforeach; ?>
      </select>
     </div>
     <?php echo HTMLHelper::_('form.token'); ?>
     <button type="submit" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
    </form>
    <?php endif; ?>
    <?php if($this->canDelete && ((string)$o->status==='cancelled'||!empty($o->deleted_at))): ?>
    <form method="post" action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=order.'.(!empty($o->deleted_at)?'restore':'trash'),false)); ?>" class="mt-3"
      <?php if(empty($o->deleted_at)): ?>data-confirm="<?php echo $e(Text::_('COM_MINIMARKET_ORDER_TRASH_CONFIRM')); ?>" onsubmit="return confirm(this.dataset.confirm)"<?php endif; ?>>
     <input type="hidden" name="id" value="<?php echo $oid; ?>">
     <?php echo HTMLHelper::_('form.token'); ?>
     <button class="btn btn-outline-<?php echo empty($o->deleted_at)?'danger':'secondary'; ?>" type="submit"><?php echo Text::_(empty($o->deleted_at)?'COM_MINIMARKET_ORDER_TRASH':'COM_MINIMARKET_ORDER_RESTORE'); ?></button>
    </form>
    <?php endif; ?>
   </section>
   <section class="card card-body mm-admin-panel mb-3">
    <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_BLOCK'); ?></h2>
    <dl class="row mb-3">
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_METHOD'); ?></dt>
     <dd class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_'.strtoupper((string)$o->payment_method)); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS'); ?></dt>
     <dd class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS_'.strtoupper((string)$o->payment_status)); ?></dd>
     <?php if(!empty($o->payment_reference)): ?>
      <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_PAYMENT_REFERENCE'); ?></dt>
      <dd class="col-sm-7"><?php echo $e($o->payment_reference); ?></dd>
     <?php endif; ?>
     <?php if(!empty($o->payment_url)&&str_starts_with((string)$o->payment_url,'https://')): ?>
      <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_PAYMENT_LINK'); ?></dt>
      <dd class="col-sm-7"><a href="<?php echo $e($o->payment_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_MINIMARKET_PAYMENT_LINK_OPEN'); ?></a></dd>
     <?php endif; ?>
    </dl>
    <?php if($canUpdate): ?>
    <form class="row g-2 mb-3" method="post" action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=order.paymentReference',false)); ?>">
     <input type="hidden" name="id" value="<?php echo $oid; ?>">
     <div class="col-12 col-md-5">
      <label class="form-label" for="mm-payment-reference"><?php echo Text::_('COM_MINIMARKET_PAYMENT_REFERENCE'); ?></label>
      <input class="form-control" id="mm-payment-reference" name="payment_reference" maxlength="128" value="<?php echo $e($o->payment_reference??''); ?>">
     </div>
     <div class="col-12 col-md-7">
      <label class="form-label" for="mm-payment-url"><?php echo Text::_('COM_MINIMARKET_PAYMENT_LINK'); ?></label>
      <input class="form-control" type="url" id="mm-payment-url" name="payment_url" maxlength="2048" placeholder="https://" value="<?php echo $e($o->payment_url??''); ?>">
     </div>
     <div class="col-12">
      <?php echo HTMLHelper::_('form.token'); ?>
      <button class="btn btn-success" type="submit"><?php echo Text::_('JSAVE'); ?></button>
     </div>
    </form>
    <?php if(in_array((string)$o->payment_status,['unpaid','paid'],true)): ?>
    <form method="post" action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=order.payment',false)); ?>">
     <input type="hidden" name="id" value="<?php echo $oid; ?>">
     <div class="d-flex flex-wrap align-items-end gap-2">
      <div class="flex-grow-1">
       <label class="form-label" for="mm-payment-status"><?php echo Text::_('COM_MINIMARKET_PAYMENT_ACTION'); ?></label>
       <select id="mm-payment-status" name="payment_status" class="form-select">
        <?php if($o->payment_status==='unpaid'): ?>
        <option value="paid"><?php echo Text::_('COM_MINIMARKET_PAYMENT_MARK_PAID'); ?></option>
        <?php else: ?>
        <?php if($this->hasDigitalItems): ?><option value="paid"><?php echo Text::_('COM_MINIMARKET_DIGITAL_RESEND'); ?></option><?php endif; ?>
        <option value="refunded"><?php echo Text::_('COM_MINIMARKET_PAYMENT_MARK_REFUNDED'); ?></option>
        <?php endif; ?>
       </select>
      </div>
      <?php echo HTMLHelper::_('form.token'); ?>
      <button type="submit" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
     </div>
     <p class="form-text"><?php echo Text::_('COM_MINIMARKET_PAYMENT_MANUAL_HELP'); ?></p>
    </form>
    <?php endif; ?>
    <?php endif; ?>
   </section>
   <section class="card card-body mm-admin-panel mb-3">
    <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_BLOCK'); ?></h2>
    <dl class="row mb-3">
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'); ?></dt>
     <dd class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_'.strtoupper((string)$o->delivery_method)); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?></dt>
     <dd class="col-sm-7"><?php echo $e($o->delivery_city); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_ADDRESS'); ?></dt>
     <dd class="col-sm-7"><?php echo $e($o->delivery_address); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_FEE'); ?></dt>
     <dd class="col-sm-7"><?php echo $money($o->delivery_fee_cents??0); ?></dd>
    </dl>
    <?php if($o->delivery_method==='courier'): ?>
     <div class="table-responsive mm-editor-table-viewport">
      <table class="table table-striped mm-admin-list">
       <thead><tr>
        <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?></th>
        <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON'); ?></th>
        <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></th>
        <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?></th>
        <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?></th>
       </tr></thead><tbody>
       <?php foreach($this->shipments as $ship): ?>
        <tr>
         <td><a href="<?php echo $e($to('delivery',(int)$ship->id)); ?>">#<?php echo (int)$ship->id; ?></a></td>
         <td><?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON_'.strtoupper((string)$ship->reason)); ?></td>
         <td><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER_'.strtoupper((string)$ship->carrier)); ?></td>
         <td><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS_'.strtoupper((string)$ship->status)); ?></td>
         <td><?php echo $e($ship->tracking_number); ?></td>
        </tr>
       <?php endforeach; ?>
       </tbody>
      </table>
     </div>
     <?php if(!$this->shipments): ?><p class="form-text"><?php echo Text::_('COM_MINIMARKET_DELIVERIES_NONE'); ?></p><?php endif; ?>
     <?php if($this->canCreateDelivery&&$active): ?>
      <a class="btn btn-outline-primary btn-sm" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=delivery&layout=create&order_id='.$oid,false)); ?>"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_DELIVERY_NEW'); ?></a>
     <?php endif; ?>
    <?php endif; ?>
   </section>
  </div>
  <div class="col-12 col-lg-5">
   <section class="card card-body mm-admin-panel">
    <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_INFO_BLOCK'); ?> #<?php echo $oid; ?></h2>
    <dl class="row mb-0">
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></dt>
     <dd class="col-sm-7"><?php echo $e($o->created_at); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE'); ?></dt>
     <dd class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE_'.strtoupper((string)($o->order_source??'site'))); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></dt>
     <dd class="col-sm-7"><?php echo $e($o->customer_name); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_PHONE'); ?></dt>
     <dd class="col-sm-7"><?php echo $e($o->phone); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_EMAIL'); ?></dt>
     <dd class="col-sm-7"><?php echo $e($o->email); ?></dd>
     <?php foreach($this->customFields as $field): ?>
      <dt class="col-sm-5"><?php echo $e($field->field_title); ?></dt>
      <dd class="col-sm-7"><?php echo nl2br($e($field->field_value)); ?></dd>
     <?php endforeach; ?>
     <dt class="col-12"><?php echo Text::_('COM_MINIMARKET_ORDER_COMMENT'); ?></dt>
     <dd class="col-12"><?php echo nl2br($e($o->customer_comment)); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_ITEMS_TOTAL'); ?></dt>
     <dd class="col-sm-7"><?php echo $money($o->items_total_cents??$o->total_cents); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_FEE'); ?></dt>
     <dd class="col-sm-7"><?php echo $money($o->delivery_fee_cents??0); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></dt>
     <dd class="col-sm-7"><strong><?php echo $money($o->total_cents); ?></strong></dd>
    </dl>
   </section>
  </div>
 </div>
 <section class="card card-body mm-admin-panel mt-3">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_PRODUCTS_BLOCK'); ?></h2>
  <div class="table-responsive mm-editor-table-viewport">
   <table class="table table-striped mm-admin-list">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th>
     <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_CART_UNIT_PRICE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_CART_TOTAL'); ?></th>
    </tr></thead><tbody>
    <?php foreach($this->items as $line): ?>
     <tr>
      <td><?php echo $e($line->sku); ?></td>
      <td><?php if((int)$line->product_id>0): ?><a href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=product&layout=edit&id='.(int)$line->product_id,false)); ?>"><?php echo $e($line->title); ?></a><?php else: ?><?php echo $e($line->title); ?><?php endif; ?></td>
      <td><?php echo $money($line->unit_cents); ?></td>
      <td><?php echo (int)$line->quantity; ?></td>
      <td><?php echo $money($line->line_cents); ?></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
 </section>
 <?php if($this->hasDigitalItems): ?>
 <section class="card card-body mm-admin-panel mt-3">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_DIGITAL_FULFILLMENT'); ?></h2>
  <?php if(trim((string)($o->digital_recipient_email?:$o->email))===''): ?>
    <div class="alert alert-warning"><?php echo Text::_('COM_MINIMARKET_DIGITAL_NO_RECIPIENT'); ?></div>
  <?php endif; ?>
  <?php foreach($this->items as $line):
   if(($line->delivery_type??'physical')!=='digital')continue;
   $mode=(string)($line->digital_delivery_mode??'file');
   $needsManual=in_array($mode,['manual','cloud'],true);
  ?>
   <div class="border rounded p-3 my-2">
    <strong><?php echo $e($line->title); ?></strong>
    <span class="badge bg-secondary ms-2"><?php echo Text::_('COM_MINIMARKET_DIGITAL_MODE_'.strtoupper($mode)); ?></span>
    <?php if($needsManual): ?>
     <?php if($line->digital_fulfilled_at): ?>
      <p class="mt-2"><?php echo Text::_('COM_MINIMARKET_DIGITAL_MANUAL_SENT'); ?></p>
      <p class="form-text"><?php echo nl2br($e($line->digital_manual_message)); ?></p>
     <?php elseif($canUpdate && $o->payment_status==='paid'): ?>
      <form method="post" action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=order.digitalIssue',false)); ?>">
       <input type="hidden" name="id" value="<?php echo $oid; ?>">
       <input type="hidden" name="item_id" value="<?php echo (int)$line->id; ?>">
       <label class="form-label" for="mm-digital-issue-<?php echo (int)$line->id; ?>"><?php echo Text::_('COM_MINIMARKET_DIGITAL_MANUAL_MESSAGE'); ?></label>
       <textarea class="form-control" id="mm-digital-issue-<?php echo (int)$line->id; ?>" name="digital_message" rows="3" maxlength="2000" required></textarea>
       <?php echo HTMLHelper::_('form.token'); ?>
       <button class="btn btn-success mt-2" type="submit"><?php echo Text::_('COM_MINIMARKET_DIGITAL_MANUAL_SEND'); ?></button>
      </form>
     <?php else: ?><p class="form-text"><?php echo Text::_('COM_MINIMARKET_DIGITAL_MANUAL_PENDING'); ?></p><?php endif; ?>
    <?php endif; ?>
   </div>
  <?php endforeach; ?>
 </section>
 <?php endif; ?>
 <section class="card card-body mm-admin-panel mt-3">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_RETURNS_TITLE'); ?></h2>
  <div class="table-responsive mm-editor-table-viewport">
   <table class="table table-striped mm-admin-list">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_RETURN_NUMBER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_RETURN_REASON'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?></th>
    </tr></thead><tbody>
    <?php foreach($this->returns as $r): ?>
     <tr>
      <td><a href="<?php echo $e($to('returncase',(int)$r->id)); ?>">#<?php echo (int)$r->id; ?></a></td>
      <td><?php echo Text::_('COM_MINIMARKET_RETURN_REASON_'.strtoupper((string)$r->reason)); ?></td>
      <td><?php echo Text::_('COM_MINIMARKET_RETURN_STATUS_'.strtoupper((string)$r->status)); ?></td>
      <td><?php if($r->shipment_id): ?><a href="<?php echo $e($to('delivery',(int)$r->shipment_id)); ?>">#<?php echo (int)$r->shipment_id; ?></a><?php else: ?>—<?php endif; ?></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php if(!$this->returns): ?><p class="form-text"><?php echo Text::_('COM_MINIMARKET_RETURNS_EMPTY'); ?></p><?php endif; ?>
 </section>
 <section class="card card-body mm-admin-panel mt-3">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_ORDER_HISTORY_BLOCK'); ?></h2>
  <div class="table-responsive mm-editor-table-viewport">
   <table class="table table-striped mm-admin-list">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_HISTORY_ACTION'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_HISTORY_CHANGE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_HISTORY_ACTOR'); ?></th>
    </tr></thead><tbody>
    <?php foreach($this->events as $event): ?>
     <tr>
      <td><?php echo $e($event->created_at); ?></td>
      <td><?php echo Text::_('COM_MINIMARKET_ORDER_EVENT_'.strtoupper((string)$event->event_type)); ?></td>
      <td><?php echo $e((string)$event->old_value); ?> <?php if((string)$event->new_value!==''): ?>→ <?php echo $e((string)$event->new_value); ?><?php endif; ?></td>
      <td><?php echo (int)$event->actor_user_id>0?'#'.(int)$event->actor_user_id:'—'; ?></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php if(!$this->events): ?><p class="form-text"><?php echo Text::_('COM_MINIMARKET_ORDER_HISTORY_EMPTY'); ?></p><?php endif; ?>
 </section>
</div>
