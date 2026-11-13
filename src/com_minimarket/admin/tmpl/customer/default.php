<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$c=$this->customer;
?>
<div class="btn-toolbar mb-3">
 <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=customers',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<div id="j-main-container" class="j-main-container">
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-customer-'.(int)$c->id,['active'=>'orders']); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-customer-'.(int)$c->id,'orders',Text::_('COM_MINIMARKET_CRM_ORDERS')); ?>
 <section class="card card-body mm-admin-panel">
  <h2 class="h5"><?php echo $this->escape($c->name); ?> (#<?php echo (int)$c->id; ?>)</h2>
  <p class="form-text"><?php echo $this->escape($c->email); ?></p>
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_CRM_LINKED_ID_HELP'); ?></p>
  <div class="table-responsive"><table class="table table-striped">
   <thead><tr><th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
   <th><?php echo Text::_('JDATE'); ?></th>
   <th><?php echo Text::_('JSTATUS'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th></tr></thead>
   <tbody><?php foreach($this->orders as $order): ?>
    <tr><td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$order->id,false)); ?>">#<?php echo (int)$order->id; ?></a></td>
    <td><?php echo $this->escape($order->created_at); ?></td><td><?php echo $this->escape($order->status); ?></td>
    <td><?php echo number_format((int)$order->total_cents/100,2,',',' '); ?> ₽</td></tr>
   <?php endforeach; ?></tbody>
  </table></div>
 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-customer-'.(int)$c->id,'tickets',Text::_('COM_MINIMARKET_TICKETS_TITLE')); ?>
 <section class="card card-body mm-admin-panel">
  <?php foreach($this->tickets as $ticket): ?>
   <p><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=ticket&id='.(int)$ticket->id,false)); ?>">#<?php echo (int)$ticket->id; ?> — <?php echo $this->escape($ticket->subject); ?></a></p>
  <?php endforeach; ?>
 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-customer-'.(int)$c->id,'notes',Text::_('COM_MINIMARKET_CRM_NOTES')); ?>
 <section class="card card-body mm-admin-panel">
  <?php if($this->canNote): ?>
   <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=customer.note',false)); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$c->id; ?>">
    <label class="form-label" for="mm-customer-note"><?php echo Text::_('COM_MINIMARKET_CRM_ADD_NOTE'); ?></label>
    <textarea id="mm-customer-note" class="form-control" name="body" maxlength="4000" required rows="3"></textarea>
    <?php echo HTMLHelper::_('form.token'); ?>
    <button class="btn btn-success mt-2" type="submit"><?php echo Text::_('JSAVE'); ?></button>
   </form>
  <?php endif; ?>
  <?php foreach($this->notes as $note): ?>
   <div class="border-top mt-3 pt-3">
    <p><?php echo nl2br($this->escape($note->body)); ?></p>
    <p class="form-text"><?php echo $this->escape($note->created_at); ?> — #<?php echo (int)$note->author_user_id; ?></p>
   </div>
  <?php endforeach; ?>
 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</div>