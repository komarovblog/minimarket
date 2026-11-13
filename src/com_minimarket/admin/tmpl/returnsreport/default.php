<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
$mmReportView='returnsreport';
?>
<div id="j-main-container" class="j-main-container">
 <?php require dirname(__DIR__).'/shared/report-period.php'; ?>
 <div class="table-responsive mm-native-list-viewport"><table class="table table-striped mm-admin-list">
 <thead><tr>
  <th><?php echo Text::_('COM_MINIMARKET_RETURN_REASON'); ?></th>
  <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?></th>
  <th><?php echo Text::_('COM_MINIMARKET_REPORT_CASES'); ?></th>
  <th><?php echo Text::_('COM_MINIMARKET_REPORT_UNITS'); ?></th>
 </tr></thead><tbody><?php foreach($this->rows as $r): ?>
 <tr>
  <td><?php echo Text::_('COM_MINIMARKET_RETURN_REASON_'.strtoupper((string)$r->reason)); ?></td>
  <td><?php echo Text::_('COM_MINIMARKET_RETURN_STATUS_'.strtoupper((string)$r->status)); ?></td>
  <td><?php echo (int)$r->cases_count; ?></td>
  <td><?php echo (int)$r->items_count; ?></td>
 </tr><?php endforeach; ?></tbody></table></div>
 <?php if(!$this->rows): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_REPORT_EMPTY'); ?></p><?php endif; ?>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_REPORT_RETURNS_NOTE'); ?></p>
</div>