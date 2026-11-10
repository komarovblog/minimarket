<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$groups=[
 'payment'=>['ЮKassa','Bank cards / SBP'],
 'delivery'=>['СДЭК','Local courier'],
 'sms'=>['SMS provider'],
 'email'=>['Transactional email'],
 'auth'=>['Яндекс ID','Сбер ID','МТС ID','VK ID'],
 'bot'=>['Telegram bot'],
 'crm'=>['CRM / ERP'],
 'analytics'=>['E-commerce events'],
 'feeds'=>['Маркетплейсы / feeds'],
 'webhook'=>['Webhooks / external API'],
];
?>
<section class="mm-admin-panel mm-admin-list-panel">
 <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
  <h2 class="h4 mb-0"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_TITLE'); ?></h2>
  <?php if($this->canManagePlugins): ?>
   <a class="btn btn-outline-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_plugins',false)); ?>"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_MANAGE_PLUGINS'); ?></a>
  <?php endif; ?>
 </div>
 <div class="alert alert-info" role="note"><span class="icon-info-circle" aria-hidden="true"></span>
 <?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_HELP'); ?></div>
 <div class="table-responsive"><table class="table table-striped mm-admin-list">
  <thead><tr><th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_PROVIDER'); ?></th><th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_STATUS'); ?></th></tr></thead>
  <tbody>
  <?php foreach($groups as $type=>$providers): ?>
   <tr class="table-group-divider"><th colspan="2" scope="rowgroup"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_GROUP_'.strtoupper($type)); ?></th></tr>
   <?php foreach($providers as $name): ?>
    <tr><td><?php echo $this->escape($name); ?></td><td><span class="badge bg-secondary"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_NOT_CONNECTED'); ?></span></td></tr>
   <?php endforeach; ?>
  <?php endforeach; ?>
  </tbody>
 </table></div>
 <h3 class="h5 mt-4"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_INSTALLED'); ?></h3>
 <div class="table-responsive"><table class="table table-striped mm-admin-list">
  <thead><tr><th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_PROVIDER'); ?></th><th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_STATUS'); ?></th></tr></thead>
  <tbody>
  <?php foreach($this->plugins as $plugin): ?>
   <tr><td><?php echo $this->escape((string)$plugin->name); ?> <small><?php echo $this->escape((string)$plugin->element); ?></small></td>
    <td><span class="badge bg-<?php echo (int)$plugin->enabled===1?'info':'secondary'; ?>"><?php echo Text::_((int)$plugin->enabled===1?'COM_MINIMARKET_INTEGRATIONS_PLUGIN_ENABLED':'COM_MINIMARKET_INTEGRATIONS_PLUGIN_DISABLED'); ?></span></td></tr>
  <?php endforeach; ?>
  <?php if(!$this->plugins): ?><tr><td colspan="2"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_NO_PLUGINS'); ?></td></tr><?php endif; ?>
  </tbody></table></div>
</section>
