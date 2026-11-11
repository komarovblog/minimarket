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
<div id="j-main-container" class="j-main-container mm-unified-list">
<?php if($this->method===''): ?>
 <div class="table-responsive">
 <table id="mm-delivery-methods-list" class="table table-striped mm-admin-list">
  <thead><tr>
   <th><?php echo Text::_('COM_MINIMARKET_METHOD_NAME'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_METHOD_TYPE'); ?></th>
   <th><?php echo Text::_('JSTATUS'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?></th>
  </tr></thead>
  <tbody>
  <?php foreach([
    'pickup'=>['COM_MINIMARKET_ORDER_DELIVERY_PICKUP',$bool('delivery_pickup_enabled',1)],
    'courier'=>['COM_MINIMARKET_ORDER_DELIVERY_COURIER',$bool('delivery_courier_enabled',0)]
    ] as $method=>$info): ?>
   <tr>
    <td><?php echo Text::_($info[0]); ?></td>
    <td><?php echo Text::_('COM_MINIMARKET_METHOD_BUILTIN'); ?></td>
    <td><span class="badge bg-<?php echo $info[1]?'success':'secondary'; ?>"><?php echo Text::_($info[1]?'COM_MINIMARKET_METHOD_ACTIVE':'COM_MINIMARKET_METHOD_INACTIVE'); ?></span></td>
    <td><?php if($this->canEdit): ?><a href="<?php echo $esc($edit($method)); ?>"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?></a><?php else: ?>—<?php endif; ?></td>
   </tr>
  <?php endforeach; ?>
  <?php foreach($this->plugins as $plugin): ?>
   <tr>
    <td><?php echo $esc(Text::_((string)$plugin->name)); ?><small class="d-block text-muted"><?php echo $esc($plugin->element); ?></small></td>
    <td><?php echo Text::_('COM_MINIMARKET_METHOD_PLUGIN'); ?></td>
    <td><span class="badge bg-<?php echo (int)$plugin->enabled===1?'success':'secondary'; ?>"><?php echo Text::_((int)$plugin->enabled===1?'COM_MINIMARKET_METHOD_ACTIVE':'COM_MINIMARKET_METHOD_INACTIVE'); ?></span></td>
    <td><?php if($this->canEditPlugins): ?><a href="<?php echo $esc(Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id='.(int)$plugin->extension_id,false)); ?>"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?></a><?php else: ?>—<?php endif; ?></td>
   </tr>
  <?php endforeach; ?>
  </tbody>
 </table>
 </div>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_METHOD_PLUGIN_NOTE'); ?></p>
<?php else: ?>
 <form method="post" id="mm-method-form" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=methodsettings.save',false)); ?>">
  <input type="hidden" name="scope" value="delivery">
  <input type="hidden" name="method" value="<?php echo $esc($this->method); ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <div class="d-flex gap-2 flex-wrap mb-3">
   <button class="btn btn-success" name="save_close" value="0" type="submit"<?php echo $this->canEdit?'':' disabled'; ?>><?php echo Text::_('JSAVE'); ?></button>
   <button class="btn btn-success" name="save_close" value="1" type="submit"<?php echo $this->canEdit?'':' disabled'; ?>><?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
   <a class="btn btn-danger" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=deliverymethods',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
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
     <div class="col-12">
      <label class="form-label" for="mm-pickup-address"><?php echo Text::_('COM_MINIMARKET_DELIVERY_PICKUP_ADDRESS'); ?></label>
      <textarea class="form-control" name="address" id="mm-pickup-address" rows="3" maxlength="512"><?php echo $esc($p->get('delivery_pickup_address','')); ?></textarea>
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
     <div class="col-12">
      <a class="btn btn-outline-primary" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=deliveryzones',false)); ?>">
       <?php echo Text::_('COM_MINIMARKET_METHOD_EDIT_ZONES'); ?> (<?php echo (int)$this->zones; ?>)
      </a>
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_METHOD_ZONES_NOTE'); ?></p>
     </div>
    <?php endif; ?>
   </div>
  </div>
 </form>
 <?php if($this->method==='courier'): ?>
 <script>
 (()=>{const source=document.getElementById('mm-rate-source'),rates=document.getElementById('mm-directory-rates');
  if(!source||!rates)return;
  const apply=()=>rates.hidden=source.value!=='directory';
  source.addEventListener('change',apply);apply();
 })();
 </script>
 <?php endif; ?>
<?php endif; ?>
</div>
