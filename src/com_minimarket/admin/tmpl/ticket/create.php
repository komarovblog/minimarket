<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="btn-toolbar gap-2 d-flex flex-wrap mb-3">
 <button type="submit" form="mm-ticket-create" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
 <a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=tickets',false)); ?>" class="btn btn-outline-secondary"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<section class="card card-body mm-admin-panel">
 <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_TICKET_NEW'); ?></h2>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_TICKET_INTERNAL_HELP'); ?></p>
 <form id="mm-ticket-create" method="post" class="row g-3"
  action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=ticket.create',false)); ?>">
  <div class="col-12"><label class="form-label" for="mm-ticket-subject"><?php echo Text::_('COM_MINIMARKET_TICKET_SUBJECT'); ?></label>
    <input class="form-control" id="mm-ticket-subject" name="subject" required maxlength="180"></div>
  <div class="col-md-6"><label class="form-label" for="mm-ticket-user"><?php echo Text::_('COM_MINIMARKET_CUSTOMER_TITLE'); ?> (Joomla ID)</label>
    <input class="form-control" id="mm-ticket-user" type="number" min="0" name="user_id" value="<?php echo max(0,(int)\Joomla\CMS\Factory::getApplication()->input->getInt('user_id',0)); ?>">
    <div class="form-text"><?php echo Text::_('COM_MINIMARKET_CRM_LINKED_ID_HELP'); ?></div></div>
  <div class="col-md-6"><label class="form-label" for="mm-ticket-order"><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></label>
    <input class="form-control" id="mm-ticket-order" type="number" min="0" name="order_id" value="<?php echo max(0,(int)\Joomla\CMS\Factory::getApplication()->input->getInt('order_id',0)); ?>"></div>
  <div class="col-12"><label class="form-label" for="mm-ticket-message"><?php echo Text::_('COM_MINIMARKET_TICKET_MESSAGE'); ?></label>
    <textarea class="form-control" id="mm-ticket-message" name="message" rows="5" required maxlength="4000"></textarea></div>
  <div class="col-12"><?php echo HTMLHelper::_('form.token'); ?></div>
 </form>
</section>