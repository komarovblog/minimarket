<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<div id="j-main-container" class="j-main-container">
 <div class="alert alert-warning"><?php echo Text::_('COM_MINIMARKET_FISCAL_DISABLED'); ?></div>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_FISCAL_HELP'); ?></p>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_FISCAL_INTEGRATION_NOTE'); ?></p>
 <div class="table-responsive mm-native-list-viewport">
  <table class="table table-striped mm-admin-list">
   <thead><tr><th>ID</th><th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_FISCAL_OPERATION'); ?></th>
    <th><?php echo Text::_('JSTATUS'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_FISCAL_PROVIDER'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_FISCAL_REFERENCE'); ?></th>
    <th><?php echo Text::_('JDATE'); ?></th></tr></thead>
   <tbody><?php foreach($this->operations as $op): ?><tr>
    <td>#<?php echo (int)$op->id; ?></td>
    <td><a href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$op->order_id,false)); ?>">#<?php echo (int)$op->order_id; ?></a></td>
    <td><?php echo $e($op->operation_type); ?></td>
    <td><?php echo $e($op->status); ?></td>
    <td><?php echo $e($op->provider_code); ?></td>
    <td><?php echo $e($op->fiscal_reference??''); ?></td>
    <td><?php echo $e($op->created_at); ?></td>
   </tr><?php endforeach; ?></tbody>
  </table>
 </div>
 <?php if(!$this->operations): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_FISCAL_EMPTY'); ?></p><?php endif; ?>
</div>
