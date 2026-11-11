<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$colors=['new'=>'info','in_transit'=>'primary','delivered'=>'success','cancelled'=>'secondary'];
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
 ['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_DELIVERY_STATUS'),
   'value'=>$this->status,'options'=>$statusOptions]]
];
$href='index.php?option=com_minimarket&view=deliveries&'.http_build_query([
 'filter_search'=>$this->search,'filter_status'=>$this->status,'filter_carrier'=>$this->carrierFilter,
 'filter_sort'=>$this->sort,'limit'=>$this->pageSize
],'','&',PHP_QUERY_RFC3986);
?>
<div class="mm-native-entity-list">
 <div id="j-main-container" class="j-main-container">
  
  <form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries',false)); ?>"
        method="post" name="adminForm" id="adminForm" class="mm-list-form">
    <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>

   <div class="table-responsive mm-native-list-viewport">
    <table id="mm-deliveries-list" class="table table-striped mm-admin-list">
     <thead><tr>
      <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?></th>
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
       <td data-label="<?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?>"><?php echo $this->escape((string)$delivery->tracking_number); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_DELIVERY_CITY'); ?>"><?php echo $this->escape((string)$delivery->delivery_city); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?>"><span class="badge bg-<?php echo $colors[(string)$delivery->status]??'secondary'; ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS_'.strtoupper((string)$delivery->status)); ?></span></td>
      </tr>
     <?php endforeach; ?>
     <?php if(!$this->deliveries): ?><tr><td colspan="9"><?php echo Text::_('COM_MINIMARKET_DELIVERIES_NONE'); ?></td></tr><?php endif; ?>
     </tbody>
    </table>
   </div>
   <input type="hidden" name="boxchecked" value="0">
   <input type="hidden" name="task" value="">
   <?php echo HTMLHelper::_('form.token'); ?>
   <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
 <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </form>
  
 </div>
</div>
