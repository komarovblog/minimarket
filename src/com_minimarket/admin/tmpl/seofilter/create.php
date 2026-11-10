<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section class="mm-admin-panel card card-body">
 <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_CREATE'); ?></h2>
 <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=seofilter.create',false)); ?>" class="row g-3">
  <div class="col-12">
   <label for="mm-seo-category" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_CATEGORY'); ?> *</label>
   <select id="mm-seo-category" name="category_id" class="form-select" required>
    <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
    <?php foreach($this->categories as $category): ?><option value="<?php echo (int)$category->id; ?>"><?php echo $this->escape($category->title); ?> (#<?php echo (int)$category->id; ?>)</option><?php endforeach; ?>
   </select>
  </div>
  <div class="col-12 col-md-6">
   <label for="mm-seo-alias" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_ALIAS'); ?> *</label>
   <input id="mm-seo-alias" class="form-control" name="alias" required maxlength="190" pattern="[a-z0-9][a-z0-9-]*" placeholder="winter-red-shoes">
  </div>
  <div class="col-12 col-md-6">
   <label for="mm-seo-title" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_TITLE'); ?> *</label>
   <input id="mm-seo-title" class="form-control" name="title" required maxlength="255">
  </div>
  <div class="col-12">
   <label for="mm-seo-heading" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_HEADING'); ?> *</label>
   <input id="mm-seo-heading" class="form-control" name="heading" required maxlength="255">
  </div>
  <div class="col-12">
   <label for="mm-seo-query" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_QUERY'); ?> *</label>
   <textarea id="mm-seo-query" class="form-control" name="filter_query" required maxlength="4000" rows="3"></textarea>
   <div class="form-text"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_QUERY_DESC'); ?></div>
  </div>
  <div class="col-12">
   <label for="mm-seo-intro" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_INTRO'); ?></label>
   <textarea id="mm-seo-intro" class="form-control" name="introtext" rows="3" maxlength="10000"></textarea>
  </div>
  <div class="col-12">
   <label for="mm-seo-description" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_DESCRIPTION'); ?></label>
   <textarea id="mm-seo-description" class="form-control" name="description" rows="6" maxlength="20000"></textarea>
  </div>
  <div class="col-12">
   <label for="mm-seo-meta" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_META_DESC'); ?></label>
   <textarea id="mm-seo-meta" class="form-control" name="meta_description" rows="2" maxlength="320"></textarea>
  </div>
  <div class="col-12 col-md-6">
   <label for="mm-seo-robots" class="form-label"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_ROBOTS'); ?></label>
   <select id="mm-seo-robots" class="form-select" name="meta_robots">
    <option value="index,follow">index, follow</option>
    <option value="noindex,follow">noindex, follow</option>
    <option value="noindex,nofollow">noindex, nofollow</option>
   </select>
  </div>
  <div class="col-12">
   <?php echo HTMLHelper::_('form.token'); ?>
   <button type="submit" class="btn btn-primary"><?php echo Text::_('JSAVE'); ?></button>
   <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=seofilters',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
  </div>
 </form>
</section>
