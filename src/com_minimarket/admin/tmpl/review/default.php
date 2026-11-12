<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$r=$this->review;
$e=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<div class="btn-toolbar d-flex flex-wrap gap-2 mb-3" role="toolbar">
 <?php if($this->canEdit): ?>
 <button class="btn btn-success" type="submit" form="mm-review-edit"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
 <?php endif; ?>
 <a class="btn btn-outline-secondary" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=reviews&filter_status=all',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<div id="j-main-container" class="j-main-container">
 <form id="mm-review-edit" method="post" action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=review.update',false)); ?>" class="card card-body mm-admin-panel">
  <input type="hidden" name="id" value="<?php echo (int)$r->id; ?>">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_REVIEWS_TITLE'); ?> #<?php echo (int)$r->id; ?></h2>
  <p><a href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=product&layout=edit&id='.(int)$r->product_id,false)); ?>"><?php echo $e($r->product_title); ?></a></p>
  <div class="row g-3">
   <div class="col-12 col-md-6">
    <label for="mm-review-author-edit" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_AUTHOR'); ?></label>
    <input id="mm-review-author-edit" name="author_name" class="form-control" required minlength="2" maxlength="120" value="<?php echo $e($r->author_name); ?>" <?php echo $this->canEdit?'':'disabled'; ?>>
   </div>
   <div class="col-12 col-md-6">
    <label for="mm-review-rating-edit" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_RATING'); ?></label>
    <select id="mm-review-rating-edit" class="form-select" name="rating" <?php echo $this->canEdit?'':'disabled'; ?>>
     <?php foreach([5,4,3,2,1] as $rating): ?>
      <option value="<?php echo $rating; ?>"<?php echo (int)$r->rating===$rating?' selected':''; ?>><?php echo $rating; ?> / 5</option>
     <?php endforeach; ?>
    </select>
   </div>
   <div class="col-12 col-md-6">
    <label for="mm-review-title-edit" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_HEADLINE'); ?></label>
    <input id="mm-review-title-edit" name="title" class="form-control" maxlength="140" value="<?php echo $e($r->title); ?>" <?php echo $this->canEdit?'':'disabled'; ?>>
   </div>
   <div class="col-12 col-md-6">
    <label for="mm-review-state-edit" class="form-label"><?php echo Text::_('JSTATUS'); ?></label>
    <select id="mm-review-state-edit" name="state" class="form-select" <?php echo $this->canModerate?'':'disabled'; ?>>
     <?php foreach([0=>'COM_MINIMARKET_REVIEWS_PENDING',1=>'COM_MINIMARKET_REVIEWS_PUBLISHED',2=>'COM_MINIMARKET_REVIEWS_REJECTED'] as $state=>$label): ?>
     <option value="<?php echo $state; ?>"<?php echo (int)$r->state===$state?' selected':''; ?>><?php echo Text::_($label); ?></option>
     <?php endforeach; ?>
    </select>
   </div>
   <div class="col-12">
    <label for="mm-review-text-edit" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_TEXT'); ?></label>
    <textarea id="mm-review-text-edit" name="body" class="form-control" rows="5" required minlength="10" maxlength="5000" <?php echo $this->canEdit?'':'disabled'; ?>><?php echo $e($r->body); ?></textarea>
   </div>
   <div class="col-12 col-md-6">
    <label for="mm-review-photo-edit" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_IMAGE'); ?></label>
    <input id="mm-review-photo-edit" name="photo_path" class="form-control" maxlength="1024" value="<?php echo $e($r->photo_path); ?>" <?php echo $this->canEdit?'':'disabled'; ?>>
   </div>
   <div class="col-12 col-md-6">
    <label for="mm-review-video-edit" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_VIDEO'); ?></label>
    <input id="mm-review-video-edit" name="video_url" class="form-control" maxlength="1024" value="<?php echo $e($r->video_url); ?>" <?php echo $this->canEdit?'':'disabled'; ?>>
   </div>
  </div>
  <?php echo HTMLHelper::_('form.token'); ?>
 </form>
</div>
