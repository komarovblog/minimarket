<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$statuses=['new'=>'info','confirmed'=>'primary','completed'=>'success','cancelled'=>'secondary'];
$mmSearch=[
 'view'=>'orders','query'=>$this->search,'searchLabel'=>Text::_('COM_MINIMARKET_ORDERS_FILTER_CUSTOMER'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
   ['name'=>'filter_product','type'=>'text','label'=>Text::_('COM_MINIMARKET_ORDERS_FILTER_PRODUCT'),'value'=>$this->productSearch],
   ['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_ORDER_STATUS'),'value'=>$this->statusFilter,
    'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY')]+array_combine(array_keys($statuses),array_map(
       static fn($v)=>Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper($v)),array_keys($statuses)))],
   ['name'=>'filter_delivery','label'=>Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'),'value'=>$this->deliveryFilter,
    'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
       'pickup'=>Text::_('COM_MINIMARKET_ORDER_DELIVERY_PICKUP'),
       'courier'=>Text::_('COM_MINIMARKET_ORDER_DELIVERY_COURIER')]],
   ['name'=>'filter_trash','label'=>Text::_('COM_MINIMARKET_LIST_ORDER_VISIBILITY'),
    'value'=>$this->showTrash?'1':'0','default'=>'0',
    'options'=>['0'=>Text::_('COM_MINIMARKET_ORDER_SHOW_ACTIVE'),'1'=>Text::_('COM_MINIMARKET_ORDER_SHOW_TRASH')]]
 ]
];
$params=[
 'filter_search'=>$this->search,'filter_product'=>$this->productSearch,
 'filter_status'=>$this->statusFilter,'filter_delivery'=>$this->deliveryFilter,
 'filter_trash'=>$this->showTrash?1:0,'filter_sort'=>$this->sort,'limit'=>$this->pageSize
];
$href='index.php?option=com_minimarket&view=orders&'.http_build_query($params,'','&',PHP_QUERY_RFC3986);
?>
<form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=orders',false)); ?>"
          method="post" name="adminForm" id="adminForm" class="mm-list-form">
 <div id="j-main-container" class="j-main-container">

    <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>

      <div class="table-responsive mm-native-list-viewport">
        <table id="mm-orders-list" class="table table-striped mm-admin-list">
          <thead><tr>
            <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_PHONE'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></th>
            <th><?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE'); ?></th>
          </tr></thead>
          <tbody>
          <?php foreach($this->orders as $i=>$order): ?>
            <tr>
              <td class="text-center" data-label="<?php echo Text::_('JSELECT'); ?>"><?php echo HTMLHelper::_('grid.id',$i,(int)$order->id); ?></td>
              <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?>">
                <a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$order->id,false)); ?>">#<?php echo (int)$order->id; ?></a></td>
              <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?>"><?php echo $this->escape($order->created_at); ?></td>
              <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?>"><?php echo $this->escape($order->customer_name); ?></td>
              <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_PHONE'); ?>"><?php echo $this->escape($order->phone); ?></td>
              <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?>"><?php echo number_format((int)$order->total_cents/100,2,'.',' '); ?> ₽</td>
              <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_METHOD'); ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_'.strtoupper((string)$order->delivery_method)); ?></td>
              <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?>"><span class="badge bg-<?php echo $statuses[(string)$order->status]??'secondary'; ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS_'.strtoupper((string)$order->status)); ?></span></td>
              <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE'); ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_SOURCE_'.strtoupper((string)$order->order_source)); ?></td>
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
