<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$mmSearch=[
 'view'=>'promocodes','query'=>$this->search,'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_PROMOCODES_CODE'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_PROMOCODES_STATUS'),
  'value'=>$this->statusFilter,'default'=>'all',
  'options'=>['all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),
   'published'=>Text::_('COM_MINIMARKET_PROMOCODES_ACTIVE'),
   'unpublished'=>Text::_('COM_MINIMARKET_PROMOCODES_INACTIVE')]]]
];
$href='index.php?option=com_minimarket&view=promocodes&'.http_build_query([
 'filter_search'=>$this->search,'filter_status'=>$this->statusFilter,
 'filter_sort'=>$this->sort,'limit'=>$this->pageSize
],'','&',PHP_QUERY_RFC3986);
?>
<div class="mm-unified-list">
 <div id="j-main-container" class="j-main-container">
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=promocodes',false)); ?>"
        method="post" name="adminForm" id="adminForm" class="mm-list-form">
   <div class="table-responsive">
    <table id="mm-promocodes-list" class="table table-striped mm-admin-list">
     <thead><tr>
      <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
      <th>ID</th>
      <th><?php echo Text::_('COM_MINIMARKET_PROMOCODES_CODE'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_PROMOCODES_AMOUNT'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_PROMO_START'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_PROMO_END'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_PROMO_USAGE_LIMIT'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_PROMOCODES_STATUS'); ?></th>
     </tr></thead>
     <tbody>
     <?php foreach($this->codes as $i=>$code): ?>
      <tr>
       <td class="text-center" data-label="<?php echo Text::_('JSELECT'); ?>"><?php echo HTMLHelper::_('grid.id',$i,(int)$code->id); ?></td>
       <td data-label="ID">#<?php echo (int)$code->id; ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_PROMOCODES_CODE'); ?>"><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=promocode&id='.(int)$code->id,false)); ?>"><code><?php echo $this->escape((string)$code->code); ?></code></a></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_PROMOCODES_AMOUNT'); ?>"><?php echo $code->discount_type==='percent' ? $this->escape((string)$code->percent_value).' %' : number_format((int)$code->amount_cents/100,2,'.',' ').' ₽'; ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_PROMO_START'); ?>"><?php echo $this->escape((string)($code->starts_at??'—')); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_PROMO_END'); ?>"><?php echo $this->escape((string)($code->ends_at??'—')); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_PROMO_USAGE_LIMIT'); ?>"><?php echo (int)$code->used_count; ?> / <?php echo $code->usage_limit===null?'∞':(int)$code->usage_limit; ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_PROMOCODES_STATUS'); ?>"><span class="badge bg-<?php echo (int)$code->state===1?'success':'secondary'; ?>"><?php echo Text::_((int)$code->state===1?'COM_MINIMARKET_PROMOCODES_ACTIVE':'COM_MINIMARKET_PROMOCODES_INACTIVE'); ?></span></td>
      </tr>
     <?php endforeach; ?>
     <?php if(!$this->codes): ?><tr><td colspan="8"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_EMPTY'); ?></td></tr><?php endif; ?>
     </tbody>
    </table>
   </div>
   <input type="hidden" name="boxchecked" value="0">
   <input type="hidden" name="task" value="">
   <?php echo HTMLHelper::_('form.token'); ?>
  </form>
  <?php if($this->total>$this->pageSize): ?>
   <nav class="mm-native-pagination">
    <?php if($this->offset>0): ?><a href="<?php echo $this->escape(Route::_($href.'&limitstart='.max(0,$this->offset-$this->pageSize),false)); ?>"><?php echo Text::_('JPREV'); ?></a><?php endif; ?>
    <span><?php echo $this->offset+1; ?>–<?php echo min($this->total,$this->offset+$this->pageSize); ?> / <?php echo $this->total; ?></span>
    <?php if($this->offset+$this->pageSize<$this->total): ?><a href="<?php echo $this->escape(Route::_($href.'&limitstart='.($this->offset+$this->pageSize),false)); ?>"><?php echo Text::_('JNEXT'); ?></a><?php endif; ?>
   </nav>
  <?php endif; ?>
 </div>
</div>
