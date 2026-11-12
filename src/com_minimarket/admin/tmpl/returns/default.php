<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$statusValues=['new','approved','received','rejected','closed'];
$reasonValues=['defective','damaged','wrong_item','not_fit','other'];
$statuses=[''=>Text::_('COM_MINIMARKET_FILTER_ANY')];
$reasons=[''=>Text::_('COM_MINIMARKET_FILTER_ANY')];
foreach($statusValues as $value)$statuses[$value]=Text::_('COM_MINIMARKET_RETURN_STATUS_'.strtoupper($value));
foreach($reasonValues as $value)$reasons[$value]=Text::_('COM_MINIMARKET_RETURN_REASON_'.strtoupper($value));
$mmSearch=[
 'view'=>'returns','query'=>$this->search,
 'searchLabel'=>Text::_('COM_MINIMARKET_ORDER_NUMBER'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
  ['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_ORDER_STATUS'),
   'value'=>$this->statusFilter,'options'=>$statuses],
  ['name'=>'filter_reason','label'=>Text::_('COM_MINIMARKET_RETURN_REASON'),
   'value'=>$this->reasonFilter,'options'=>$reasons]
 ]
];
?>
<form method="post" name="adminForm" id="adminForm" class="mm-list-form"
 action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=returns',false)); ?>">
 <div id="j-main-container" class="j-main-container">
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
    <?php $mmColumnTable='mm-returns-list'; require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table class="table table-striped mm-admin-list" id="mm-returns-list">
    <thead><tr>
     <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_RETURN_NUMBER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_RETURN_REASON'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_RETURN_RESOLUTION'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
    </tr></thead><tbody>
    <?php foreach($this->returns as $i=>$r): ?>
     <tr>
      <td class="text-center"><?php echo HTMLHelper::_('grid.id',$i,(int)$r->id); ?></td>
      <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=returncase&id='.(int)$r->id,false)); ?>">#<?php echo (int)$r->id; ?></a></td>
      <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$r->order_id,false)); ?>">#<?php echo (int)$r->order_id; ?></a></td>
      <td><?php echo $this->escape((string)$r->customer_name); ?></td>
      <td><?php echo Text::_('COM_MINIMARKET_RETURN_REASON_'.strtoupper((string)$r->reason)); ?></td>
      <td><?php echo Text::_('COM_MINIMARKET_RETURN_STATUS_'.strtoupper((string)$r->status)); ?></td>
      <td><?php echo Text::_('COM_MINIMARKET_RETURN_RESOLUTION_'.strtoupper((string)$r->resolution)); ?></td>
      <td><?php if($r->shipment_id): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=delivery&id='.(int)$r->shipment_id,false)); ?>">#<?php echo (int)$r->shipment_id; ?></a><?php else: ?>—<?php endif; ?></td>
      <td><?php echo $this->escape((string)$r->created_at); ?></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
   <?php if(!$this->returns): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_RETURNS_EMPTY'); ?></p><?php endif; ?>
  </div>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="0">
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
 </div>
</form>
