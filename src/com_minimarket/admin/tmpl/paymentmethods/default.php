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
<?php if($this->method===''):
 $mmSearch=[
  'view'=>'paymentmethods','query'=>$this->search,
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
<?php else: ?>
<div id="j-main-container" class="j-main-container">
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
</div>
<?php endif; ?>
