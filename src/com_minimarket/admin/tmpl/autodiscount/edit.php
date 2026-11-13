<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$r=$this->rule;
$v=static fn(string $key,string $default='')=>(string)($r->$key??$default);
$date=static fn($key)=>$r!==null&&!empty($r->$key)
    ?str_replace(' ','T',substr((string)$r->$key,0,16)):'';
$amount=$r!==null?intdiv((int)$r->amount_cents,100).'.'.str_pad((string)((int)$r->amount_cents%100),2,'0',STR_PAD_LEFT):'0.00';
$percent=$r!==null?intdiv((int)$r->basis_points,100).'.'.str_pad((string)((int)$r->basis_points%100),2,'0',STR_PAD_LEFT):'10.00';
?>
<form id="mm-auto-discount-form" method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=autodiscount.save',false)); ?>">
<div class="btn-toolbar d-flex gap-2 flex-wrap mb-3">
 <button type="submit" class="btn btn-success" name="save_close" value="0"><?php echo Text::_('JSAVE'); ?></button>
 <button type="submit" class="btn btn-success" name="save_close" value="1"><?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
 <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=autodiscounts',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<input type="hidden" name="id" value="<?php echo (int)($r->id??0); ?>">
<?php echo HTMLHelper::_('form.token'); ?>
<?php echo HTMLHelper::_('uitab.startTabSet','mm-auto-discount',['active'=>'general']); ?>
<?php echo HTMLHelper::_('uitab.addTab','mm-auto-discount','general',Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
<div class="card card-body mm-admin-panel"><div class="row g-3">
 <div class="col-md-6"><label class="form-label" for="mm-auto-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label>
  <input id="mm-auto-title" name="title" class="form-control" maxlength="160" required value="<?php echo $this->escape($v('title')); ?>"></div>
 <div class="col-md-6"><label class="form-label" for="mm-auto-kind"><?php echo Text::_('COM_MINIMARKET_PROMO_DISCOUNT_TYPE'); ?></label>
  <select id="mm-auto-kind" name="discount_type" class="form-select">
   <?php foreach(['percent','fixed'] as $kind): ?><option value="<?php echo $kind; ?>"<?php echo $v('discount_type','percent')===$kind?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_PROMO_'.strtoupper($kind)); ?></option><?php endforeach; ?>
  </select></div>
 <div class="col-md-6" data-mm-auto-percent><label class="form-label" for="mm-auto-percent"><?php echo Text::_('COM_MINIMARKET_PROMO_PERCENT'); ?></label>
  <input id="mm-auto-percent" name="percent" class="form-control" type="number" step="0.01" min="0.01" max="100" value="<?php echo $percent; ?>"></div>
 <div class="col-md-6" data-mm-auto-fixed><label class="form-label" for="mm-auto-amount"><?php echo Text::_('COM_MINIMARKET_AUTO_DISCOUNT_AMOUNT'); ?></label>
  <input id="mm-auto-amount" name="amount" class="form-control" type="number" step="0.01" min="0.01" value="<?php echo $amount; ?>"></div>
</div></div>
<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php echo HTMLHelper::_('uitab.addTab','mm-auto-discount','conditions',Text::_('COM_MINIMARKET_PROMO_TAB_CONDITIONS')); ?>
<div class="card card-body mm-admin-panel">
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_AUTO_DISCOUNT_HELP'); ?></p>
 <label class="form-label" for="mm-auto-scope"><?php echo Text::_('COM_MINIMARKET_AUTO_DISCOUNT_SCOPE'); ?></label>
 <select id="mm-auto-scope" class="form-select" name="applies_to">
  <?php foreach(['all','product','category','brand'] as $scope): ?>
   <option value="<?php echo $scope; ?>"<?php echo $v('applies_to','all')===$scope?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_AUTO_SCOPE_'.strtoupper($scope)); ?></option>
  <?php endforeach; ?>
 </select>
 <?php foreach(['product'=>'search','category'=>'categories','brand'=>'brands'] as $scope=>$endpoint): ?>
  <div class="mt-3" data-mm-auto-scope="<?php echo $scope; ?>" hidden>
   <?php
   $mmReference=['input'=>$scope.'_ids','endpoint'=>$endpoint,'multiple'=>true,
      'selected'=>$v('applies_to','all')===$scope?$this->selected:[],
      'title'=>Text::_('COM_MINIMARKET_AUTO_SCOPE_'.strtoupper($scope))];
   require dirname(__DIR__).'/shared/reference-picker.php';
   ?>
  </div>
 <?php endforeach; ?>
</div>
<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php echo HTMLHelper::_('uitab.addTab','mm-auto-discount','limits',Text::_('COM_MINIMARKET_PROMO_TAB_LIMITS')); ?>
<div class="card card-body mm-admin-panel row g-3">
 <div class="col-md-6"><label class="form-label" for="mm-auto-start"><?php echo Text::_('COM_MINIMARKET_PROMO_START'); ?> (UTC)</label>
  <input id="mm-auto-start" name="starts_at" class="form-control" type="datetime-local" value="<?php echo $this->escape($date('starts_at')); ?>"></div>
 <div class="col-md-6"><label class="form-label" for="mm-auto-end"><?php echo Text::_('COM_MINIMARKET_PROMO_END'); ?> (UTC)</label>
  <input id="mm-auto-end" name="ends_at" class="form-control" type="datetime-local" value="<?php echo $this->escape($date('ends_at')); ?>"></div>
</div>
<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php echo HTMLHelper::_('uitab.addTab','mm-auto-discount','publishing',Text::_('COM_MINIMARKET_CATEGORY_TAB_PUBLISHING')); ?>
<div class="card card-body mm-admin-panel">
 <?php if($this->canPublish): ?>
 <label class="form-label" for="mm-auto-state"><?php echo Text::_('JSTATUS'); ?></label>
 <select id="mm-auto-state" name="state" class="form-select">
  <option value="0"<?php echo (int)$v('state')===0?' selected':''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
  <option value="1"<?php echo (int)$v('state')===1?' selected':''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
 </select>
 <?php else: ?><p class="form-text"><?php echo Text::_('JERROR_ALERTNOAUTHOR'); ?></p><?php endif; ?>
</div>
<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</form>
<script>
(()=>{const kind=document.getElementById('mm-auto-kind'),scope=document.getElementById('mm-auto-scope');
 const sync=()=>{const isFixed=kind.value==='fixed';
  const percent=document.getElementById('mm-auto-percent'),amount=document.getElementById('mm-auto-amount');
  for(const el of document.querySelectorAll('[data-mm-auto-percent]'))el.hidden=isFixed;
  for(const el of document.querySelectorAll('[data-mm-auto-fixed]'))el.hidden=!isFixed;
  percent.required=!isFixed;amount.required=isFixed;
  for(const el of document.querySelectorAll('[data-mm-auto-scope]'))el.hidden=el.dataset.mmAutoScope!==scope.value;
 };
 kind.addEventListener('change',sync);scope.addEventListener('change',sync);sync();
})();
</script>
