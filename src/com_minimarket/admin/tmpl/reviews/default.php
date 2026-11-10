<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$states=[0=>'COM_MINIMARKET_REVIEWS_PENDING',1=>'COM_MINIMARKET_REVIEWS_PUBLISHED',2=>'COM_MINIMARKET_REVIEWS_REJECTED'];
$colors=[0=>'warning',1=>'success',2=>'secondary'];
?>
<section class="mm-admin-panel mm-admin-list-panel">
 <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_REVIEWS_TITLE'); ?></h2>
  <?php if($this->canCreate) : ?><a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=review&layout=create',false)); ?>"><span class="icon-plus"></span> <?php echo Text::_('COM_MINIMARKET_REVIEW_NEW'); ?></a><?php endif; ?>
 </div>
 <form method="get" action="index.php" class="d-flex gap-2 align-items-end mb-3">
  <input type="hidden" name="option" value="com_minimarket"><input type="hidden" name="view" value="reviews">
  <label><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?>
   <select name="filter_status" class="form-select">
    <option value="all"<?php echo $this->statusFilter==='all'?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
    <?php foreach(['pending'=>0,'published'=>1,'rejected'=>2] as $state=>$val) : ?>
     <option value="<?php echo $state; ?>"<?php echo $this->statusFilter===$state?' selected':''; ?>><?php echo Text::_($states[$val]); ?></option>
    <?php endforeach; ?>
   </select></label>
  <button class="btn btn-primary" type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
 </form>
 <div class="table-responsive">
 <table class="table table-striped mm-admin-list">
  <thead><tr>
   <th>ID</th><th><?php echo Text::_('COM_MINIMARKET_REVIEW_PRODUCT'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_REVIEW_AUTHOR'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_REVIEW_RATING'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_REVIEW_TEXT'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></th>
  </tr></thead>
  <tbody>
  <?php foreach($this->reviews as $review) : ?>
   <tr>
    <td>#<?php echo (int)$review->id; ?></td>
    <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=product.edit&id='.(int)$review->product_id,false)); ?>"><?php echo $this->escape($review->product_title); ?></a></td>
    <td><?php echo $this->escape($review->author_name); ?></td>
    <td><?php echo (int)$review->rating; ?> / 5</td>
    <td><strong><?php echo $this->escape($review->title); ?></strong><p class="small mb-0"><?php echo $this->escape(mb_substr((string)$review->body,0,250)); ?></p></td>
    <td><?php echo $this->escape($review->created_at); ?></td>
    <td>
     <span class="badge bg-<?php echo $colors[(int)$review->state]??'secondary'; ?>"><?php echo Text::_($states[(int)$review->state]??$states[2]); ?></span>
     <?php if($this->canModerate) : ?>
      <form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=review.moderate',false)); ?>" method="post" class="mt-1">
       <input type="hidden" name="id" value="<?php echo (int)$review->id; ?>">
       <select name="state" class="form-select form-select-sm" aria-label="<?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?>">
        <?php foreach($states as $value=>$label) : ?><option value="<?php echo $value; ?>"<?php echo (int)$review->state===$value?' selected':''; ?>><?php echo Text::_($label); ?></option><?php endforeach; ?>
       </select>
       <?php echo HTMLHelper::_('form.token'); ?>
       <button class="btn btn-outline-primary btn-sm mt-1" type="submit"><?php echo Text::_('COM_MINIMARKET_REVIEW_SAVE_STATUS'); ?></button>
      </form>
     <?php endif; ?>
    </td>
   </tr>
  <?php endforeach; ?>
  <?php if(!$this->reviews): ?><tr><td colspan="7"><?php echo Text::_('COM_MINIMARKET_REVIEW_NO_ROWS'); ?></td></tr><?php endif; ?>
  </tbody>
 </table></div>
 <nav class="d-flex gap-3">
  <?php if($this->offset>0): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=reviews&filter_status='.$this->statusFilter.'&limitstart='.max(0,$this->offset-$this->pageSize),false)); ?>"><?php echo Text::_('JPREV'); ?></a><?php endif; ?>
  <?php if($this->offset+$this->pageSize<$this->total): ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=reviews&filter_status='.$this->statusFilter.'&limitstart='.($this->offset+$this->pageSize),false)); ?>"><?php echo Text::_('JNEXT'); ?></a><?php endif; ?>
 </nav>
</section>
