<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;
$e=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$groups=[
 ['name'=>'customer','label'=>'COM_MINIMARKET_NOTIFICATION_EVENTS_CUSTOMER',
  'events'=>NotificationPolicy::EVENTS,'selected'=>$this->customer],
 ['name'=>'admin','label'=>'COM_MINIMARKET_NOTIFICATION_EVENTS_ADMIN',
  'events'=>NotificationPolicy::ADMIN_EVENTS,'selected'=>$this->admin]
];
?>
<div id="j-main-container" class="j-main-container">
 <div class="btn-toolbar d-flex flex-wrap gap-2 mb-3">
   <a class="btn btn-outline-secondary" href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=notificationmethods',false)); ?>">
    <?php echo Text::_('COM_MINIMARKET_NOTIFICATION_METHODS_TITLE'); ?></a>
 </div>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFICATION_SETTINGS_HELP'); ?></p>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFICATION_EVENTS_HELP'); ?></p>
 <div class="alert alert-warning"><?php echo Text::_('COM_MINIMARKET_NOTIFICATION_EVENTS_ADMIN_DISABLED'); ?></div>
 <form method="post" action="<?php echo $e(Route::_('index.php?option=com_minimarket&task=notificationevents.save',false)); ?>">
  <?php if($this->canEdit): ?><div class="mb-3"><button class="btn btn-success" type="submit">
   <?php echo Text::_('JSAVE'); ?></button></div><?php endif; ?>
  <?php foreach($groups as $group): ?>
   <section class="card mb-3 mm-admin-panel">
    <div class="card-header"><h2 class="h5 mb-0"><?php echo Text::_($group['label']); ?></h2></div>
    <div class="card-body">
     <div class="table-responsive mm-native-list-viewport">
      <table class="table table-striped mm-admin-list">
       <thead><tr>
        <th><?php echo Text::_('COM_MINIMARKET_NOTIFICATION_EVENTS_EVENT'); ?></th>
        <th><?php echo Text::_('COM_MINIMARKET_MESSAGE_TEMPLATES_TITLE'); ?></th>
        <?php foreach($this->channels as $channel): ?>
         <th><?php echo $e($channel['name']); ?><?php if(!$channel['enabled']): ?>
          <span class="form-text">(<?php echo Text::_('COM_MINIMARKET_METHOD_INACTIVE'); ?>)</span>
         <?php endif; ?></th>
        <?php endforeach; ?>
        <th><?php echo Text::_('JGLOBAL_ACTIONS'); ?></th>
       </tr></thead><tbody>
       <?php foreach($group['events'] as $event=>$label): ?><tr>
        <th scope="row"><?php echo Text::_($label); ?></th>
        <td>
         <div class="d-flex flex-wrap gap-2">
         <?php foreach(['email','telegram','max','account'] as $channelCode):
           $key=$group['name'].':'.$event.':'.$channelCode;
           $stored=$this->templates[$key]??null;
           $url='index.php?option=com_minimarket&view=messagetemplate'
             .($stored?'&id='.(int)$stored['id']
               :'&layout=edit&audience='.$group['name'].'&event_code='.$event.'&channel_code='.$channelCode);
         ?>
           <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e(Route::_($url,false)); ?>">
            <?php echo Text::_('COM_MINIMARKET_TEMPLATE_CHANNEL_'.strtoupper($channelCode)); ?>
            <?php if($channelCode!=='email'): ?>
             <span class="visually-hidden"><?php echo Text::_('COM_MINIMARKET_TEMPLATE_DRAFT_ONLY'); ?></span>
            <?php endif; ?>
           </a>
         <?php endforeach; ?>
         </div>
         <p class="form-text mb-0"><?php echo Text::_('COM_MINIMARKET_TEMPLATE_DRAFT_CHANNEL_HELP'); ?></p>
        </td>
        <?php foreach($this->channels as $channel): ?>
         <td><label class="form-check">
          <input class="form-check-input" type="checkbox"
            name="<?php echo $e($group['name']); ?>[<?php echo $e($channel['code']); ?>][]"
            value="<?php echo $e($event); ?>"
            <?php echo in_array($event,$group['selected'][$channel['code']]??[],true)?'checked':''; ?>
            <?php echo $this->canEdit?'':'disabled'; ?>>
          <span class="visually-hidden"><?php echo $e($channel['name']).' — '.Text::_($label); ?></span>
         </label></td>
        <?php endforeach; ?>
        <td>
         <button class="btn btn-outline-danger btn-sm" type="button" data-mm-event-disable
          <?php echo $this->canEdit?'':'disabled'; ?>>
          <?php echo Text::_('COM_MINIMARKET_NOTIFICATION_DISABLE_EVENT'); ?>
         </button>
        </td>
       </tr><?php endforeach; ?>
       </tbody></table>
     </div>
    </div>
   </section>
  <?php endforeach; ?>
  <?php echo HTMLHelper::_('form.token'); ?>
 </form>
 <script>
  (()=>{for(const b of document.querySelectorAll('[data-mm-event-disable]')){
    b.addEventListener('click',()=>{for(const checkbox of b.closest('tr').querySelectorAll('input[type=checkbox]'))checkbox.checked=false;});
  }})();
 </script>
</div>
