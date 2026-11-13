<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$e=static fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<div id="j-main-container" class="j-main-container">
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_FISCAL_PROVIDERS_HELP'); ?></p>
 <div class="table-responsive mm-native-list-viewport">
  <table class="table table-striped mm-admin-list">
   <thead><tr>
    <th><?php echo Text::_('COM_MINIMARKET_METHOD_NAME'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_METHOD_PLUGIN'); ?></th>
    <th><?php echo Text::_('JSTATUS'); ?></th>
    <th><?php echo Text::_('JGLOBAL_ACTIONS'); ?></th>
   </tr></thead>
   <tbody><?php foreach($this->providers as $provider): ?><tr>
    <td><?php echo $e(Text::_((string)$provider->name)); ?></td>
    <td><?php echo $e($provider->element); ?></td>
    <td><?php echo Text::_((int)$provider->enabled===1?'JENABLED':'JDISABLED'); ?></td>
    <td><?php if($this->canEditPlugins): ?>
     <a href="<?php echo $e(Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id='.(int)$provider->extension_id,false)); ?>">
      <?php echo Text::_('JEDIT'); ?></a>
    <?php else: ?>—<?php endif; ?></td>
   </tr><?php endforeach; ?></tbody>
  </table>
 </div>
 <?php if(!$this->providers): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_FISCAL_PROVIDERS_EMPTY'); ?></p><?php endif; ?>
 <p class="form-text"><?php echo Text::_('COM_MINIMARKET_FISCAL_PROVIDERS_NO_ADAPTER'); ?></p>
</div>
