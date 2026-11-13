<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Administrator\Service\MarketingFlows;
$f=$this->flow;
$esc=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$trigger=(string)($f->trigger_code??'cart_abandoned');
?>
<form method="post" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=marketingflow.save',false)); ?>">
 <div class="btn-toolbar d-flex gap-2 flex-wrap mb-3">
  <button class="btn btn-success" type="submit" name="save_close" value="0"><?php echo Text::_('JSAVE'); ?></button>
  <button class="btn btn-success" type="submit" name="save_close" value="1"><?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
  <a class="btn btn-outline-secondary" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=marketingflows',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
 </div>
 <input type="hidden" name="id" value="<?php echo (int)($f->id??0); ?>">
 <?php echo HTMLHelper::_('form.token'); ?>
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-marketing-flow',['active'=>'general']); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-marketing-flow','general',Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
 <div class="card card-body mm-admin-panel">
  <div class="row g-3">
   <div class="col-12"><label class="form-label" for="mm-flow-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label>
    <input class="form-control" id="mm-flow-title" name="title" required maxlength="160" value="<?php echo $esc($f->title??''); ?>"></div>
   <div class="col-md-6"><label class="form-label" for="mm-flow-trigger"><?php echo Text::_('COM_MINIMARKET_FLOW_TRIGGER'); ?></label>
    <select class="form-select" name="trigger_code" id="mm-flow-trigger">
     <?php foreach(MarketingFlows::TRIGGERS as $code=>$label): ?>
      <option value="<?php echo $code; ?>"<?php echo $code===$trigger?' selected':''; ?>><?php echo Text::_($label); ?></option>
     <?php endforeach; ?>
    </select></div>
   <div class="col-md-6"><label class="form-label" for="mm-flow-stop"><?php echo Text::_('COM_MINIMARKET_FLOW_STOP_ON_ORDER'); ?></label>
    <select class="form-select" id="mm-flow-stop" name="stop_on_order">
     <option value="1"<?php echo (int)($f->stop_on_order??1)===1?' selected':''; ?>><?php echo Text::_('JYES'); ?></option>
     <option value="0"<?php echo (int)($f->stop_on_order??1)===0?' selected':''; ?>><?php echo Text::_('JNO'); ?></option>
    </select></div>
  </div>
 </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-marketing-flow','steps',Text::_('COM_MINIMARKET_FLOW_STEPS')); ?>
 <div class="card card-body mm-admin-panel">
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_FLOW_STEP_HELP'); ?></p>
  <p class="alert alert-warning"><?php echo Text::_('COM_MINIMARKET_FLOWS_DRAFT_ONLY'); ?></p>
  <?php if(!$this->templates): ?><p class="alert alert-info">
    <a href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=messagetemplates',false)); ?>">
     <?php echo Text::_('COM_MINIMARKET_FLOW_NEEDS_TEMPLATES'); ?></a></p><?php endif; ?>
  <div id="mm-flow-step-list">
   <?php foreach($this->steps as $i=>$step): ?>
    <div class="row g-3 align-items-end mb-3" data-mm-flow-step>
     <div class="col-md-3"><label class="form-label"><?php echo Text::_('COM_MINIMARKET_FLOW_DELAY_MINUTES'); ?></label>
      <input type="number" min="0" max="43200" required class="form-control" name="steps[<?php echo (int)$i; ?>][delay_minutes]"
       value="<?php echo (int)$step['delay_minutes']; ?>"></div>
     <div class="col-md-7"><label class="form-label"><?php echo Text::_('COM_MINIMARKET_TEMPLATE_TITLE'); ?></label>
      <select name="steps[<?php echo (int)$i; ?>][template_id]" class="form-select" required>
       <option value=""><?php echo Text::_('JSELECT'); ?></option>
       <?php foreach($this->templates as $t): ?><option value="<?php echo (int)$t['id']; ?>"
        data-mm-trigger="<?php echo $esc($t['event_code']); ?>"
        <?php echo (int)$t['id']===(int)$step['template_id']?' selected':''; ?>>
         <?php echo $esc($t['title']); ?></option><?php endforeach; ?>
      </select></div>
     <div class="col-md-2"><button class="btn btn-outline-danger" type="button" data-mm-flow-remove><?php echo Text::_('JDELETE'); ?></button></div>
    </div>
   <?php endforeach; ?>
  </div>
  <button type="button" id="mm-flow-add" class="btn btn-outline-primary">
   <span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_FLOW_ADD_STEP'); ?>
  </button>
 </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</form>
<script>
(()=> {
 const list=document.getElementById('mm-flow-step-list'),add=document.getElementById('mm-flow-add'),
       trigger=document.getElementById('mm-flow-trigger');
 if(!list||!add||!trigger)return;
 const template=list.querySelector('[data-mm-flow-step]');
 const blueprint=template?.cloneNode(true);
 const sync=()=> {
  for(const row of list.querySelectorAll('[data-mm-flow-step]')){
   const select=row.querySelector('select'),previous=select.value;let valid=false;
   for(const opt of select.options){
    const visible=opt.value===''||opt.dataset.mmTrigger===trigger.value;
    opt.hidden=!visible;opt.disabled=!visible;
    if(visible&&opt.value===previous)valid=true;
   }
   if(!valid)select.value='';
  }
  add.disabled=list.querySelectorAll('[data-mm-flow-step]').length>=10;
 };
 const renumber=()=>{
  for(const [i,row] of Array.from(list.querySelectorAll('[data-mm-flow-step]')).entries()){
   row.querySelector('input').name='steps['+i+'][delay_minutes]';
   row.querySelector('select').name='steps['+i+'][template_id]';
  }
  sync();
 };
 add.addEventListener('click',()=>{
  if(!blueprint||list.querySelectorAll('[data-mm-flow-step]').length>=10)return;
  const row=blueprint.cloneNode(true);
  row.querySelector('input').value='1440';
  row.querySelector('select').value='';
  list.appendChild(row);renumber();
 });
 list.addEventListener('click',e=>{
  if(!e.target.closest('[data-mm-flow-remove]'))return;
  const rows=list.querySelectorAll('[data-mm-flow-step]');
  if(rows.length<=1)return;
  e.target.closest('[data-mm-flow-step]').remove();renumber();
 });
 trigger.addEventListener('change',sync);sync();
})();
</script>
