<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$statuses=['unpaid','processing','paid','refunded'];
$mmSearch=[
 'view'=>'payments','query'=>$this->search,
 'searchLabel'=>Text::_('COM_MINIMARKET_ORDERS_FILTER_CUSTOMER'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),
                'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
  ['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_PAYMENT_STATUS'),
   'value'=>$this->statusFilter,
   'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY')]+array_combine(
       $statuses,array_map(static fn($v)=>Text::_('COM_MINIMARKET_PAYMENT_STATUS_'.strtoupper($v)),$statuses))],
  ['name'=>'filter_method','label'=>Text::_('COM_MINIMARKET_ORDER_PAYMENT_METHOD'),
   'value'=>$this->methodFilter,
   'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
               'on_receipt'=>Text::_('COM_MINIMARKET_ORDER_PAYMENT_ON_RECEIPT'),
               'bank_transfer'=>Text::_('COM_MINIMARKET_ORDER_PAYMENT_BANK_TRANSFER'),
               'pending'=>Text::_('COM_MINIMARKET_ORDER_PAYMENT_PENDING')]]
 ]
];
?>
<form method="post" id="adminForm" name="adminForm" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=payments',false)); ?>" class="mm-list-form">
 <div id="j-main-container" class="j-main-container">
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-payments-list'; require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table class="table table-striped mm-admin-list mm-orders-compact" id="mm-payments-list">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_METHOD'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_PAID_AT'); ?></th>
    </tr></thead>
    <tbody>
    <?php foreach($this->payments as $item): ?>
     <tr>
      <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=payment&id='.(int)$item->id,false)); ?>">#<?php echo (int)$item->id; ?></a></td>
      <td><?php echo HTMLHelper::_('date',$item->created_at,'d.m.Y H:i'); ?></td>
      <td><?php echo $this->escape($item->customer_name); ?></td>
      <td><?php echo Text::_('COM_MINIMARKET_PAYMENT_STATUS_'.strtoupper((string)$item->payment_status)); ?></td>
      <td><?php echo Text::_('COM_MINIMARKET_ORDER_PAYMENT_'.strtoupper((string)$item->payment_method)); ?></td>
      <td class="text-nowrap"><?php echo number_format((int)$item->total_cents/100,2,','," "); ?> <?php echo $this->escape($item->currency); ?></td>
      <td><?php echo $item->paid_at?HTMLHelper::_('date',$item->paid_at,'d.m.Y H:i'):'—'; ?></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php if(!$this->payments): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_PAYMENTS_EMPTY'); ?></p><?php endif; ?>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="0">
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>
