<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;
$esc=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<?php if($this->method===''):
 $mmSearch=[
 'view'=>'notificationmethods','query'=>$this->search,
 'searchLabel'=>Text::_('COM_MINIMARKET_METHOD_NAME'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['name_asc'=>Text::_('JGLOBAL_TITLE_ASC'),'name_desc'=>Text::_('JGLOBAL_TITLE_DESC')],
 'filters'=>[
 ['name'=>'filter_type','label'=>Text::_('COM_MINIMARKET_METHOD_TYPE'),
  'value'=>$this->typeFilter,'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
      'builtin'=>Text::_('COM_MINIMARKET_METHOD_BUILTIN'),'plugin'=>Text::_('COM_MINIMARKET_METHOD_PLUGIN')]],
 ['name'=>'filter_status','label'=>Text::_('JSTATUS'),'value'=>$this->statusFilter,'default'=>'all',
  'options'=>['all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),
      'enabled'=>Text::_('COM_MINIMARKET_METHOD_ACTIVE'),'disabled'=>Text::_('COM_MINIMARKET_METHOD_INACTIVE')]]]
 ];
 require dirname(__DIR__).'/shared/method-list.php';
?>
<?php else: ?>
 <div id="j-main-container" class="j-main-container">
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFY_CHANNEL_NOTE'); ?></p>
  <form method="post" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=notificationmethods.save',false)); ?>">
   <input type="hidden" name="method" value="<?php echo $esc($this->method); ?>">
   <div class="btn-toolbar d-flex gap-2 mb-3">
    <button class="btn btn-success" type="submit"<?php echo $this->canEdit?'':' disabled'; ?>><?php echo Text::_('JSAVE'); ?></button>
    <a class="btn btn-outline-secondary" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view=notificationmethods',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
   </div>
   <section class="card card-body mm-admin-panel">
    <h2 class="h5"><?php echo $esc($this->method==='email'?Text::_('COM_MINIMARKET_NOTIFY_EMAIL'):$this->pluginName); ?></h2>
    <?php if($this->method==='email'): ?>
    <div class="mb-3">
     <label class="form-label" for="mm-notify-email-enabled"><?php echo Text::_('COM_MINIMARKET_NOTIFY_CHANNEL_ENABLED'); ?></label>
     <select class="form-select" style="max-width:360px" name="enabled" id="mm-notify-email-enabled">
      <option value="0"<?php echo !$this->channelEnabled?' selected':''; ?>><?php echo Text::_('JNO'); ?></option>
      <option value="1"<?php echo $this->channelEnabled?' selected':''; ?>><?php echo Text::_('JYES'); ?></option>
     </select>
     <p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFY_EMAIL_DESCRIPTION'); ?></p>
    </div>
    <?php else: ?>
     <p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFY_PLUGIN_OWNER'); ?>
      <a href="<?php echo $esc(Route::_('index.php?option=com_plugins&view=plugins',false)); ?>"><?php echo Text::_('COM_MINIMARKET_METHOD_PLUGIN_INFO'); ?></a>
     </p>
    <?php endif; ?>
    <h3 class="h6"><?php echo Text::_('COM_MINIMARKET_NOTIFY_EVENT_SECTION'); ?></h3>
    <div class="row g-2">
     <?php foreach(NotificationPolicy::EVENTS as $code=>$label): ?>
      <div class="col-12 col-md-6">
       <label class="form-check">
        <input class="form-check-input" type="checkbox" name="events[]" value="<?php echo $esc($code); ?>"
          <?php echo in_array($code,$this->events,true)?'checked':''; ?>
          <?php echo $this->canEdit?'':'disabled'; ?>>
        <span class="form-check-label"><?php echo Text::_($label); ?></span>
       </label>
      </div>
     <?php endforeach; ?>
    </div>
   </section>
   <?php echo HTMLHelper::_('form.token'); ?>
  </form>
 </div>
<?php endif; ?>
