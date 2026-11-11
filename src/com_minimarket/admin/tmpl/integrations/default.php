<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Only actually installed MiniMarket plugins are listed. Category is a separate column.
$pluginType = static function (string $element): string {
    $prefix = strtolower(explode('_', $element, 2)[0]);
    $types = [
        'delivery' => 'Доставка', 'shipping' => 'Доставка',
        'payment' => 'Оплата', 'sms' => 'SMS',
        'email' => 'Почта', 'auth' => 'Авторизация',
        'bot' => 'Боты', 'crm' => 'CRM',
        'feed' => 'Фиды', 'analytics' => 'Аналитика',
    ];
    return $types[$prefix] ?? 'Другое';
};
?>
<section class="mm-admin-panel mm-admin-list-panel">
 <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
  <h2 class="h4 mb-0"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_TITLE'); ?></h2>
  <?php if ($this->canManagePlugins): ?>
   <a class="btn btn-outline-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_plugins', false)); ?>"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_MANAGE_PLUGINS'); ?></a>
  <?php endif; ?>
 </div>
 <div class="table-responsive">
  <table class="table table-striped mm-admin-list">
   <thead><tr>
    <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_PROVIDER'); ?></th>
    <th>Тип</th>
    <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_STATUS'); ?></th>
    <th>Настройки</th>
   </tr></thead>
   <tbody>
   <?php foreach ($this->plugins as $plugin): ?>
    <tr>
     <td><?php echo $this->escape(Text::_((string) $plugin->name)); ?>
      <small class="d-block text-muted"><?php echo $this->escape((string) $plugin->element); ?></small></td>
     <td><?php echo $this->escape($pluginType((string) $plugin->element)); ?></td>
     <td><span class="badge bg-<?php echo (int) $plugin->enabled === 1 ? 'info' : 'secondary'; ?>"><?php echo Text::_((int) $plugin->enabled === 1 ? 'COM_MINIMARKET_INTEGRATIONS_PLUGIN_ENABLED' : 'COM_MINIMARKET_INTEGRATIONS_PLUGIN_DISABLED'); ?></span></td>
     <td><?php if ($this->canManagePlugins): ?>
      <a href="<?php echo $this->escape(Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . (int) $plugin->extension_id, false)); ?>">Настроить</a>
     <?php else: ?>&mdash;<?php endif; ?></td>
    </tr>
   <?php endforeach; ?>
   <?php if (!$this->plugins): ?><tr><td colspan="4"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_NO_PLUGINS'); ?></td></tr><?php endif; ?>
   </tbody>
  </table>
 </div>
</section>
