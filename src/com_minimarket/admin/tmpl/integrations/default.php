<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$pluginType=static function(string $element):string {
    $prefix=strtolower(explode('_',$element,2)[0]);
    $types=['shipping'=>'DELIVERY','delivery'=>'DELIVERY','payment'=>'PAYMENT',
        'sms'=>'SMS','email'=>'EMAIL','auth'=>'AUTH','bot'=>'BOT','crm'=>'CRM',
        'feed'=>'FEED','analytics'=>'ANALYTICS'];
    return Text::_('COM_MINIMARKET_INTEGRATIONS_TYPE_'.($types[$prefix]??'OTHER'));
};
$kindOptions=[''=>Text::_('COM_MINIMARKET_FILTER_ANY')];
foreach(['delivery','payment','sms','email','auth','bot','crm','feed','analytics','other'] as $type){
    $kindOptions[$type]=Text::_('COM_MINIMARKET_INTEGRATIONS_TYPE_'.strtoupper($type));
}
$mmSearch=[
 'view'=>'integrations','query'=>$this->search,'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_INTEGRATIONS_PROVIDER'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],
 'filters'=>[
 ['name'=>'filter_type','label'=>Text::_('COM_MINIMARKET_INTEGRATIONS_TYPE'),'value'=>$this->typeFilter,'options'=>$kindOptions],
 ['name'=>'filter_status','label'=>Text::_('COM_MINIMARKET_INTEGRATIONS_STATUS'),
  'value'=>$this->statusFilter,'default'=>'all',
  'options'=>['all'=>Text::_('COM_MINIMARKET_FILTER_ANY'),
   'enabled'=>Text::_('COM_MINIMARKET_INTEGRATIONS_PLUGIN_ENABLED'),
   'disabled'=>Text::_('COM_MINIMARKET_INTEGRATIONS_PLUGIN_DISABLED')]]]
];
$href='index.php?option=com_minimarket&view=integrations&'.http_build_query([
 'filter_search'=>$this->search,'filter_status'=>$this->statusFilter,'filter_type'=>$this->typeFilter,
 'filter_sort'=>$this->sort,'limit'=>$this->pageSize
],'','&',PHP_QUERY_RFC3986);
?>
<form method="post" id="adminForm" name="adminForm" class="mm-list-form"
       action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=integrations',false)); ?>">
 <div id="j-main-container" class="j-main-container">

    <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table id="mm-integrations-list" class="table table-striped mm-admin-list">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_PROVIDER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_TYPE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_STATUS'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?></th>
    </tr></thead>
    <tbody>
    <?php foreach($this->plugins as $plugin): ?>
     <tr>
      <td data-label="<?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_PROVIDER'); ?>">
       <?php echo $this->escape(Text::_((string)$plugin->name)); ?>
       <small class="d-block text-muted"><?php echo $this->escape((string)$plugin->element); ?></small></td>
      <td data-label="<?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_TYPE'); ?>"><?php echo $this->escape($pluginType((string)$plugin->element)); ?></td>
      <td data-label="<?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_STATUS'); ?>">
       <span class="badge bg-<?php echo (int)$plugin->enabled===1?'success':'secondary'; ?>">
       <?php echo Text::_((int)$plugin->enabled===1?'COM_MINIMARKET_INTEGRATIONS_PLUGIN_ENABLED':'COM_MINIMARKET_INTEGRATIONS_PLUGIN_DISABLED'); ?></span></td>
      <td data-label="<?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?>">
       <?php if($this->canManagePlugins): ?>
        <a href="<?php echo $this->escape(Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id='.(int)$plugin->extension_id,false)); ?>">
         <?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_SETTINGS'); ?></a>
       <?php else: ?>—<?php endif; ?>
      </td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
<?php if (!$this->plugins): ?><div class="alert alert-info mt-2" role="status"><?php echo Text::_('COM_MINIMARKET_INTEGRATIONS_NO_PLUGINS'); ?></div><?php endif; ?>
  </div>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <input type="hidden" name="boxchecked" value="0">
  <input type="hidden" name="task" value="">
  <?php echo HTMLHelper::_('form.token'); ?>
  </div>
</form>
