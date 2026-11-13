<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$statuses=['new'=>'info','confirmed'=>'primary','completed'=>'success','cancelled'=>'secondary'];
$paymentStatuses=['unpaid'=>'secondary','processing'=>'info','paid'=>'success','refunded'=>'secondary'];
$fulfillmentStatuses=['pending'=>'secondary','shipped'=>'primary',
 'awaiting_assembly'=>'secondary','assembling'=>'info','ready'=>'primary',
 'in_transit'=>'primary','awaiting_pickup'=>'info','delivered'=>'success','failed'=>'warning'];
$mmSearch=[
 'view'=>'orders','query'=>$this->search,'searchLabel'=>Text::_('COM_MINIMARKET_ORDERS_FILTER_CUSTOMER'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
   ['name'=>'filter_product','type'=>'text','label'=>Text::_('COM_MINIMARKET_ORDERS_FILTER_PRODUCT'),'value'=>$this->productSearch],
   ['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_ORDER_STATUS'),'value'=>$this->statusFilter,
    'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY')]+array_combine(array_keys($statuses),array_map(
       static fn($v)=>Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper($v)),array_keys($statuses)))],
   ['name'=>'filter_payment','label'=>Text::_('COM_MINIMARKET_PAYMENT_STATUS'),'value'=>$this->paymentFilter,
    'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY')]+array_combine(array_keys($paymentStatuses),array_map(
       static fn($v)=>Text::_('COM_MINIMARKET_PAYMENT_STATUS_'.strtoupper($v)),array_keys($paymentStatuses)))],
   ['name'=>'filter_fulfillment','label'=>Text::_('COM_MINIMARKET_FULFILLMENT_STATUS'),'value'=>$this->fulfillmentFilter,
    'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY')]+array_combine(array_keys($fulfillmentStatuses),array_map(
       static fn($v)=>Text::_('COM_MINIMARKET_FULFILLMENT_STATUS_'.strtoupper($v)),array_keys($fulfillmentStatuses)))],
   ['name'=>'filter_delivery','label'=>Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'),'value'=>$this->deliveryFilter,
    'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
       'pickup'=>Text::_('COM_MINIMARKET_ORDER_DELIVERY_PICKUP'),
       'courier'=>Text::_('COM_MINIMARKET_ORDER_DELIVERY_COURIER')]],
   ['name'=>'filter_quick','label'=>Text::_('COM_MINIMARKET_ORDERS_QUICK_FILTERS'),'value'=>$this->quickFilter,
    'options'=>[
      ''=>Text::_('COM_MINIMARKET_ORDERS_QUICK_ALL'),
      'new'=>Text::_('COM_MINIMARKET_ORDERS_QUICK_NEW'),
      'due_first'=>Text::sprintf('COM_MINIMARKET_ORDERS_QUICK_DUE_FIRST',$this->remindFirst),
      'due_final'=>Text::sprintf('COM_MINIMARKET_ORDERS_QUICK_DUE_FINAL',$this->remindFinal),
      'overdue'=>Text::_('COM_MINIMARKET_ORDERS_QUICK_OVERDUE'),
      'unpaid_reserved'=>Text::_('COM_MINIMARKET_ORDERS_QUICK_UNPAID_RESERVED'),
      'paid_uncollected'=>Text::_('COM_MINIMARKET_ORDERS_QUICK_PAID_UNCOLLECTED')]],
   ['name'=>'filter_trash','label'=>Text::_('COM_MINIMARKET_LIST_ORDER_VISIBILITY'),
    'value'=>$this->showTrash?'1':'0','default'=>'0',
    'options'=>['0'=>Text::_('COM_MINIMARKET_ORDER_SHOW_ACTIVE'),'1'=>Text::_('COM_MINIMARKET_ORDER_SHOW_TRASH')]]
 ]
];
?>
<form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=orders',false)); ?>"
      method="post" name="adminForm" id="adminForm" class="mm-list-form">
 <div id="j-main-container" class="j-main-container">
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-orders-list'; require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table id="mm-orders-list" class="table table-striped mm-admin-list mm-orders-compact">
    <thead><tr>
     <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_FULFILLMENT_STATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_RESERVE_UNTIL'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th>
    </tr></thead>
    <tbody>
     <?php foreach($this->orders as $i=>$order):
      $validReserve=($order->reservation_state??'')==='held'&&!empty($order->reserved_until);
      $pastDue=$validReserve&&strtotime((string)$order->reserved_until)<time();
     ?>
      <tr>
       <td class="text-center" data-label="<?php echo Text::_('JSELECT'); ?>">
        <?php echo HTMLHelper::_('grid.id',$i,(int)$order->id); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?>">
        <a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$order->id,false)); ?>">#<?php echo (int)$order->id; ?></a></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?>"><?php echo HTMLHelper::_('date',$order->created_at,'d.m.Y H:i'); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?>"><?php echo $this->escape($order->customer_name); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?>">
        <span class="badge bg-<?php echo $statuses[(string)$order->status]??'secondary'; ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper((string)$order->status)); ?></span></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS'); ?>">
        <span class="badge bg-<?php echo $paymentStatuses[(string)$order->payment_status]??'secondary'; ?>"><?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS_'.strtoupper((string)$order->payment_status)); ?></span></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_FULFILLMENT_STATUS'); ?>">
        <span class="badge bg-<?php echo $fulfillmentStatuses[(string)$order->fulfillment_status]??'secondary'; ?>"><?php echo Text::_('COM_MINIMARKET_FULFILLMENT_STATUS_'.strtoupper((string)$order->fulfillment_status)); ?></span></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_RESERVE_UNTIL'); ?>">
        <?php if($validReserve): ?>
         <span class="<?php echo $pastDue?'text-danger fw-semibold':''; ?>"><?php echo HTMLHelper::_('date',$order->reserved_until,'d.m.Y H:i'); ?></span>
        <?php else: ?>&mdash;<?php endif; ?>
       </td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?>"><span class="text-nowrap"><?php echo number_format((int)$order->total_cents/100,2,','," "); ?> ₽</span></td>
      </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
<?php if (!$this->orders): ?><div class="alert alert-info mt-2" role="status"><?php echo Text::_('COM_MINIMARKET_ORDERS_EMPTY'); ?></div><?php endif; ?>
      </div>
      <input type="hidden" name="boxchecked" value="0">
      <input type="hidden" name="task" value="">
      <?php echo HTMLHelper::_('form.token'); ?>
     <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
 <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
  </div>
</form>
