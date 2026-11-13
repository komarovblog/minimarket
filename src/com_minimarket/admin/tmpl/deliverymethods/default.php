<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$p=$this->params;
$esc=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$bool=static fn($name,$default=0)=>(int)$p->get($name,$default)===1;
$edit=static fn($method)=>Route::_('index.php?option=com_minimarket&view=deliverymethods&method='.$method,false);
?>
<?php if($this->method===''):
 $mmSearch=[
  'view'=>'deliverymethods','query'=>$this->search,
  'searchLabel'=>Text::_('COM_MINIMARKET_METHOD_NAME'),
  'sort'=>$this->sort,'limit'=>$this->pageSize,
  'sortOptions'=>[
   'name_asc'=>Text::_('JGLOBAL_TITLE_ASC'),
   'name_desc'=>Text::_('JGLOBAL_TITLE_DESC')
  ],
  'filters'=>[
   ['name'=>'filter_type','label'=>Text::_('COM_MINIMARKET_METHOD_TYPE'),
    'value'=>$this->typeFilter,
    'options'=>[
     ''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
     'builtin'=>Text::_('COM_MINIMARKET_METHOD_BUILTIN'),
     'plugin'=>Text::_('COM_MINIMARKET_METHOD_PLUGIN')]],
   ['name'=>'filter_status','label'=>Text::_('JSTATUS'),
    'value'=>$this->statusFilter,'default'=>'all',
    'options'=>[
     'all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),
     'enabled'=>Text::_('COM_MINIMARKET_METHOD_ACTIVE'),
     'disabled'=>Text::_('COM_MINIMARKET_METHOD_INACTIVE')]]
  ]
 ];
 require dirname(__DIR__).'/shared/method-list.php';
?>
<?php elseif($this->pluginMethod!==null): ?>
 <?php $mmPluginScope='delivery';require dirname(__DIR__).'/shared/plugin-method.php'; ?>
