<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$colors=['new'=>'secondary','awaiting_assembly'=>'secondary','assembling'=>'info',
 'ready'=>'primary','in_transit'=>'primary','awaiting_pickup'=>'info',
 'delivered'=>'success','failed'=>'warning','cancelled'=>'secondary'];
$statusOptions=[''=>Text::_('COM_MINIMARKET_FILTER_ANY')];
foreach(array_keys($colors) as $value)$statusOptions[$value]=Text::_('COM_MINIMARKET_DELIVERY_STATUS_'.strtoupper($value));
$mmSearch=[
 'view'=>'deliveries','query'=>$this->search,'searchLabel'=>Text::_('COM_MINIMARKET_DELIVERY_ORDER'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
 ['name'=>'filter_carrier','label'=>Text::_('COM_MINIMARKET_DELIVERY_CARRIER'),
  'value'=>$this->carrierFilter,
  'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
   'own'=>Text::_('COM_MINIMARKET_DELIVERY_CARRIER_OWN'),
   'cdek'=>Text::_('COM_MINIMARKET_DELIVERY_CARRIER_CDEK'),
   'other'=>Text::_('COM_MINIMARKET_DELIVERY_CARRIER_OTHER')]],
 ['name'=>'filter_reason','label'=>Text::_('COM_MINIMARKET_DELIVERY_REASON'),
  'value'=>$this->reasonFilter,
  'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
   'initial'=>Text::_('COM_MINIMARKET_DELIVERY_REASON_INITIAL'),
   'redelivery'=>Text::_('COM_MINIMARKET_DELIVERY_REASON_REDELIVERY'),
   'replacement'=>Text::_('COM_MINIMARKET_DELIVERY_REASON_REPLACEMENT'),
   'other'=>Text::_('COM_MINIMARKET_DELIVERY_REASON_OTHER')]],
 ['name'=>'filter_problem','label'=>Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_TYPE'),
  'value'=>$this->problemFilter,
  'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
   'open'=>Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_OPEN'),
   'resolved'=>Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_RESOLVED')]],
 ['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_DELIVERY_STATUS'),
   'value'=>$this->status,'options'=>$statusOptions]]
];
$href='index.php?option=com_minimarket&view=deliveries&'.http_build_query([
 'filter_search'=>$this->search,'filter_status'=>$this->status,'filter_carrier'=>$this->carrierFilter,
 'filter_reason'=>$this->reasonFilter,'filter_problem'=>$this->problemFilter,
 'filter_sort'=>$this->sort,'limit'=>$this->pageSize
],'','&',PHP_QUERY_RFC3986);
?>
<form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries',false)); ?>"
        method="post" name="adminForm" id="adminForm" class="mm-list-form">
 <div id="j-main-container" class="j-main-container">

    <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
    <?php $mmColumnTable='mm-deliveries-list'; require dirname(__DIR__).'/shared/column-toggle.php'; ?>

   <div class="table-responsive mm-native-list-viewport">
    <table id="mm-deliveries-list" class="table table-striped mm-admin-list">
     <thead><tr>
      <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_TYPE'); ?></th>
     </tr></thead>
     <tbody>
     <?php foreach($this->deliveries as $i=>$delivery): ?>
      <tr>
       <td class="text-center" data-label="<?php echo Text::_('JSELECT'); ?>"><?php echo HTMLHelper::_('grid.id',$i,(int)$delivery->id); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?>"><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=delivery&id='.(int)$delivery->id,false)); ?>">#<?php echo (int)$delivery->id; ?></a></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?>"><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$delivery->order_id,false)); ?>">#<?php echo (int)$delivery->order_id; ?></a></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?>"><?php echo $this->escape($delivery->created_at); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?>"><?php echo $this->escape($delivery->customer_name); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER_'.strtoupper((string)$delivery->carrier)); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON'); ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON_'.strtoupper((string)$delivery->reason)); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?>"><?php echo $this->escape((string)$delivery->tracking_number); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?>"><?php echo $this->escape((string)$delivery->delivery_city); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?>"><span class="badge bg-<?php echo $colors[(string)$delivery->status]??'secondary'; ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS_'.strtoupper((string)$delivery->status)); ?></span></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_TYPE'); ?>">
        <?php if((string)$delivery->problem_status==='open'): ?>
         <span class="badge bg-warning text-dark"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_'.strtoupper((string)$delivery->problem_code)); ?></span>
        <?php elseif((string)$delivery->problem_status==='resolved'): ?>
         <span class="badge bg-secondary"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_RESOLVED'); ?></span>
        <?php else: ?>—<?php endif; ?>
       </td>
      </tr>
     <?php endforeach; ?>
     </tbody>
    </table>
<?php if (!$this->deliveries): ?><div class="alert alert-info mt-2" role="status"><?php echo Text::_('COM_MINIMARKET_DELIVERIES_NONE'); ?></div><?php endif; ?>
   </div>
   <input type="hidden" name="boxchecked" value="0">
   <input type="hidden" name="task" value="">
   <?php echo HTMLHelper::_('form.token'); ?>
   <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
 <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
  </div>
</form>
