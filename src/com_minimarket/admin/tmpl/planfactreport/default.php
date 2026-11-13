<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$money=static fn(int $cents):string=>number_format($cents/100,2,',',' ').' ₽';
$exact=static fn(int $cents):string=>intdiv($cents,100).'.'
    .str_pad((string)($cents%100),2,'0',STR_PAD_LEFT);
?>
<div id="j-main-container" class="j-main-container">
 <form class="card card-body mm-admin-panel mb-3" method="get"
  action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=planfactreport',false)); ?>">
  <input type="hidden" name="option" value="com_minimarket">
  <input type="hidden" name="view" value="planfactreport">
  <div class="row g-3 align-items-end">
   <div class="col-md-3">
    <label class="form-label" for="mm-plan-month"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_PERIOD'); ?></label>
    <select id="mm-plan-month" name="month" class="form-select">
     <?php for($i=1;$i<=12;$i++): ?>
      <option value="<?php echo $i; ?>" <?php echo $i===$this->month?'selected':''; ?>>
       <?php echo sprintf('%02d',$i); ?>
      </option>
     <?php endfor; ?>
    </select>
   </div>
   <div class="col-md-3">
    <label class="form-label" for="mm-plan-year"><?php echo Text::_('JYEAR'); ?></label>
    <input id="mm-plan-year" name="year" type="number" class="form-control"
     min="2020" max="2100" value="<?php echo $this->year; ?>" required>
   </div>
   <div class="col-md-3">
    <button class="btn btn-outline-secondary" type="submit"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_GO'); ?></button>
   </div>
  </div>
 </form>
 <section class="card card-body mm-admin-panel mb-3">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_TITLE'); ?> —
   <?php echo sprintf('%02d.%04d',$this->month,$this->year); ?></h2>
  <dl class="row mb-0">
   <dt class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_PLAN'); ?></dt>
   <dd class="col-sm-5"><?php echo $this->targetCents===null
    ?Text::_('COM_MINIMARKET_PLANFACT_REPORT_NO_PLAN'):$money($this->targetCents); ?></dd>
   <dt class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_ACTUAL'); ?></dt>
   <dd class="col-sm-5"><?php echo $money($this->actualCents); ?></dd>
   <dt class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_ORDERS'); ?></dt>
   <dd class="col-sm-5"><?php echo $this->orders; ?></dd>
   <?php if($this->targetCents!==null): ?>
    <dt class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_DELTA'); ?></dt>
    <dd class="col-sm-5"><?php echo $money($this->actualCents-$this->targetCents); ?></dd>
    <dt class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_PROGRESS'); ?></dt>
    <dd class="col-sm-5"><?php echo $this->percentTenths===null?'—'
     :intdiv($this->percentTenths,10).','.($this->percentTenths%10).'%'; ?></dd>
   <?php endif; ?>
  </dl>
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_NOTE'); ?></p>
 </section>
 <section class="card card-body mm-admin-panel mb-3">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_SAVE'); ?></h2>
  <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=planfactreport.save',false)); ?>">
   <input name="year" type="hidden" value="<?php echo $this->year; ?>">
   <input name="month" type="hidden" value="<?php echo $this->month; ?>">
   <div class="row g-3 align-items-end">
    <div class="col-sm-6">
     <label class="form-label" for="mm-plan-target"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_PLAN'); ?> (₽)</label>
     <input class="form-control" id="mm-plan-target" name="target" inputmode="decimal"
      type="number" min="0.01" max="999999999999.99" step="0.01"
      value="<?php echo $this->targetCents===null?'':$exact($this->targetCents); ?>" required>
    </div>
    <div class="col-sm-6">
     <button class="btn btn-success" type="submit"><?php echo Text::_('JSAVE'); ?></button>
    </div>
   </div>
   <?php echo HTMLHelper::_('form.token'); ?>
  </form>
 </section>
 <section class="card card-body mm-admin-panel">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_HISTORY'); ?></h2>
  <?php if($this->history): ?>
   <div class="table-responsive mm-native-list-viewport"><table class="table table-striped">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_UTC'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_PREVIOUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_NEW'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_ACTOR'); ?></th>
    </tr></thead>
    <tbody><?php foreach($this->history as $entry): ?>
     <tr>
      <td><?php echo $this->escape((string)$entry->created_at); ?></td>
      <td><?php echo $entry->previous_target_cents===null?'—':$money((int)$entry->previous_target_cents); ?></td>
      <td><?php echo $money((int)$entry->target_goods_cents); ?></td>
      <td>#<?php echo (int)$entry->actor_user_id; ?></td>
     </tr>
    <?php endforeach; ?></tbody>
   </table></div>
  <?php else: ?>
   <p class="form-text"><?php echo Text::_('COM_MINIMARKET_PLANFACT_REPORT_NO_PLAN'); ?></p>
  <?php endif; ?>
 </section>
</div>
