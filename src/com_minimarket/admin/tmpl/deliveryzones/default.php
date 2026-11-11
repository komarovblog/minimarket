<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
$mmSearch=['view'=>'deliveryzones','query'=>$this->search,'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_ZONES_TITLE'),
 'filters'=>[
 ['name'=>'filter_rate','label'=>Text::_('COM_MINIMARKET_ZONE_RATE_MODE'),
  'value'=>$this->rateFilter,'options'=>[
   ''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
   'fixed'=>Text::_('COM_MINIMARKET_ZONE_RATE_FIXED'),
   'per_kg'=>Text::_('COM_MINIMARKET_ZONE_RATE_PER_KG'),
   'per_liter'=>Text::_('COM_MINIMARKET_ZONE_RATE_PER_LITER')]],
 ['name'=>'filter_status','label'=>Text::_('JSTATUS'),
  'value'=>$this->statusFilter,'options'=>[
   'all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),
   'published'=>Text::_('JPUBLISHED'),
   'unpublished'=>Text::_('JUNPUBLISHED')]]
 ],
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')]];
$href='index.php?option=com_minimarket&view=deliveryzones&'.http_build_query([
 'filter_search'=>$this->search,'filter_sort'=>$this->sort,'limit'=>$this->pageSize,
 'filter_rate'=>$this->rateFilter,'filter_status'=>$this->statusFilter
],'','&',PHP_QUERY_RFC3986);
?>
<div class="mm-native-entity-list"><div id="j-main-container" class="j-main-container">
 <form method="post" id="adminForm" name="adminForm" class="mm-list-form"
       action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveryzones',false)); ?>">
    <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
 <div class="table-responsive mm-native-list-viewport">
  <table class="table table-striped mm-admin-list" id="mm-deliveryzones-list">
   <thead><tr>
    <th>ID</th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ZONE_CITIES'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ZONE_RATE_MODE'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ZONE_BASE_FEE'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_ZONE_FREE_FROM'); ?></th>
    <th><?php echo Text::_('JSTATUS'); ?></th>
   </tr></thead><tbody>
   <?php foreach($this->zones as $z): ?>
    <tr>
     <td><?php echo (int)$z->id; ?></td>
     <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveryzone&id='.(int)$z->id,false)); ?>"><?php echo $this->escape($z->title); ?></a></td>
     <td><?php echo $this->escape(mb_substr(str_replace("\n",', ',(string)$z->cities),0,180)); ?></td>
     <td><?php echo Text::_('COM_MINIMARKET_ZONE_RATE_'.strtoupper($z->rate_mode)); ?></td>
     <td><?php echo number_format((int)$z->base_fee_cents/100,2,'.',' '); ?></td>
     <td><?php echo number_format((int)$z->free_from_cents/100,2,'.',' '); ?></td>
     <td><span class="badge bg-<?php echo (int)$z->state===1?'success':'secondary'; ?>"><?php echo Text::_((int)$z->state===1?'JPUBLISHED':'JUNPUBLISHED'); ?></span></td>
    </tr>
   <?php endforeach; ?>
   <?php if(!$this->zones): ?><tr><td colspan="7"><?php echo Text::_('COM_MINIMARKET_REF_EMPTY'); ?></td></tr><?php endif; ?>
   </tbody></table>
 </div>
 <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <input type="hidden" name="boxchecked" value="0">
  <input type="hidden" name="task" value="">
  <?php echo HTMLHelper::_('form.token'); ?>
 </form>
</div></div>