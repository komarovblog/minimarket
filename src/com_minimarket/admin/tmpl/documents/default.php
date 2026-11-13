<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$mmSearch=[
 'view'=>'documents','query'=>$this->search,
 'searchLabel'=>Text::_('COM_MINIMARKET_DOCUMENT_NUMBER'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),
                 'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[]
];
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<form id="adminForm" name="adminForm" method="post" class="mm-list-form"
 action="<?php echo $e(Route::_('index.php?option=com_minimarket&view=documents',false)); ?>">
 <div id="j-main-container" class="j-main-container">
  <?php if($this->orderFilter>0): ?>
   <div class="mb-3"><a href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=order&id='.$this->orderFilter,false)); ?>" class="btn btn-outline-secondary"><?php echo Text::_('JTOOLBAR_BACK'); ?> #<?php echo (int)$this->orderFilter; ?></a></div>
   <input type="hidden" name="filter_order" value="<?php echo (int)$this->orderFilter; ?>">
  <?php endif; ?>
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-document-list';require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table id="mm-document-list" class="table table-striped mm-admin-list">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_DOCUMENT_NUMBER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_DOCUMENT_TYPE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_AMOUNT'); ?></th>
     <th><?php echo Text::_('JDATE'); ?></th>
    </tr></thead>
    <tbody><?php foreach($this->documents as $item): ?><tr>
     <td><a href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=document&id='.(int)$item->id,false)); ?>"><?php echo $e($item->document_number); ?></a></td>
     <td><a href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$item->order_id,false)); ?>">#<?php echo (int)$item->order_id; ?></a></td>
     <td><?php echo $e($item->customer_name); ?></td>
     <td><?php echo Text::_('COM_MINIMARKET_DOCUMENT_TYPE_INVOICE'); ?></td>
     <td><?php echo number_format((int)$item->total_cents/100,2,',',' '); ?> <?php echo $e($item->currency); ?></td>
     <td><?php echo $e($item->created_at); ?></td>
    </tr><?php endforeach; ?></tbody>
   </table>
  </div>
  <?php if(!$this->documents): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_DOCUMENTS_EMPTY'); ?></p><?php endif; ?>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>
