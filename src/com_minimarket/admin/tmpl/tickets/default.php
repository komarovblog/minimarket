<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$mmSearch=[
 'view'=>'tickets','query'=>$this->search,'searchLabel'=>Text::_('COM_MINIMARKET_TICKET_SUBJECT'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),
                'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[['name'=>'filter_status','label'=>Text::_('JSTATUS'),
    'value'=>$this->statusFilter,
    'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
      'new'=>Text::_('COM_MINIMARKET_TICKET_NEW_STATUS'),
      'in_progress'=>Text::_('COM_MINIMARKET_TICKET_PROGRESS'),
      'closed'=>Text::_('COM_MINIMARKET_TICKET_CLOSED')]]];
?>
<form method="post" id="adminForm" name="adminForm" class="mm-list-form"
 action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=tickets',false)); ?>">
 <div id="j-main-container" class="j-main-container">
  <?php if($this->canCreate): ?>
   <a class="btn btn-success mb-3" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=ticket&layout=create',false)); ?>"><?php echo Text::_('JTOOLBAR_NEW'); ?></a>
  <?php endif; ?>
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-ticket-list';require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport"><table id="mm-ticket-list" class="table table-striped mm-admin-list">
   <thead><tr><th>ID</th><th><?php echo Text::_('COM_MINIMARKET_TICKET_SUBJECT'); ?></th>
    <th><?php echo Text::_('JSTATUS'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_CUSTOMER_TITLE'); ?></th>
    <th><?php echo Text::_('JDATE'); ?></th></tr></thead>
   <tbody><?php foreach($this->tickets as $ticket): ?><tr>
    <td>#<?php echo (int)$ticket->id; ?></td>
    <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=ticket&id='.(int)$ticket->id,false)); ?>"><?php echo $this->escape($ticket->subject); ?></a></td>
    <td><?php echo Text::_('COM_MINIMARKET_TICKET_'.strtoupper((string)$ticket->status)); ?></td>
    <td><?php if($ticket->order_id!==null): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$ticket->order_id,false)); ?>">#<?php echo (int)$ticket->order_id; ?></a><?php endif; ?></td>
    <td><?php if((int)$ticket->user_id>0): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=customer&id='.(int)$ticket->user_id,false)); ?>">#<?php echo (int)$ticket->user_id; ?></a><?php endif; ?></td>
    <td><?php echo $this->escape($ticket->created_at); ?></td>
   </tr><?php endforeach; ?></tbody>
  </table></div>
  <?php if(!$this->tickets): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_TICKETS_EMPTY'); ?></p><?php endif; ?>
  <input name="task" type="hidden" value=""><input name="limitstart" type="hidden" value="<?php echo (int)$this->offset; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>