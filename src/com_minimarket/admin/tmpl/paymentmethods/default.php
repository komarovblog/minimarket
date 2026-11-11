<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$p=$this->params;
$esc=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$bool=static fn($name,$default=0)=>(int)$p->get($name,$default)===1;
$edit=static fn($method)=>Route::_('index.php?option=com_minimarket&view=paymentmethods&method='.$method,false);
?>
<div id="j-main-container" class="j-main-container mm-unified-list">
<?php if($this->method===''): ?>
 <div class="table-responsive">
 <table id="mm-payment-methods-list" class="table table-striped mm-admin-list">
  <thead><tr>
   <th><?php echo Text::_('COM_MINIMARKET_METHOD_NAME'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_METHOD_TYPE'); ?></th>
   <th><?php echo Text::_('JSTATUS'); ?></th>
   <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?></th>
  </tr></thead>
  <tbody>
  <?php foreach([
    'on_receipt'=>['COM_MINIMARKET_PAYMENT_ON_RECEIPT',$bool('payment_on_receipt_enabled',1)],
    'bank_transfer'=>['COM_MINIMARKET_PAYMENT_BANK_TRANSFER',$bool('payment_bank_transfer_enabled',0)]
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
 <form id="mm-payment-method-form" method="post" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=methodsettings.save',false)); ?>">
  <input type="hidden" name="scope" value="payment">
  <input type="hidden" name="method" value="<?php echo $esc($this->method); ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <div class="d-flex gap-2 flex-wrap mb-3">
   <button type="submit" name="save_close" value="0" class="btn btn-success"<?php echo $this->canEdit?'':' disabled'; ?>><?php echo Text::_('JSAVE'); ?></button>
   <button type="submit" name="save_close" value="1" class="btn btn-success"<?php echo $this->canEdit?'':' disabled'; ?>><?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
   <a class="btn btn-danger" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=paymentmethods',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
  </div>
  <div class="card card-body mm-admin-panel">
   <h2 class="h5"><?php echo Text::_($this->method==='on_receipt'?'COM_MINIMARKET_PAYMENT_ON_RECEIPT':'COM_MINIMARKET_PAYMENT_BANK_TRANSFER'); ?></h2>
   <div class="row g-3">
    <div class="col-md-6">
     <label class="form-label" for="mm-payment-enabled"><?php echo Text::_('COM_MINIMARKET_METHOD_ENABLED'); ?></label>
     <select class="form-select" id="mm-payment-enabled" name="enabled">
      <option value="1"<?php echo $bool('payment_'.$this->method.'_enabled',$this->method==='on_receipt'?1:0)?' selected':''; ?>><?php echo Text::_('JYES'); ?></option>
      <option value="0"<?php echo !$bool('payment_'.$this->method.'_enabled',$this->method==='on_receipt'?1:0)?' selected':''; ?>><?php echo Text::_('JNO'); ?></option>
     </select>
    </div>
    <?php if($this->method==='bank_transfer'): ?>
     <div class="col-12">
      <label for="mm-bank-instructions" class="form-label"><?php echo Text::_('COM_MINIMARKET_METHOD_BANK_INSTRUCTIONS'); ?></label>
      <textarea id="mm-bank-instructions" name="instructions" class="form-control" maxlength="2000" rows="5"><?php echo $esc($p->get('payment_bank_instructions','')); ?></textarea>
      <p class="form-text"><?php echo Text::_('COM_MINIMARKET_METHOD_BANK_HELP'); ?></p>
     </div>
    <?php else: ?>
     <p class="form-text"><?php echo Text::_('COM_MINIMARKET_METHOD_ON_RECEIPT_HELP'); ?></p>
    <?php endif; ?>
   </div>
  </div>
 </form>
<?php endif; ?>
</div>
