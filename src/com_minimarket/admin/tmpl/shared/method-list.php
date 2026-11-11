<?php
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$esc = static fn($v): string => htmlspecialchars(
    (string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
);

$viewName = (string) $mmSearch['view'];
?>
<form id="adminForm" name="adminForm" class="mm-list-form" method="post"
      action="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=' . $viewName, false)); ?>">
 <div id="j-main-container" class="j-main-container">
 <?php require __DIR__ . '/searchtools.php'; ?>
 <div class="table-responsive mm-native-list-viewport">
  <table id="mm-<?php echo $esc($viewName); ?>-list"
         class="table table-striped mm-admin-list">
   <thead>
    <tr>
     <th><?php echo Text::_('COM_MINIMARKET_METHOD_NAME'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_METHOD_TYPE'); ?></th>
     <th><?php echo Text::_('JSTATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?></th>
    </tr>
   </thead>
   <tbody>
    <?php foreach ($this->methodRows as $row): ?>
    <tr>
     <td data-label="<?php echo $esc(Text::_('COM_MINIMARKET_METHOD_NAME')); ?>">
      <?php if ($row['url'] !== ''): ?>
       <a href="<?php echo $esc($row['url']); ?>"><?php echo $esc($row['name']); ?></a>
      <?php else: ?>
       <?php echo $esc($row['name']); ?>
      <?php endif; ?>
      <small class="d-block text-muted"><?php echo $esc($row['code']); ?></small>
     </td>
     <td data-label="<?php echo $esc(Text::_('COM_MINIMARKET_METHOD_TYPE')); ?>">
      <?php echo Text::_($row['type'] === 'builtin'
          ? 'COM_MINIMARKET_METHOD_BUILTIN' : 'COM_MINIMARKET_METHOD_PLUGIN'); ?>
     </td>
     <td data-label="<?php echo $esc(Text::_('JSTATUS')); ?>">
      <span class="badge bg-<?php echo $row['enabled'] ? 'success' : 'secondary'; ?>">
       <?php echo Text::_($row['enabled']
           ? 'COM_MINIMARKET_METHOD_ACTIVE' : 'COM_MINIMARKET_METHOD_INACTIVE'); ?>
      </span>
     </td>
     <td data-label="<?php echo $esc(Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS')); ?>">
      <?php if ($row['url'] !== ''): ?>
       <a href="<?php echo $esc($row['url']); ?>"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?></a>
      <?php else: ?>—<?php endif; ?>
     </td>
    </tr>
    <?php endforeach; ?>

   </tbody>
  </table>
<?php if (!$this->methodRows): ?>
 <div class="alert alert-info mt-2" role="status"><?php echo Text::_('COM_MINIMARKET_REF_EMPTY'); ?></div>
<?php endif; ?>
 </div>
 <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination(
     $this->total, $this->offset, $this->pageSize
 ); ?>
 <input type="hidden" name="limitstart" value="<?php echo (int) $this->offset; ?>">
 <input type="hidden" name="boxchecked" value="0">
 <input type="hidden" name="task" value="">
 <?php echo HTMLHelper::_('form.token'); ?>
 </div>
</form>
<p class="form-text"><?php echo Text::_('COM_MINIMARKET_METHOD_PLUGIN_NOTE'); ?></p>
