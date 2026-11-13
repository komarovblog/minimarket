<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$mmReportView='salesreport';
?>
<div id="j-main-container" class="j-main-container">
 <?php require dirname(__DIR__).'/shared/report-period.php'; ?>
 <div class="table-responsive mm-native-list-viewport"><table class="table table-striped mm-admin-list">
  <thead><tr>
   <th><?php echo Text::_('COM_MINIMARKET_PRODUCTS'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_REPORT_UNITS'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_REPORT_GROSS_GOODS'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_ORDER_COST_TOTAL'); ?></th>
  </tr></thead>
  <tbody><?php foreach($this->rows as $r): ?>
   <tr>
    <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=product.edit&id='.(int)$r->product_id,false)); ?>"><?php echo $this->escape($r->title); ?></a></td>
    <td><?php echo (int)$r->qty; ?></td>
    <td><?php echo number_format((int)$r->gross_cents/100,2,',',' '); ?> ₽</td>
    <td><?php echo (int)$r->unknown_cost_lines>0?Text::_('COM_MINIMARKET_FINANCE_UNKNOWN'):number_format((int)$r->known_cost_cents/100,2,',',' ').' ₽'; ?></td>
   </tr>
  <?php endforeach; ?></tbody>
 </table></div>
 <?php if(!$this->rows): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_REPORT_EMPTY'); ?></p><?php endif; ?>
 <?php if($this->truncated): ?><p class="form-text"><?php echo Text::_('COM_MINIMARKET_REPORT_TOP100'); ?></p><?php endif; ?>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_REPORT_SALES_NOTE'); ?></p>
</div>