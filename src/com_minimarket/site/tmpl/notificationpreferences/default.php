<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Administrator\Service\NotificationPolicy;
$esc=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<div class="container py-4">
 <h1 class="h3"><?php echo Text::_('COM_MINIMARKET_NOTIFY_PREFERENCES_TITLE'); ?></h1>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_NOTIFY_PREFERENCES_HELP'); ?></p>
 <?php if(!$this->channels): ?>
  <p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_NOTIFY_NO_CHANNELS'); ?></p>
 <?php else: ?>
  <form method="post" action="<?php echo $esc(Route::_('index.php?option=com_minimarket&task=notificationpreferences.save',false)); ?>">
   <div class="table-responsive">
    <table class="table table-striped">
     <thead><tr><th><?php echo Text::_('COM_MINIMARKET_NOTIFY_EVENT_SECTION'); ?></th>
      <?php foreach($this->channels as $channel=>$info): ?>
      <th><?php echo $esc(Text::_($info['label'])); ?></th><?php endforeach; ?>
     </tr></thead>
     <tbody>
     <?php foreach(NotificationPolicy::EVENTS as $event=>$label): ?>
      <tr>
       <th scope="row"><?php echo Text::_($label); ?></th>
       <?php foreach($this->channels as $channel=>$info): ?>
        <td>
         <?php if(in_array($event,$info['events'],true)): ?>
          <label class="form-check">
           <input class="form-check-input" type="checkbox"
              name="preferences[<?php echo $esc($event); ?>][<?php echo $esc($channel); ?>]"
              value="1" <?php echo !empty($this->preferences[$event][$channel])?'checked':''; ?>>
           <span class="visually-hidden"><?php echo Text::_($label); ?> — <?php echo $esc(Text::_($info['label'])); ?></span>
          </label>
         <?php else: ?>—<?php endif; ?>
        </td>
       <?php endforeach; ?>
      </tr>
     <?php endforeach; ?>
     </tbody>
    </table>
   </div>
   <?php echo HTMLHelper::_('form.token'); ?>
   <button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button>
  </form>
 <?php endif; ?>
</div>
