<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
$labels=[
 'payment'=>'COM_MINIMARKET_PAYMENTS_TITLE',
 'delivery'=>'COM_MINIMARKET_DASHBOARD_DELIVERIES',
 'ticket'=>'COM_MINIMARKET_TICKETS_TITLE',
 'returncase'=>'COM_MINIMARKET_RETURNS_TITLE'];
$views=['payment'=>'payment','delivery'=>'delivery','ticket'=>'ticket','returncase'=>'returncase'];
?>
<div id="j-main-container" class="j-main-container">
 <p class="form-text"><?php echo Text::_($this->onlyUnread
    ?'COM_MINIMARKET_NOTIFICATIONS_HELP':'COM_MINIMARKET_WORKQUEUE_HELP'); ?></p>
 <?php foreach($this->groups as $kind=>$group): ?>
  <section class="card mb-3 mm-admin-panel">
   <div class="card-header">
    <h2 class="h5 mb-0"><?php echo Text::_($labels[$kind]); ?> — <?php echo (int)$group['total']; ?></h2>
   </div>
   <div class="card-body">
    <?php if(!$group['rows']): ?>
     <p class="form-text"><?php echo Text::_('COM_MINIMARKET_WORKQUEUE_EMPTY'); ?></p>
    <?php else: ?>
     <div class="table-responsive"><table class="table table-striped">
      <thead><tr><th>ID</th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
       <th><?php echo Text::_('JDATE'); ?></th>
       <th><?php echo Text::_('COM_MINIMARKET_WORKQUEUE_READ_STATE'); ?></th>
       <th><?php echo Text::_('JGLOBAL_ACTIONS'); ?></th></tr></thead>
      <tbody><?php foreach($group['rows'] as $entry): ?>
       <tr>
        <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view='.$views[$kind].'&id='.(int)$entry->id,false)); ?>">#<?php echo (int)$entry->id; ?></a></td>
        <td><?php echo $this->escape($entry->description); ?>
         <?php if(($entry->priority??'')==='high'): ?><span class="small text-body-secondary"> — <?php echo Text::_('COM_MINIMARKET_TICKET_PRIORITY_HIGH'); ?></span><?php endif; ?></td>
        <td><?php echo $this->escape($entry->date_value); ?></td>
        <td><?php echo Text::_($entry->wasRead?'COM_MINIMARKET_WORKQUEUE_READ':'COM_MINIMARKET_WORKQUEUE_UNREAD'); ?></td>
        <td>
         <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=workqueue.setRead',false)); ?>">
          <input type="hidden" name="kind" value="<?php echo $this->escape($kind); ?>">
          <input type="hidden" name="return_view" value="<?php echo $this->onlyUnread?'notifications':'workqueue'; ?>">
          <input type="hidden" name="id" value="<?php echo (int)$entry->id; ?>">
          <input type="hidden" name="signature" value="<?php echo $this->escape($entry->signature); ?>">
          <input type="hidden" name="read" value="<?php echo $entry->wasRead?'0':'1'; ?>">
          <?php echo HTMLHelper::_('form.token'); ?>
          <button type="submit" class="btn btn-outline-secondary btn-sm"><?php echo Text::_($entry->wasRead?'COM_MINIMARKET_WORKQUEUE_MARK_UNREAD':'COM_MINIMARKET_WORKQUEUE_MARK_READ'); ?></button>
         </form>
        </td>
       </tr>
      <?php endforeach; ?></tbody>
     </table></div>
     <?php if(count($group['rows'])<$group['total'] || $this->onlyUnread): ?>
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_WORKQUEUE_LIMIT'); ?></p>
     <?php endif; ?>
    <?php endif; ?>
   </div>
  </section>
 <?php endforeach; ?>
</div>
