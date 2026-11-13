<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
$mmReportView='businessreport';
$money=static fn(?int $value)=>$value===null?Text::_('COM_MINIMARKET_FINANCE_UNKNOWN')
    :number_format($value/100,2,',',' ').' ₽';
$labels=[
 'orders'=>'COM_MINIMARKET_REPORT_CASES',
 'goods'=>'COM_MINIMARKET_ORDER_GOODS_NET',
 'shipping_charged'=>'COM_MINIMARKET_REPORT_SHIPPING_CHARGED',
 'goods_cost'=>'COM_MINIMARKET_ORDER_COST_TOTAL',
 'goods_difference'=>'COM_MINIMARKET_ORDER_GROSS_DIFFERENCE',
 'shipping_expense'=>'COM_MINIMARKET_ORDER_DELIVERY_REAL_COST'
];
?>
<div id="j-main-container" class="j-main-container">
 <?php require dirname(__DIR__).'/shared/report-period.php'; ?>
 <section class="card card-body mm-admin-panel">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_BUSINESS_REPORT_TITLE'); ?></h2>
  <dl class="row mb-0">
  <?php foreach($labels as $field=>$label): ?>
    <dt class="col-sm-7"><?php echo Text::_($label); ?></dt>
    <dd class="col-sm-5"><?php echo $field==='orders'?(int)$this->metrics[$field]:$money($this->metrics[$field]); ?></dd>
  <?php endforeach; ?>
  </dl>
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_REPORT_BUSINESS_NOTE'); ?></p>
 </section>
</div>