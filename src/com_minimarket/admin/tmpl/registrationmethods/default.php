<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$esc=static fn($value):string=>htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<?php if($this->method===''):
 $mmSearch=[
  'view'=>'registrationmethods','query'=>$this->search,
  'searchLabel'=>Text::_('COM_MINIMARKET_METHOD_NAME'),
  'sort'=>$this->sort,'limit'=>$this->pageSize,
  'sortOptions'=>['name_asc'=>Text::_('JGLOBAL_TITLE_ASC'),'name_desc'=>Text::_('JGLOBAL_TITLE_DESC')],
  'filters'=>[
    ['name'=>'filter_type','label'=>Text::_('COM_MINIMARKET_METHOD_TYPE'),
     'value'=>$this->typeFilter,
     'options'=>[''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
       'builtin'=>Text::_('COM_MINIMARKET_METHOD_BUILTIN'),
       'plugin'=>Text::_('COM_MINIMARKET_METHOD_PLUGIN')]],
    ['name'=>'filter_status','label'=>Text::_('JSTATUS'),
     'value'=>$this->statusFilter,'default'=>'all',
     'options'=>['all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),
       'enabled'=>Text::_('COM_MINIMARKET_METHOD_ACTIVE'),
       'disabled'=>Text::_('COM_MINIMARKET_METHOD_INACTIVE')]]]
 ];
 require dirname(__DIR__).'/shared/method-list.php';
?>
<?php elseif($this->pluginMethod!==null): ?>
 <?php $mmPluginScope='registration';require dirname(__DIR__).'/shared/plugin-method.php'; ?>
<?php else: ?>
 <div id="j-main-container" class="j-main-container">
  <div class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_REGISTRATION_JOOMLA_HELP'); ?></div>
  <div class="card card-body mm-admin-panel">
   <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_REGISTRATION_EMAIL_METHOD'); ?></h2>
   <dl class="row">
    <dt class="col-md-4"><?php echo Text::_('JSTATUS'); ?></dt>
    <dd class="col-md-8"><?php echo Text::_((int)$this->params->get('allowUserRegistration',0)===1?'JENABLED':'JDISABLED'); ?></dd>
    <dt class="col-md-4"><?php echo Text::_('COM_MINIMARKET_REGISTRATION_EMAIL_CONFIRMATION'); ?></dt>
    <dd class="col-md-8"><?php
      $activation=(int)$this->params->get('useractivation',0);
      echo Text::_($activation===1?'COM_MINIMARKET_REGISTRATION_ACTIVATION_SELF'
         :($activation===2?'COM_MINIMARKET_REGISTRATION_ACTIVATION_ADMIN'
           :'COM_MINIMARKET_REGISTRATION_ACTIVATION_NONE'));
    ?></dd>
   </dl>
   <form method="post" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=methodsettings.save',false)); ?>">
    <input type="hidden" name="scope" value="registration">
    <input type="hidden" name="method" value="email">
    <input type="hidden" name="primary" value="0">
    <label class="form-check mb-2">
     <input class="form-check-input" type="checkbox" name="primary" value="1"
        <?php echo (string)$this->shopParams->get('checkout_verification_method','email')==='email'?'checked':''; ?>
        <?php echo $this->canEdit?'':'disabled'; ?>>
     <span class="form-check-label"><?php echo Text::_('COM_MINIMARKET_REGISTRATION_PRIMARY_CHECKOUT'); ?></span>
    </label>
    <p class="form-text"><?php echo Text::_('COM_MINIMARKET_REGISTRATION_PRIMARY_HELP'); ?></p>
    <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
    <?php if($this->canEdit): ?><button class="btn btn-success" type="submit"><?php echo Text::_('JSAVE'); ?></button><?php endif; ?>
   </form>
   <?php if($this->canEditUsers): ?>
    <div><a class="btn btn-outline-secondary mt-3" href="<?php echo $esc(Route::_('index.php?option=com_config&view=component&component=com_users',false)); ?>">
       <?php echo Text::_('COM_MINIMARKET_REGISTRATION_JOOMLA_SETTINGS'); ?></a></div>
   <?php endif; ?>
  </div>
 </div>
<?php endif; ?>
