<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$t=$this->ticket;
?>
<div class="btn-toolbar mb-3">
 <a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=tickets',false)); ?>" class="btn btn-outline-secondary"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<section class="card card-body mm-admin-panel">
 <h2 class="h4">#<?php echo (int)$t->id; ?> — <?php echo $this->escape($t->subject); ?></h2>
 <div class="d-flex flex-wrap gap-3 mb-3">
  <?php if($t->order_id!==null): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$t->order_id,false)); ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?> #<?php echo (int)$t->order_id; ?></a><?php endif; ?>
  <?php if((int)$t->user_id>0): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=customer&id='.(int)$t->user_id,false)); ?>"><?php echo Text::_('COM_MINIMARKET_CUSTOMER_TITLE'); ?> #<?php echo (int)$t->user_id; ?></a><?php endif; ?>
 </div>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_TICKET_INTERNAL_HELP'); ?></p>
 <?php foreach($this->messages as $msg): ?>
  <div class="border-top py-3">
   <p><?php echo nl2br($this->escape($msg->body)); ?></p>
   <span class="form-text"><?php echo $this->escape($msg->created_at); ?> — #<?php echo (int)$msg->author_user_id; ?></span>
  </div>
 <?php endforeach; ?>
 <?php if($this->canEdit): ?>
  <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=ticket.update',false)); ?>" class="row g-3">
   <input name="id" type="hidden" value="<?php echo (int)$t->id; ?>">
   <div class="col-md-6">
    <label for="mm-ticket-status" class="form-label"><?php echo Text::_('JSTATUS'); ?></label>
    <select name="status" id="mm-ticket-status" class="form-select" <?php echo $this->canChangeStatus?'':'disabled'; ?>>
     <?php foreach(['new','in_progress','closed'] as $state): ?>
      <option value="<?php echo $state; ?>"<?php echo $t->status===$state?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_TICKET_'.strtoupper($state)); ?></option>
     <?php endforeach; ?>
    </select>
    <?php if(!$this->canChangeStatus): ?><input type="hidden" name="status" value="<?php echo $this->escape($t->status); ?>"><?php endif; ?>
   </div>
   <div class="col-12">
    <label for="mm-ticket-reply" class="form-label"><?php echo Text::_('COM_MINIMARKET_TICKET_MESSAGE'); ?></label>
    <textarea name="message" id="mm-ticket-reply" rows="4" maxlength="4000" class="form-control"></textarea>
   </div>
   <div class="col-12"><?php echo HTMLHelper::_('form.token'); ?>
    <button type="submit" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button></div>
  </form>
 <?php endif; ?>
</section>