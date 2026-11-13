<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$labels=[
 'order'=>'COM_MINIMARKET_ORDERS_TITLE',
 'payment'=>'COM_MINIMARKET_PAYMENTS_TITLE',
 'shipment'=>'COM_MINIMARKET_DASHBOARD_DELIVERIES',
 'ticket'=>'COM_MINIMARKET_TICKETS_TITLE',
 'returncase'=>'COM_MINIMARKET_RETURNS_TITLE'
];
$views=['order'=>'order','payment'=>'order','shipment'=>'delivery',
 'ticket'=>'ticket','returncase'=>'returncase'];
?>
<div id="j-main-container" class="j-main-container">
 <section class="card mm-admin-panel mb-3">
  <div class="card-header">
   <h2 class="h5 mb-0"><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_ATTENTION'); ?>
   <?php if($this->attentionTotal>0): ?><span class="badge bg-danger text-white ms-2"><?php echo (int)$this->attentionTotal; ?></span><?php endif; ?></h2>
  </div>
  <div class="card-body">
   <p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_ATTENTION_HELP'); ?></p>
   <?php if(!$this->attentionGroups): ?>
    <p class="text-body-secondary"><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_ATTENTION_EMPTY'); ?></p>
   <?php else: ?>
    <?php foreach($this->attentionGroups as $group): ?>
     <h3 class="h6"><?php echo Text::_($group['label']); ?> — <?php echo (int)$group['count']; ?></h3>
     <div class="d-flex flex-wrap gap-2 mb-3">
      <?php foreach($group['ids'] as $id): ?>
       <a class="btn btn-outline-secondary btn-sm"
          href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view='.$group['view'].($group['view']==='fiscalization'?'':'&id='.$id),false)); ?>">
        <?php echo Text::_($group['label']); ?> #<?php echo (int)$id; ?>
       </a>
      <?php endforeach; ?>
     </div>
     <?php if($group['capped']): ?><p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_LIMIT'); ?></p><?php endif; ?>
    <?php endforeach; ?>
   <?php endif; ?>
  </div>
 </section>
 <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_HISTORY'); ?></h2>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_EVENTS_HELP'); ?></p>
 <div class="table-responsive mm-native-list-viewport">
 <table class="table table-striped mm-admin-list">
  <thead><tr><th><?php echo Text::_('JDATE'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_NOTIFICATION_EVENTS_EVENT'); ?></th>
   <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_WORKQUEUE_READ_STATE'); ?></th>
   <th><?php echo Text::_('JGLOBAL_ACTIONS'); ?></th>
  </tr></thead>
  <tbody>
  <?php foreach($this->events as $item): ?>
   <tr>
    <td><?php echo $this->escape((string)$item['occurred_at']); ?> UTC</td>
    <td><?php echo Text::_($labels[$item['kind']]); ?></td>
    <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view='.$views[$item['kind']]
       .'&id='.(int)$item['target_id'],false)); ?>">
      #<?php echo (int)$item['target_id']; ?>
     </a> — <?php echo $this->escape((string)$item['description']); ?></td>
    <td><?php echo Text::_($item['read']?'COM_MINIMARKET_WORKQUEUE_READ':'COM_MINIMARKET_WORKQUEUE_UNREAD'); ?></td>
    <td>
     <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=notifications.markRead',false)); ?>">
      <input type="hidden" name="kind" value="<?php echo $this->escape((string)$item['kind']); ?>">
      <input type="hidden" name="event_id" value="<?php echo (int)$item['event_id']; ?>">
      <input type="hidden" name="read" value="<?php echo $item['read']?'0':'1'; ?>">
      <?php echo HTMLHelper::_('form.token'); ?>
      <button type="submit" class="btn btn-outline-secondary btn-sm">
       <?php echo Text::_($item['read']?'COM_MINIMARKET_WORKQUEUE_MARK_UNREAD':'COM_MINIMARKET_WORKQUEUE_MARK_READ'); ?>
      </button>
     </form>
    </td>
   </tr>
  <?php endforeach; ?>
  </tbody>
 </table>
 </div>
 <?php if(!$this->events): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_EMPTY'); ?></p><?php endif; ?>
 <?php if($this->capped): ?><p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFICATIONS_LIMIT'); ?></p><?php endif; ?>
</div>
