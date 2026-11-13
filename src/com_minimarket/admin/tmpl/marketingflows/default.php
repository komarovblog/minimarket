<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Administrator\Service\MarketingFlows;
$mmSearch=['view'=>'marketingflows','query'=>$this->search,
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_MARKETING_FLOWS_TITLE'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),
 'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],'filters'=>[]];
?>
<form id="adminForm" name="adminForm" method="post"
 action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=marketingflows',false)); ?>">
 <div id="j-main-container">
  <p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_FLOWS_DRAFT_ONLY'); ?></p>
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-marketing-flows';require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table id="mm-marketing-flows" class="table table-striped mm-admin-list">
    <thead><tr><th>ID</th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_FLOW_TRIGGER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_FLOW_STEPS'); ?></th>
     <th><?php echo Text::_('JSTATUS'); ?></th></tr></thead>
    <tbody><?php foreach($this->items as $flow): ?><tr>
     <td>#<?php echo (int)$flow->id; ?></td>
     <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=marketingflow&id='.(int)$flow->id,false)); ?>">
      <?php echo $this->escape((string)$flow->title); ?></a></td>
     <td><?php echo Text::_(MarketingFlows::TRIGGERS[(string)$flow->trigger_code]??'COM_MINIMARKET_FLOW_INVALID'); ?></td>
     <td><?php echo (int)$flow->step_count; ?></td>
     <td><?php echo Text::_('COM_MINIMARKET_FLOW_DRAFT'); ?></td>
    </tr><?php endforeach; ?></tbody>
   </table>
  </div>
  <?php if(!$this->items): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_FLOWS_EMPTY'); ?></p><?php endif; ?>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination(
   $this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>
