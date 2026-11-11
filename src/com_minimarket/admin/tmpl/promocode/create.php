<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$p=$this->promo;
$v=static fn(string $name,string $fallback='') => $p !== null ? (string)($p->$name ?? $fallback) : $fallback;
$money=static fn(string $name,string $fallback='0.00') => $p!==null
    ? number_format((int)($p->$name ?? 0)/100,2,'.','') : $fallback;
$date=static fn(string $name) => $p!==null && !empty($p->$name)
    ? str_replace(' ','T',substr((string)$p->$name,0,16)) : '';
$ids=$p ? json_decode((string)($p->target_ids_json??'[]'),true) : [];
$targets=is_array($ids)?implode(', ',array_map('intval',$ids)):'';
?>
<form id="mm-promo-form" method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=promocode.save',false)); ?>">
 <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <button type="submit" class="btn btn-success" name="save_close" value="0"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
  <button type="submit" class="btn btn-success" name="save_close" value="1"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
  <a class="btn btn-danger" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=promocodes',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
 </div>
 <input type="hidden" name="id" value="<?php echo (int)($p->id??0); ?>">
 <?php echo HTMLHelper::_('form.token'); ?>
 <div class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_HELP'); ?></div>
 <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-promocode', ['active' => 'general']); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-promocode', 'general', Text::_('COM_MINIMARKET_PROMO_TAB_GENERAL')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <div class="col-md-6"><label for="mm-code" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_CODE'); ?> *</label>
      <input id="mm-code" name="code" class="form-control" required pattern="[A-Za-z0-9-]{3,64}" maxlength="64" value="<?php echo $this->escape($v('code')); ?>"></div>
     <div class="col-md-6"><label for="mm-kind" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_DISCOUNT_TYPE'); ?></label>
      <select id="mm-kind" name="discount_type" class="form-select">
       <option value="fixed"<?php echo $v('discount_type','fixed')==='fixed'?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_PROMO_FIXED'); ?></option>
       <option value="percent"<?php echo $v('discount_type')==='percent'?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_PROMO_PERCENT'); ?></option>
      </select></div>
     <div class="col-md-6" data-promo-fixed><label for="mm-amount" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMOCODES_AMOUNT'); ?> *</label>
      <input id="mm-amount" name="amount" class="form-control" type="number" min="0.01" step="0.01" max="99999999.99" value="<?php echo $this->escape($money('amount_cents')); ?>"></div>
     <div class="col-md-6" data-promo-percent><label for="mm-percent" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_PERCENT'); ?> *</label>
      <input id="mm-percent" name="percent_value" class="form-control" type="number" min="0.01" max="100" step="0.01" value="<?php echo $this->escape($v('percent_value','10')); ?>"></div>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-promocode', 'conditions', Text::_('COM_MINIMARKET_PROMO_TAB_CONDITIONS')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <div class="col-md-6"><label for="mm-min-cart" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_MIN_CART'); ?></label>
      <input id="mm-min-cart" name="min_cart" type="number" min="0" step="0.01" class="form-control" value="<?php echo $this->escape($money('min_cart_cents')); ?>"></div>
     <div class="col-md-6"><label for="mm-scope" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_SCOPE'); ?></label>
      <select id="mm-scope" name="applies_to" class="form-select">
       <?php foreach(['all','category','product'] as $scope): ?><option value="<?php echo $scope; ?>"<?php echo $scope===$v('applies_to','all')?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_PROMO_SCOPE_'.strtoupper($scope)); ?></option><?php endforeach; ?>
      </select></div>
     <?php foreach(['category'=>'categories','product'=>'search'] as $scope=>$endpoint):
       $selected=[];
       if($v('applies_to','all')===$scope && is_array($ids))
           foreach($ids as $id)if((int)$id>0)$selected[(int)$id]='#'.(int)$id;
       $mmReference=[
         'title'=>Text::_('COM_MINIMARKET_PROMO_SCOPE_'.strtoupper($scope)),
         'input'=>'target_ids_'.$scope,'endpoint'=>$endpoint,'multiple'=>true,
         'selected'=>$selected,'placeholder'=>Text::_('COM_MINIMARKET_REF_SEARCH')
       ];
     ?>
      <div class="col-12" data-promo-targets="<?php echo $scope; ?>">
       <?php require dirname(__DIR__).'/shared/reference-picker.php'; ?>
      </div>
     <?php endforeach; ?>
     <div class="col-12"><input type="hidden" name="combinable" value="0">
      <label class="form-check"><input type="checkbox" class="form-check-input" name="combinable" value="1"<?php echo (int)$v('combinable')===1?' checked':''; ?>>
       <span class="form-check-label"><?php echo Text::_('COM_MINIMARKET_PROMO_COMBINABLE'); ?></span></label></div>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-promocode', 'limits', Text::_('COM_MINIMARKET_PROMO_TAB_LIMITS')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <div class="col-md-6"><label for="mm-start" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_START'); ?> (UTC)</label>
      <input id="mm-start" type="datetime-local" name="starts_at" class="form-control" value="<?php echo $this->escape($date('starts_at')); ?>"></div>
     <div class="col-md-6"><label for="mm-end" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_END'); ?> (UTC)</label>
      <input id="mm-end" type="datetime-local" name="ends_at" class="form-control" value="<?php echo $this->escape($date('ends_at')); ?>"></div>
     <div class="col-md-6"><label for="mm-limit" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_USAGE_LIMIT'); ?></label>
      <input id="mm-limit" name="usage_limit" class="form-control" type="number" min="1" step="1" placeholder="∞" value="<?php echo $this->escape($v('usage_limit')); ?>"></div>
     <div class="col-md-6"><label for="mm-limit-customer" class="form-label"><?php echo Text::_('COM_MINIMARKET_PROMO_CUSTOMER_LIMIT'); ?></label>
      <input id="mm-limit-customer" name="per_customer_limit" class="form-control" type="number" min="1" step="1" placeholder="∞" value="<?php echo $this->escape($v('per_customer_limit')); ?>"></div>
     <div class="col-12"><div class="alert alert-secondary"><?php echo Text::_('COM_MINIMARKET_PROMO_CHECKOUT_INFO'); ?></div></div>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</form>
<script>
(() => {
 const kind=document.getElementById('mm-kind'),scope=document.getElementById('mm-scope');
 const refresh=()=>{
  const fixed=kind.value==='fixed';
  for(const field of document.querySelectorAll('[data-promo-fixed]'))field.hidden=!fixed;
  for(const field of document.querySelectorAll('[data-promo-percent]'))field.hidden=fixed;
  document.getElementById('mm-amount').required=fixed;
  document.getElementById('mm-percent').required=!fixed;
  for(const panel of document.querySelectorAll('[data-promo-targets]')){
   panel.hidden=panel.dataset.promoTargets!==scope.value;
  }
 };
 kind.addEventListener('change',refresh);scope.addEventListener('change',refresh);refresh();
})();
</script>
