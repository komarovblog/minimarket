<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section class="mm-admin-panel mm-admin-list-panel">
 <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
  <h2 class="h4 mb-0"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_TITLE'); ?></h2>
  <?php if($this->canCreate): ?>
   <a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=promocode&layout=create',false)); ?>"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_PROMOCODES_NEW'); ?></a>
  <?php endif; ?>
 </div>
 <div class="alert alert-info" role="note"><span class="icon-info-circle" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_PROMOCODES_HELP'); ?></div>
 <div class="table-responsive"><table class="table table-striped mm-admin-list">
  <thead><tr><th>ID</th><th><?php echo Text::_('COM_MINIMARKET_PROMOCODES_CODE'); ?></th><th><?php echo Text::_('COM_MINIMARKET_PROMOCODES_AMOUNT'); ?></th><th><?php echo Text::_('COM_MINIMARKET_PROMOCODES_STATUS'); ?></th></tr></thead>
  <tbody>
  <?php foreach($this->codes as $code): ?>
   <tr>
    <td>#<?php echo (int)$code->id; ?></td>
    <td><code><?php echo $this->escape((string)$code->code); ?></code></td>
    <td><?php echo number_format((int)$code->amount_cents/100,2,'.',' '); ?> ₽</td>
    <td>
     <?php if($this->canUpdate): ?>
      <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=promocode.state',false)); ?>" class="d-flex gap-2 align-items-center">
       <input type="hidden" name="id" value="<?php echo (int)$code->id; ?>">
       <select class="form-select form-select-sm" name="state">
        <option value="0"<?php echo (int)$code->state===0?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_PROMOCODES_INACTIVE'); ?></option>
        <option value="1"<?php echo (int)$code->state===1?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_PROMOCODES_ACTIVE'); ?></option>
       </select>
       <?php echo HTMLHelper::_('form.token'); ?><button type="submit" class="btn btn-outline-primary btn-sm"><?php echo Text::_('JSAVE'); ?></button>
      </form>
     <?php else: ?>
      <?php echo Text::_((int)$code->state===1?'COM_MINIMARKET_PROMOCODES_ACTIVE':'COM_MINIMARKET_PROMOCODES_INACTIVE'); ?>
     <?php endif; ?>
    </td>
   </tr>
  <?php endforeach; ?>
  <?php if(!$this->codes): ?><tr><td colspan="4"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_EMPTY'); ?></td></tr><?php endif; ?>
  </tbody></table></div>
</section>
