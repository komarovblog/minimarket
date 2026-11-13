<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$o=$this->order;
$id=(int)$o->id;
$esc=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$fmt=static fn($v)=>number_format((int)$v/100,2,','," ").' '.(string)$o->currency;
?>
<div class="d-flex flex-wrap gap-2 mb-3">
 <a class="btn btn-outline-secondary" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=payments',false)); ?>"><?php echo Text::_('JTOOLBAR_BACK'); ?></a>
 <a class="btn btn-outline-primary" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=order&id='.$id,false)); ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?> #<?php echo $id; ?></a>
</div>
<div id="j-main-container" class="j-main-container mm-order-page">
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-payment-'.$id,['active'=>'general']); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-payment-'.$id,'general',Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
 <section class="card card-body mm-admin-panel mm-order-card">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_PAYMENTS_TITLE'); ?> #<?php echo $id; ?></h2>
  <dl class="row mb-2">
   <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></dt><dd class="col-sm-9"><?php echo $esc($o->customer_name); ?></dd>
   <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS'); ?></dt><dd class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS_'.strtoupper((string)$o->payment_status)); ?></dd>
   <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_METHOD'); ?></dt><dd class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_'.strtoupper((string)$o->payment_method)); ?></dd>
   <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></dt><dd class="col-sm-9"><?php echo $esc($fmt($o->total_cents)); ?></dd>
   <dt class="col-sm-3"><?php echo Text::_('COM_MINIMARKET_ORDER_PAID_AT'); ?></dt><dd class="col-sm-9"><?php echo $o->paid_at?HTMLHelper::_('date',$o->paid_at,'d.m.Y H:i'):'—'; ?></dd>
  </dl>
  <?php if($this->canConfirm&&$o->payment_status==='unpaid'): ?>
   <form method="post" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=order.payment',false)); ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="payment_status" value="paid">
    <div class="mb-2">
     <label for="mm-payment-proof" class="form-label"><?php echo Text::_('COM_MINIMARKET_PAYMENT_PROOF'); ?></label>
     <input id="mm-payment-proof" name="payment_confirmation" class="form-control" maxlength="128" minlength="3" required>
     <p class="form-text"><?php echo Text::_('COM_MINIMARKET_PAYMENT_MANUAL_HELP'); ?></p>
    </div>
    <?php echo HTMLHelper::_('form.token'); ?>
    <button type="submit" class="btn btn-success"><?php echo Text::_('COM_MINIMARKET_PAYMENT_MARK_PAID'); ?></button>
   </form>
  <?php endif; ?>
 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-payment-'.$id,'records',Text::_('COM_MINIMARKET_PAYMENT_OPERATIONS')); ?>
 <section class="card card-body mm-admin-panel mm-order-card">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_PAYMENT_OPERATIONS'); ?></h2>
  <?php if($this->records): ?>
  <div class="table-responsive">
   <table class="table table-striped mm-admin-list">
    <thead><tr><th>ID</th><th><?php echo Text::_('COM_MINIMARKET_ORDER_PAID_AT'); ?></th><th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th><th><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_METHOD'); ?></th><th><?php echo Text::_('COM_MINIMARKET_PAYMENT_REFERENCE'); ?></th></tr></thead>
    <tbody><?php foreach($this->records as $rec): ?><tr>
     <td>#<?php echo (int)$rec->id; ?></td>
     <td><?php echo HTMLHelper::_('date',$rec->confirmed_at,'d.m.Y H:i'); ?></td>
     <td class="text-nowrap"><?php echo $esc($fmt($rec->amount_cents)); ?></td>
     <td><?php echo $esc((string)$rec->method); ?></td>
     <td><?php echo $esc($rec->external_reference); ?></td>
    </tr><?php endforeach; ?></tbody>
   </table>
  </div>
  <?php else: ?>
   <p class="form-text"><?php echo Text::_('COM_MINIMARKET_PAYMENT_NO_RECORDS'); ?></p>
  <?php endif; ?>
 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</div>
