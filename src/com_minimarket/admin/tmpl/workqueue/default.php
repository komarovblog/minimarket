<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$labels=[
 'payment'=>'COM_MINIMARKET_PAYMENTS_TITLE',
 'delivery'=>'COM_MINIMARKET_DASHBOARD_DELIVERIES',
 'ticket'=>'COM_MINIMARKET_TICKETS_TITLE',
 'returncase'=>'COM_MINIMARKET_RETURNS_TITLE'];
$views=['payment'=>'payment','delivery'=>'delivery','ticket'=>'ticket','returncase'=>'returncase'];
?>
<div id="j-main-container" class="j-main-container">
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_WORKQUEUE_HELP'); ?></p>
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
      <thead><tr><th>ID</th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('JDATE'); ?></th></tr></thead>
      <tbody><?php foreach($group['rows'] as $entry): ?>
       <tr>
        <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view='.$views[$kind].'&id='.(int)$entry->id,false)); ?>">#<?php echo (int)$entry->id; ?></a></td>
        <td><?php echo $this->escape($entry->description); ?></td>
        <td><?php echo $this->escape($entry->date_value); ?></td>
       </tr>
      <?php endforeach; ?></tbody>
     </table></div>
     <?php if(count($group['rows'])<$group['total']): ?>
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_WORKQUEUE_LIMIT'); ?></p>
     <?php endif; ?>
    <?php endif; ?>
   </div>
  </section>
 <?php endforeach; ?>
</div>