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
   <?php if($this->canEdit): ?>
    <div><a class="btn btn-primary" href="<?php echo $esc(Route::_('index.php?option=com_config&view=component&component=com_users',false)); ?>">
       <?php echo Text::_('COM_MINIMARKET_REGISTRATION_JOOMLA_SETTINGS'); ?></a></div>
   <?php endif; ?>
  </div>
 </div>
<?php endif; ?>
