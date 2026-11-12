<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$z=$this->zone;
$embedded=!empty($this->embeddedZoneEditor);
$back=$embedded
 ?'index.php?option=com_minimarket&view=deliverymethods&method=courier&tab=zones'
 :'index.php?option=com_minimarket&view=deliveryzones';
$get=static fn($key,$default='')=>$z?(string)($z->$key??$default):$default;
$money=static fn($key)=>$z?number_format((int)($z->$key??0)/100,2,'.',''):'0.00';
?>
<div class="btn-toolbar d-flex gap-2 flex-wrap mb-3" role="toolbar">
 <button type="submit" form="mm-zone-form" name="save_close" value="0" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
 <button type="submit" form="mm-zone-form" name="save_close" value="1" class="btn btn-success"><?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
 <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_($back,false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<form id="mm-zone-form" method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=deliveryzone.save',false)); ?>">
 <input type="hidden" name="id" value="<?php echo (int)($z->id??0); ?>">
 <?php if($embedded): ?><input type="hidden" name="return_to" value="deliverymethods"><?php endif; ?>
 <?php echo HTMLHelper::_('form.token'); ?>
 <?php echo HTMLHelper::_('uitab.startTabSet', 'minimarket-deliveryzone', ['active' => 'basic']); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-deliveryzone', 'basic', Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <div class="col-md-6"><label for="z-title" class="form-label"><?php echo Text::_('JGLOBAL_TITLE'); ?> *</label>
      <input id="z-title" name="title" maxlength="160" class="form-control" required value="<?php echo $this->escape($get('title')); ?>"></div>
     <div class="col-md-3"><label for="z-country" class="form-label"><?php echo Text::_('COM_MINIMARKET_ZONE_COUNTRY'); ?></label>
      <input id="z-country" class="form-control" name="country_code" maxlength="2" pattern="[A-Za-z]{2}" required value="<?php echo $this->escape($get('country_code','RU')); ?>"></div>
     <div class="col-md-3"><label for="z-region" class="form-label"><?php echo Text::_('COM_MINIMARKET_ZONE_REGION'); ?></label>
      <input id="z-region" class="form-control" name="region" maxlength="120" value="<?php echo $this->escape($get('region')); ?>"></div>
     <div class="col-md-6"><label for="z-cities" class="form-label"><?php echo Text::_('COM_MINIMARKET_ZONE_CITIES'); ?> *</label>
      <textarea id="z-cities" name="cities" class="form-control" rows="5" required><?php echo $this->escape($get('cities')); ?></textarea>
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_ZONE_CITIES_HELP'); ?></p></div>
     <div class="col-md-6"><label for="z-postcodes" class="form-label"><?php echo Text::_('COM_MINIMARKET_ZONE_POSTCODES'); ?></label>
      <textarea id="z-postcodes" name="postcode_prefixes" class="form-control" rows="5"><?php echo $this->escape($get('postcode_prefixes')); ?></textarea>
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_ZONE_POSTCODES_HELP'); ?></p></div>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-deliveryzone', 'tariff', Text::_('COM_MINIMARKET_ZONE_TAB_TARIFF')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <div class="col-md-6"><label for="z-mode" class="form-label"><?php echo Text::_('COM_MINIMARKET_ZONE_RATE_MODE'); ?></label>
      <select id="z-mode" name="rate_mode" class="form-select">
       <?php foreach(['fixed','per_kg','per_liter'] as $mode): ?>
        <option value="<?php echo $mode; ?>"<?php echo $get('rate_mode','fixed')===$mode?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_ZONE_RATE_'.strtoupper($mode)); ?></option>
       <?php endforeach; ?>
      </select></div>
     <?php foreach([
      'base_fee_cents'=>'COM_MINIMARKET_ZONE_BASE_FEE','rate_per_unit_cents'=>'COM_MINIMARKET_ZONE_UNIT_FEE',
      'free_from_cents'=>'COM_MINIMARKET_ZONE_FREE_FROM','min_cart_cents'=>'COM_MINIMARKET_ZONE_MIN_CART'
      ] as $key=>$label): $name=str_replace('_cents','',$key); ?>
      <div class="col-md-6"><label class="form-label" for="z-<?php echo $name; ?>"><?php echo Text::_($label); ?></label>
       <input id="z-<?php echo $name; ?>" class="form-control" name="<?php echo $name; ?>" type="number"
              min="0" step="0.01" value="<?php echo $this->escape($money($key)); ?>"></div>
     <?php endforeach; ?>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'minimarket-deliveryzone', 'limits', Text::_('COM_MINIMARKET_ZONE_TAB_LIMITS')); ?>
 <div class="card card-body mm-admin-panel">
    <div class="row g-3">
     <?php foreach([
       'max_weight_grams'=>'COM_MINIMARKET_ZONE_MAX_WEIGHT',
       'max_volume_cm3'=>'COM_MINIMARKET_ZONE_MAX_VOLUME',
       'estimated_days_min'=>'COM_MINIMARKET_ZONE_DAYS_MIN',
       'estimated_days_max'=>'COM_MINIMARKET_ZONE_DAYS_MAX',
       'priority'=>'COM_MINIMARKET_ZONE_PRIORITY'] as $key=>$label): ?>
      <div class="col-md-6"><label for="z-<?php echo $key; ?>" class="form-label"><?php echo Text::_($label); ?></label>
       <input id="z-<?php echo $key; ?>" name="<?php echo $key; ?>" type="number" min="0" step="1"
              class="form-control" value="<?php echo $this->escape($get($key,'0')); ?>"></div>
     <?php endforeach; ?>
     <div class="col-12"><input type="hidden" name="state" value="0">
      <label class="form-check"><input class="form-check-input" type="checkbox" name="state" value="1"<?php echo (int)$get('state')===1?' checked':''; ?>>
       <span class="form-check-label"><?php echo Text::_('JPUBLISHED'); ?></span></label></div>
    </div>
   </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</form>