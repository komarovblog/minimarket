<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="d-flex gap-2 flex-wrap mb-3">
 <button type="submit" form="mm-review-form" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
 <a class="btn btn-danger" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=reviews',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<section class="mm-admin-panel card card-body">
 <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_REVIEW_NEW'); ?></h2>
 <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=review.create',false)); ?>" id="mm-review-form" class="row g-3">
  <div class="col-12">
   <?php $mmReference=[
     'title'=>Text::_('COM_MINIMARKET_REVIEW_PRODUCT'),
     'endpoint'=>'search','input'=>'product_id','selected'=>[],
     'placeholder'=>Text::_('COM_MINIMARKET_PRODUCT_SEARCH'),
     'multiple'=>false,
   ]; require dirname(__DIR__).'/shared/reference-picker.php'; ?>
  </div>
  <div class="col-12 col-md-6">
   <label for="mm-review-name" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_AUTHOR'); ?> *</label>
   <input id="mm-review-name" name="author_name" class="form-control" required minlength="2" maxlength="120">
  </div>
  <div class="col-12 col-md-6">
   <label for="mm-review-rating" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_RATING'); ?> *</label>
   <select id="mm-review-rating" class="form-select" name="rating" required><?php foreach([5,4,3,2,1] as $n): ?><option value="<?php echo $n; ?>"><?php echo $n; ?> / 5</option><?php endforeach; ?></select>
  </div>
  <div class="col-12">
   <label for="mm-review-title" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_HEADLINE'); ?></label>
   <input id="mm-review-title" class="form-control" name="title" maxlength="140">
  </div>
  <div class="col-12">
   <label for="mm-review-body" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_TEXT'); ?> *</label>
   <textarea id="mm-review-body" class="form-control" name="body" required minlength="10" maxlength="5000" rows="5"></textarea>
  </div>
  <div class="col-12 col-md-6">
   <label for="mm-review-photo" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_IMAGE'); ?></label>
   <input id="mm-review-photo" class="form-control" name="photo_path" maxlength="1024" placeholder="images/reviews/photo.webp">
  </div>
  <div class="col-12 col-md-6">
   <label for="mm-review-video" class="form-label"><?php echo Text::_('COM_MINIMARKET_REVIEW_VIDEO'); ?></label>
   <input id="mm-review-video" class="form-control" name="video_url" maxlength="1024" placeholder="https://www.youtube.com/watch?v=...">
  </div>
  <div class="col-12">
   <?php echo HTMLHelper::_('form.token'); ?>
   <button type="submit" class="btn btn-primary"><?php echo Text::_('JSAVE'); ?></button>
   <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=reviews',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
  </div>
 </form>
</section>
