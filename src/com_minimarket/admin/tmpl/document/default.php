<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$d=$this->document;$s=$this->snapshot;
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$money=static fn($v)=>number_format((int)$v/100,2,',',' ').' '.$e($s['currency']);
?>
<div class="d-flex flex-wrap gap-2 mb-3">
 <a class="btn btn-outline-secondary" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=documents',false)); ?>"><?php echo Text::_('JTOOLBAR_BACK'); ?></a>
 <a class="btn btn-outline-primary" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$d->order_id,false)); ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?> #<?php echo (int)$d->order_id; ?></a>
 <button class="btn btn-primary" type="button" onclick="window.print()"><?php echo Text::_('COM_MINIMARKET_DOCUMENT_PRINT'); ?></button>
</div>
<div id="j-main-container" class="j-main-container">
 <div class="alert alert-warning"><?php echo Text::_('COM_MINIMARKET_DOCUMENT_NON_FISCAL'); ?></div>
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-document-'.(int)$d->id,['active'=>'main']); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-document-'.(int)$d->id,'main',Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
 <section class="card card-body mm-admin-panel">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_DOCUMENT_TYPE_INVOICE'); ?> <?php echo $e($d->document_number); ?></h2>
  <dl class="row mb-3">
   <dt class="col-sm-4"><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></dt><dd class="col-sm-8">#<?php echo (int)$s['order_id']; ?></dd>
   <dt class="col-sm-4"><?php echo Text::_('COM_MINIMARKET_DOCUMENT_CREATED_AT'); ?></dt><dd class="col-sm-8"><?php echo $e($d->created_at); ?> UTC</dd>
   <dt class="col-sm-4"><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></dt><dd class="col-sm-8"><?php echo $e($s['buyer_name']); ?></dd>
   <dt class="col-sm-4"><?php echo Text::_('COM_MINIMARKET_ORDER_EMAIL'); ?></dt><dd class="col-sm-8"><?php echo $e($s['buyer_email']); ?></dd>
   <dt class="col-sm-4"><?php echo Text::_('COM_MINIMARKET_ORDER_PHONE'); ?></dt><dd class="col-sm-8"><?php echo $e($s['buyer_phone']); ?></dd>
   <dt class="col-sm-4"><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></dt><dd class="col-sm-8"><?php echo $e($s['order_created_at']); ?></dd>
  </dl>
  <div class="table-responsive"><table class="table table-striped mm-admin-list">
   <thead><tr><th><?php echo Text::_('COM_MINIMARKET_PRODUCTS'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ORDER_QUANTITY'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ORDER_ITEMS_TOTAL'); ?></th></tr></thead>
   <tbody><?php foreach($s['lines'] as $line): ?><tr>
    <td><?php echo $e($line['title']); ?><?php if($line['sku']!==''): ?><span class="text-muted"> (<?php echo $e($line['sku']); ?>)</span><?php endif; ?></td>
    <td><?php echo $money($line['unit_cents']); ?></td>
    <td><?php echo (int)$line['quantity']; ?></td>
    <td><?php echo $money($line['line_cents']); ?></td>
   </tr><?php endforeach; ?></tbody>
  </table></div>
  <dl class="row mb-0">
   <dt class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_ITEMS_TOTAL'); ?></dt><dd class="col-sm-3"><?php echo $money($s['items_total_cents']); ?></dd>
   <dt class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_DISCOUNT'); ?></dt><dd class="col-sm-3"><?php echo $money($s['discount_cents']); ?></dd>
   <dt class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_FEE'); ?></dt><dd class="col-sm-3"><?php echo $money($s['delivery_fee_cents']); ?></dd>
   <dt class="col-sm-9"><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></dt><dd class="col-sm-3"><strong><?php echo $money($s['total_cents']); ?></strong></dd>
  </dl>
 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</div>
