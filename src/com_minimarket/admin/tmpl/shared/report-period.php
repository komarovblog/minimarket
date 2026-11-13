<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form method="get" class="row g-3 mb-3 align-items-end" action="<?php echo $this->escape(Route::_('index.php',false)); ?>">
 <input type="hidden" name="option" value="com_minimarket">
 <input type="hidden" name="view" value="<?php echo $this->escape($mmReportView); ?>">
 <div class="col-sm-4 col-lg-3">
  <label class="form-label" for="mm-report-from"><?php echo Text::_('COM_MINIMARKET_REPORT_FROM'); ?></label>
  <input class="form-control" type="date" id="mm-report-from" name="from" value="<?php echo $this->escape($this->from); ?>" required>
 </div>
 <div class="col-sm-4 col-lg-3">
  <label class="form-label" for="mm-report-to"><?php echo Text::_('COM_MINIMARKET_REPORT_TO'); ?></label>
  <input class="form-control" type="date" id="mm-report-to" name="to" value="<?php echo $this->escape($this->to); ?>" required>
 </div>
 <div class="col-sm-4 col-lg-2">
  <button class="btn btn-primary" type="submit"><?php echo Text::_('JFILTER'); ?></button>
 </div>
</form>
<p class="form-text"><?php echo Text::_('COM_MINIMARKET_REPORT_SCOPE'); ?></p>