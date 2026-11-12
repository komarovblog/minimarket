<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
/** $mmPluginScope is delivery, payment or registration. */
$plugin=$this->pluginMethod;
$pluginId=(int)$plugin->extension_id;
$viewName=match($mmPluginScope){
 'delivery'=>'deliverymethods','payment'=>'paymentmethods',
 default=>'registrationmethods'
};
$esc=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$canEdit=!empty($this->canEditPlugins);
$canState=!empty($this->canEditPluginState);
$info=Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id='.$pluginId,false);
?>
<div id="j-main-container" class="j-main-container">
 <div class="d-flex gap-2 flex-wrap mb-3">
  <a class="btn btn-outline-secondary" href="<?php echo $esc(Route::_('index.php?option=com_minimarket&view='.$viewName,false)); ?>">
   <?php echo Text::_('JTOOLBAR_BACK'); ?></a>
  <?php if($canEdit): ?>
  <a class="btn btn-outline-secondary" href="<?php echo $esc($info); ?>">
   <?php echo Text::_('COM_MINIMARKET_METHOD_PLUGIN_INFO'); ?></a>
  <?php endif; ?>
 </div>
 <h2 class="h4"><?php echo $esc(Text::_((string)$plugin->name)); ?></h2>
 <p class="text-muted small"><?php echo $esc((string)$plugin->element); ?> ·
  <?php echo Text::_((int)$plugin->enabled===1
   ?'COM_MINIMARKET_METHOD_ACTIVE':'COM_MINIMARKET_METHOD_INACTIVE'); ?></p>
 <?php if($canEdit || $canState): ?>
 <form method="post" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=pluginmethod.save',false)); ?>"
       id="mm-plugin-method-form">
  <input type="hidden" name="scope" value="<?php echo $esc($mmPluginScope); ?>">
  <input type="hidden" name="plugin_id" value="<?php echo $pluginId; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <div class="btn-toolbar d-flex gap-2 flex-wrap mb-3" role="toolbar">
   <button class="btn btn-success" name="save_close" value="0" type="submit"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button>
   <button class="btn btn-success" name="save_close" value="1" type="submit"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_ACTION_SAVE_CLOSE'); ?></button>
  </div>
  <?php if($canState): ?>
  <div class="mb-3">
   <label for="mm-plugin-enabled" class="form-label"><?php echo Text::_('JSTATUS'); ?></label>
   <select id="mm-plugin-enabled" name="enabled" class="form-select">
    <option value="1"<?php echo (int)$plugin->enabled===1?' selected':''; ?>><?php echo Text::_('JENABLED'); ?></option>
    <option value="0"<?php echo (int)$plugin->enabled===0?' selected':''; ?>><?php echo Text::_('JDISABLED'); ?></option>
   </select>
  </div>
  <?php endif; ?>
  <?php if($this->pluginForm!==null && $canEdit): ?>
  <?php foreach($this->pluginForm->getFieldsets('params') as $fieldset): ?>
   <div class="mb-3">
    <h3 class="h6"><?php echo $esc(Text::_((string)($fieldset->label??$fieldset->name))); ?></h3>
    <?php echo $this->pluginForm->renderFieldset($fieldset->name); ?>
   </div>
  <?php endforeach; ?>
  <?php endif; ?>

 </form>
 <?php else: ?>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_METHOD_PLUGIN_NO_LOCAL_SETTINGS'); ?></p>
 <?php endif; ?>
</div>
