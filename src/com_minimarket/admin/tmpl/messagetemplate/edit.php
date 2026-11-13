<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$t=$this->item;
$initialAudience=$this->initialAudience;
$initialEvent=$this->initialEvent;
$val=static fn($key,$default='')=>(string)($t->$key??$default);
?>
<form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=messagetemplate.save',false)); ?>">
 <div class="btn-toolbar d-flex gap-2 flex-wrap mb-3">
  <button class="btn btn-success" name="save_close" value="0" type="submit"><?php echo Text::_('JSAVE'); ?></button>
  <button class="btn btn-success" name="save_close" value="1" type="submit"><?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
  <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=messagetemplates',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
 </div>
 <input type="hidden" name="id" value="<?php echo (int)($t->id??0); ?>">
 <?php echo HTMLHelper::_('form.token'); ?>
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-message-template',['active'=>'general']); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-message-template','general',Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
 <div class="card card-body mm-admin-panel"><div class="row g-3">
  <div class="col-12"><label class="form-label" for="mm-template-title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label>
   <input class="form-control" name="title" id="mm-template-title" maxlength="160" required value="<?php echo $this->escape($val('title')); ?>"></div>
  <div class="col-md-6"><label class="form-label" for="mm-template-audience"><?php echo Text::_('COM_MINIMARKET_TEMPLATE_AUDIENCE'); ?></label>
   <select class="form-select" id="mm-template-audience" name="audience">
    <?php foreach(['customer','admin','marketing'] as $audience): ?>
     <option value="<?php echo $audience; ?>"<?php echo $val('audience',$initialAudience)===$audience?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_TEMPLATE_'.strtoupper($audience)); ?></option>
    <?php endforeach; ?>
   </select></div>
  <div class="col-md-6"><label class="form-label" for="mm-template-event"><?php echo Text::_('COM_MINIMARKET_NOTIFICATION_EVENTS_EVENT'); ?></label>
   <select class="form-select" id="mm-template-event" name="event_code" required>
    <?php foreach($this->events as $audience=>$list): ?>
     <?php foreach($list as $code=>$label): ?>
      <option value="<?php echo $this->escape($code); ?>" data-mm-audience="<?php echo $audience; ?>"
       <?php echo $val('event_code',$initialEvent)===$code&&$val('audience',$initialAudience)===$audience?' selected':''; ?>>
       <?php echo Text::_($label); ?></option>
     <?php endforeach; ?>
    <?php endforeach; ?>
   </select></div>
  <div class="col-12"><label class="form-label" for="mm-template-subject"><?php echo Text::_('COM_MINIMARKET_TEMPLATE_SUBJECT'); ?></label>
   <input class="form-control" id="mm-template-subject" maxlength="190" name="subject"
    value="<?php echo $this->escape($val('subject')); ?>" required></div>
  <div class="col-12"><label class="form-label" for="mm-template-body"><?php echo Text::_('COM_MINIMARKET_TEMPLATE_BODY'); ?></label>
   <textarea class="form-control" id="mm-template-body" name="body" rows="10" maxlength="6000" required><?php echo $this->escape($val('body')); ?></textarea></div>
  <div class="col-12"><p class="form-text"><?php echo Text::_('COM_MINIMARKET_TEMPLATE_PLACEHOLDERS'); ?></p></div>
 </div></div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-message-template','publishing',Text::_('COM_MINIMARKET_CATEGORY_TAB_PUBLISHING')); ?>
 <div class="card card-body mm-admin-panel"><label class="form-label" for="mm-template-state"><?php echo Text::_('JSTATUS'); ?></label>
  <select class="form-select" id="mm-template-state" name="state">
   <option value="0"<?php echo (int)$val('state')===0?' selected':''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
   <option value="1"<?php echo (int)$val('state')===1?' selected':''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
  </select>
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_TEMPLATE_ACTIVATION_HELP'); ?></p>
 </div>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</form>
<script>
(()=> {
 const audience=document.getElementById('mm-template-audience'),
       event=document.getElementById('mm-template-event');
 const sync=()=>{const selected=event.value;let chosen=false;
  for(const option of event.options){
   const visible=option.dataset.mmAudience===audience.value;
   option.hidden=!visible;option.disabled=!visible;
   if(visible&&option.value===selected)chosen=true;
  }
  if(!chosen){const first=Array.from(event.options).find(o=>!o.disabled);
   if(first)event.value=first.value;}
 };
 audience.addEventListener('change',sync);sync();
})();
</script>
