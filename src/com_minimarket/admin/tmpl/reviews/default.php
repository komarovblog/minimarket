<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$states=[0=>'COM_MINIMARKET_REVIEWS_PENDING',1=>'COM_MINIMARKET_REVIEWS_PUBLISHED',2=>'COM_MINIMARKET_REVIEWS_REJECTED'];
$colors=[0=>'warning',1=>'success',2=>'secondary'];
$mmSearch=[
 'view'=>'reviews','query'=>$this->search,'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_REVIEW_AUTHOR'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
 ['name'=>'filter_rating','label'=>Text::_('COM_MINIMARKET_REVIEW_RATING'),
  'value'=>$this->ratingFilter,
  'options'=>[0=>Text::_('COM_MINIMARKET_FILTER_ANY'),1=>'1',2=>'2',3=>'3',4=>'4',5=>'5']],
 ['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_ORDER_STATUS'),
  'value'=>$this->statusFilter,'default'=>'pending',
  'options'=>['all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),'pending'=>Text::_($states[0]),
   'published'=>Text::_($states[1]),'rejected'=>Text::_($states[2])]]]
];
$href='index.php?option=com_minimarket&view=reviews&'.http_build_query([
 'filter_search'=>$this->search,'filter_status'=>$this->statusFilter,'filter_rating'=>$this->ratingFilter,
 'filter_sort'=>$this->sort,'limit'=>$this->pageSize
],'','&',PHP_QUERY_RFC3986);
?>
<form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=reviews',false)); ?>"
        method="post" name="adminForm" id="adminForm" class="mm-list-form">
 <div id="j-main-container" class="j-main-container">

    <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>

   <div class="table-responsive mm-native-list-viewport">
    <table id="mm-reviews-list" class="table table-striped mm-admin-list">
     <thead><tr>
      <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
      <th>ID</th>
      <th><?php echo Text::_('COM_MINIMARKET_REVIEW_PRODUCT'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_REVIEW_AUTHOR'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_REVIEW_RATING'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_REVIEW_TEXT'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
      <th><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></th>
     </tr></thead>
     <tbody>
     <?php foreach($this->reviews as $i=>$review): ?>
      <tr>
       <td class="text-center" data-label="<?php echo Text::_('JSELECT'); ?>"><?php echo HTMLHelper::_('grid.id',$i,(int)$review->id); ?></td>
       <td data-label="ID">#<?php echo (int)$review->id; ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_REVIEW_PRODUCT'); ?>"><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=product.edit&id='.(int)$review->product_id,false)); ?>"><?php echo $this->escape($review->product_title); ?></a></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_REVIEW_AUTHOR'); ?>"><?php echo $this->escape($review->author_name); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_REVIEW_RATING'); ?>"><?php echo (int)$review->rating; ?> / 5</td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_REVIEW_TEXT'); ?>"><strong><?php echo $this->escape($review->title); ?></strong><small class="d-block text-muted"><?php echo $this->escape(mb_substr((string)$review->body,0,130)); ?></small></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?>"><?php echo $this->escape($review->created_at); ?></td>
       <td data-label="<?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?>"><span class="badge bg-<?php echo $colors[(int)$review->state]??'secondary'; ?>"><?php echo Text::_($states[(int)$review->state]??$states[2]); ?></span></td>
      </tr>
     <?php endforeach; ?>
     </tbody>
    </table>
<?php if (!$this->reviews): ?><div class="alert alert-info mt-2" role="status"><?php echo Text::_('COM_MINIMARKET_REVIEW_NO_ROWS'); ?></div><?php endif; ?>
   </div>
   <input type="hidden" name="boxchecked" value="0">
   <input type="hidden" name="task" value="">
   <?php echo HTMLHelper::_('form.token'); ?>
   <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
 <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
  </div>
</form>
