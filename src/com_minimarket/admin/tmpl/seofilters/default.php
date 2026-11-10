<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Site\Helper\RouteHelper;
?>
<section class="mm-admin-panel card card-body">
 <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
  <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_SEOFILTERS_TITLE'); ?></h2>
  <?php if($this->canCreate): ?><a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=seofilter&layout=create',false)); ?>"><span class="icon-plus"></span> <?php echo Text::_('COM_MINIMARKET_SEOFILTER_CREATE'); ?></a><?php endif; ?>
 </div>
 <div class="table-responsive">
 <table class="table table-striped mm-admin-list">
  <thead><tr><th>ID</th><th><?php echo Text::_('COM_MINIMARKET_SEOFILTER_TITLE'); ?></th><th><?php echo Text::_('COM_MINIMARKET_SEOFILTER_CATEGORY'); ?></th><th><?php echo Text::_('COM_MINIMARKET_SEOFILTER_ALIAS'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th></tr></thead>
  <tbody>
  <?php foreach($this->pages as $row): ?>
   <tr>
    <td><?php echo (int)$row->id; ?></td>
    <td><?php echo $this->escape($row->title); ?></td>
    <td><?php echo $this->escape($row->category_title); ?></td>
    <td><a href="<?php echo $this->escape(RouteHelper::seoFilter((int)$row->id)); ?>" target="_blank" rel="noopener noreferrer">/seo/<?php echo $this->escape($row->alias); ?>-<?php echo (int)$row->id; ?></a></td>
    <td>
     <span class="badge bg-<?php echo (int)$row->state===1?'success':'secondary'; ?>"><?php echo Text::_((int)$row->state===1?'COM_MINIMARKET_SEOFILTER_PUBLISHED':'COM_MINIMARKET_SEOFILTER_UNPUBLISHED'); ?></span>
     <?php if($this->canPublish): ?>
      <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=seofilter.state',false)); ?>" class="d-flex align-items-center gap-1 mt-1">
       <input type="hidden" name="id" value="<?php echo (int)$row->id; ?>">
       <select class="form-select form-select-sm" name="state" aria-label="<?php echo Text::_('JSTATUS'); ?>">
        <option value="0"<?php echo (int)$row->state===0?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_SEOFILTER_UNPUBLISHED'); ?></option>
        <option value="1"<?php echo (int)$row->state===1?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_SEOFILTER_PUBLISHED'); ?></option>
       </select>
       <?php echo HTMLHelper::_('form.token'); ?><button type="submit" class="btn btn-outline-primary btn-sm"><?php echo Text::_('JSAVE'); ?></button>
      </form>
     <?php endif; ?>
    </td>
   </tr>
  <?php endforeach; ?>
  <?php if(!$this->pages): ?><tr><td colspan="5"><?php echo Text::_('COM_MINIMARKET_SEOFILTER_EMPTY'); ?></td></tr><?php endif; ?>
  </tbody>
 </table></div>
</section>
