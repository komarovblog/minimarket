<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section class="mm-admin-panel mm-admin-list-panel">
 <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_NEW'); ?></h2>
 <div class="alert alert-info" role="note"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_HELP'); ?></div>
 <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=promocode.create',false)); ?>" class="row g-3">
  <div class="col-12 col-md-6"><label class="form-label" for="mm-promo-code"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_CODE'); ?> *</label>
   <input id="mm-promo-code" name="code" required pattern="[A-Za-z0-9-]{3,64}" maxlength="64" class="form-control"></div>
  <div class="col-12 col-md-6"><label class="form-label" for="mm-promo-value"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_AMOUNT'); ?> *</label>
   <input id="mm-promo-value" name="amount" type="number" min="0.01" max="99999999.99" step="0.01" required class="form-control"></div>
  <div class="col-12">
   <?php echo HTMLHelper::_('form.token'); ?>
   <button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button>
   <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=promocodes',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
  </div>
 </form>
</section>