<?php else: ?>
<div id="j-main-container" class="j-main-container">
 <?php $selectedTab=\Joomla\CMS\Factory::getApplication()->input->getCmd('tab','main');
 $selectedTab=($this->method==='courier'&&$this->embeddedZoneEditor)?'zones':(in_array($selectedTab,['main','zones','compatibility'],true)?$selectedTab:'main'); ?>
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-delivery-method',['active'=>$selectedTab]); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-delivery-method','main',Text::_('COM_MINIMARKET_ZONE_TAB_MAIN')); ?>
 <form method="post" id="mm-method-form" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=methodsettings.save',false)); ?>">
  <input type="hidden" name="scope" value="delivery">
  <input type="hidden" name="method" value="<?php echo $esc($this->method); ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <div class="btn-toolbar d-flex gap-2 flex-wrap mb-3" role="toolbar">
   <button class="btn btn-success" name="save_close" value="0" type="submit"<?php echo $this->canEdit?'':' disabled'; ?>><?php echo Text::_('JSAVE'); ?></button>
   <button class="btn btn-success" name="save_close" value="1" type="submit"<?php echo $this->canEdit?'':' disabled'; ?>><?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
   <a class="btn btn-outline-secondary" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=deliverymethods',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
  </div>
  <div class="card card-body mm-admin-panel">
   <h2 class="h5"><?php echo Text::_($this->method==='pickup'?'COM_MINIMARKET_ORDER_DELIVERY_PICKUP':'COM_MINIMARKET_ORDER_DELIVERY_COURIER'); ?></h2>
   <div class="row g-3">
    <div class="col-md-6">
     <label class="form-label" for="mm-method-enabled"><?php echo Text::_('COM_MINIMARKET_METHOD_ENABLED'); ?></label>
     <select class="form-select" name="enabled" id="mm-method-enabled">
      <option value="1"<?php echo $bool('delivery_'.$this->method.'_enabled',$this->method==='pickup'?1:0)?' selected':''; ?>><?php echo Text::_('JYES'); ?></option>
      <option value="0"<?php echo !$bool('delivery_'.$this->method.'_enabled',$this->method==='pickup'?1:0)?' selected':''; ?>><?php echo Text::_('JNO'); ?></option>
     </select>
    </div>
    <?php if($this->method==='pickup'): ?>
     <div class="col-md-6">
      <label class="form-label" for="mm-pickup-fee"><?php echo Text::_('COM_MINIMARKET_PICKUP_FEE'); ?></label>
      <input class="form-control" type="number" min="0" step="0.01" id="mm-pickup-fee" name="fee"
       value="<?php echo $esc(number_format((int)$p->get('delivery_pickup_fee_cents',0)/100,2,'.','')); ?>">
     </div>
     <div class="col-12">
      <label class="form-label" for="mm-pickup-address"><?php echo Text::_('COM_MINIMARKET_DELIVERY_PICKUP_ADDRESS'); ?></label>
      <textarea class="form-control" name="address" id="mm-pickup-address" rows="3" maxlength="512"><?php echo $esc($p->get('delivery_pickup_address','')); ?></textarea>
     </div>
     <div class="col-12">
      <label class="form-label" for="mm-pickup-schedule"><?php echo Text::_('COM_MINIMARKET_PICKUP_SCHEDULE'); ?></label>
      <textarea class="form-control" name="schedule" id="mm-pickup-schedule" rows="3" maxlength="2000"><?php echo $esc($p->get('delivery_pickup_schedule','')); ?></textarea>
     </div>
     <div class="col-12">
      <label class="form-label" for="mm-pickup-map"><?php echo Text::_('COM_MINIMARKET_PICKUP_MAP_URL'); ?></label>
      <input class="form-control" type="url" name="map_url" id="mm-pickup-map" maxlength="1024"
       value="<?php echo $esc($p->get('delivery_pickup_map_url','')); ?>" placeholder="https://">
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_PICKUP_MAP_HELP'); ?></p>
     </div>
    <?php else: ?>
     <div class="col-md-6">
      <label class="form-label" for="mm-rate-source"><?php echo Text::_('COM_MINIMARKET_DELIVERY_RATE_SOURCE'); ?></label>
      <select class="form-select" name="rate_source" id="mm-rate-source">
       <option value="directory"<?php echo $p->get('delivery_rate_source','directory')==='directory'?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_DIRECTORY'); ?></option>
       <option value="zones"<?php echo $p->get('delivery_rate_source','directory')==='zones'?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_ZONES'); ?></option>
      </select>
     </div>
     <div class="col-12" id="mm-directory-rates">
      <label class="form-label" for="mm-city-rates"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CITY_RATES'); ?></label>
      <textarea class="form-control" name="city_rates" id="mm-city-rates" rows="8" maxlength="15000" placeholder="Санкт-Петербург|350|5000"><?php echo $esc($p->get('delivery_city_rates','')); ?></textarea>
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CITY_RATES_DESC'); ?></p>
     </div>

    <?php endif; ?>
   </div>
  </div>
 </form>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php if($this->method==='courier'): ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-delivery-method','zones',Text::_('COM_MINIMARKET_DELIVERY_ZONES')); ?>
 <div class="py-3">
  <?php if($this->embeddedZoneEditor): ?>
   <?php require dirname(__DIR__).'/deliveryzone/edit.php'; ?>
  <?php else: ?>
   <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="h5 mb-0"><?php echo Text::_('COM_MINIMARKET_DELIVERY_ZONES'); ?> (<?php echo (int)$this->zones; ?>)</h3>
    <?php if($this->canEdit): ?>
     <a class="btn btn-success btn-sm" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=deliverymethods&method=courier&tab=zones&zone_new=1',false)); ?>">
      <?php echo Text::_('JTOOLBAR_NEW'); ?></a>
    <?php endif; ?>
   </div>
   <div class="table-responsive mm-native-list-viewport">
    <table class="table table-striped">
     <thead><tr><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('COM_MINIMARKET_ZONE_CITIES'); ?></th><th><?php echo Text::_('COM_MINIMARKET_ZONE_BASE_FEE'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th></tr></thead>
     <tbody>
      <?php foreach($this->zoneRows as $z): ?>
       <tr><td>
        <a href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=deliverymethods&method=courier&tab=zones&zone_id='.(int)$z->id,false)); ?>"><?php echo $esc($z->title); ?></a>
       </td><td><?php echo $esc(mb_substr(str_replace("\n",', ',(string)$z->cities),0,120)); ?></td>
       <td><?php echo number_format((int)$z->base_fee_cents/100,2,'.',' '); ?></td>
       <td><?php echo Text::_((int)$z->state===1?'JPUBLISHED':'JUNPUBLISHED'); ?></td></tr>
      <?php endforeach; ?>
     </tbody>
    </table>
   </div>
  <?php endif; ?>
 </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php endif; ?>

 <?php echo HTMLHelper::_('uitab.addTab','mm-delivery-method','compatibility',Text::_('COM_MINIMARKET_PAYMENT_COMPATIBILITY')); ?>
 <div class="card card-body mm-admin-panel">
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_DELIVERY_COMPATIBILITY_MIRROR_HELP'); ?></p>
  <form method="post" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=methodsettings.saveDeliveryCompatibility',false)); ?>">
   <input type="hidden" name="delivery_method" value="<?php echo $esc($this->method); ?>">
   <?php foreach(['on_receipt'=>'COM_MINIMARKET_PAYMENT_ON_RECEIPT','bank_transfer'=>'COM_MINIMARKET_PAYMENT_BANK_TRANSFER'] as $payment=>$label):
       $allowed=\MiniMarket\Component\Minimarket\Site\Service\CheckoutDelivery::allowedForPayment($p,$payment);
       $enabled=$bool('payment_'.$payment.'_enabled',$payment==='on_receipt'?1:0);
   ?>
    <div class="form-check mb-2">
     <input type="checkbox" name="allowed_payments[]" class="form-check-input"
       id="mm-compatible-payment-<?php echo $payment; ?>" value="<?php echo $payment; ?>"
       <?php echo in_array($this->method,$allowed,true)?'checked':''; ?>
       <?php echo $this->canEdit?'':'disabled'; ?>>
     <label class="form-check-label" for="mm-compatible-payment-<?php echo $payment; ?>">
      <?php echo Text::_($label); ?>
      <?php if(!$enabled): ?><span class="text-muted">(<?php echo Text::_('COM_MINIMARKET_METHOD_INACTIVE'); ?>)</span><?php endif; ?>
     </label>
    </div>
   <?php endforeach; ?>
   <?php echo HTMLHelper::_('form.token'); ?>
   <?php if($this->canEdit): ?>
    <button type="submit" class="btn btn-success mt-2"><?php echo Text::_('JSAVE'); ?></button>
   <?php endif; ?>
  </form>
 </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
 <?php if($this->method==='courier'): ?>
 <script>
 (()=>{const source=document.getElementById('mm-rate-source'),rates=document.getElementById('mm-directory-rates');
  if(!source||!rates)return;
  const apply=()=>rates.hidden=source.value!=='directory';
  source.addEventListener('change',apply);apply();
 })();
 </script>
 <?php endif; ?>
</div>
<?php endif; ?>
