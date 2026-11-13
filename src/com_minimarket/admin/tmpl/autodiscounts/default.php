<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$mmSearch=['view'=>'autodiscounts','query'=>$this->search,'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_AUTO_DISCOUNTS_TITLE'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[['name'=>'filter_status','label'=>Text::_('JSTATUS'),'value'=>$this->statusFilter,
 'default'=>'all','options'=>['all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),
 'published'=>Text::_('JPUBLISHED'),'unpublished'=>Text::_('JUNPUBLISHED')]]]];
?>
<form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=autodiscounts',false)); ?>"
 method="post" name="adminForm" id="adminForm" class="mm-list-form">
 <div id="j-main-container">
 <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
 <?php $mmColumnTable='mm-auto-discounts';require dirname(__DIR__).'/shared/column-toggle.php'; ?>
 <div class="table-responsive mm-native-list-viewport"><table class="table table-striped mm-admin-list" id="mm-auto-discounts">
  <thead><tr><th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
   <th>ID</th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_AUTO_DISCOUNT_AMOUNT'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_AUTO_DISCOUNT_SCOPE'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_PROMO_START'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_PROMO_END'); ?></th>
   <th><?php echo Text::_('JSTATUS'); ?></th></tr></thead>
  <tbody><?php foreach($this->discounts as $i=>$item): ?><tr>
   <td class="text-center"><?php echo HTMLHelper::_('grid.id',$i,(int)$item->id); ?></td>
   <td>#<?php echo (int)$item->id; ?></td>
   <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=autodiscount&id='.(int)$item->id,false)); ?>"><?php echo $this->escape((string)$item->title); ?></a></td>
   <td><?php echo (string)$item->discount_type==='percent'
      ?number_format((int)$item->basis_points/100,2,'.','').' %'
      :number_format((int)$item->amount_cents/100,2,'.',' ').' ₽'; ?></td>
   <td><?php echo Text::_('COM_MINIMARKET_AUTO_SCOPE_'.strtoupper((string)$item->applies_to)); ?></td>
   <td><?php echo $this->escape((string)($item->starts_at??'—')); ?></td>
   <td><?php echo $this->escape((string)($item->ends_at??'—')); ?></td>
   <td><?php echo Text::_((int)$item->state===1?'JPUBLISHED':'JUNPUBLISHED'); ?></td>
  </tr><?php endforeach; ?></tbody></table></div>
 <?php if(!$this->discounts): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_AUTO_DISCOUNT_EMPTY'); ?></p><?php endif; ?>
 <input type="hidden" name="boxchecked" value="0"><input type="hidden" name="task" value="">
 <?php echo HTMLHelper::_('form.token'); ?>
 <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
 <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>
