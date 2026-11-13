<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$mmSearch=['view'=>'customers','query'=>$this->search,
 'searchLabel'=>Text::_('COM_MINIMARKET_CUSTOMER_SEARCH'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),
                'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')]];
?>
<form id="adminForm" name="adminForm" class="mm-list-form" method="post"
 action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=customers',false)); ?>">
<div id="j-main-container" class="j-main-container">
 <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
 <?php $mmColumnTable='mm-customer-list';require dirname(__DIR__).'/shared/column-toggle.php'; ?>
 <div class="table-responsive mm-native-list-viewport"><table id="mm-customer-list" class="table table-striped mm-admin-list">
 <thead><tr>
 <th><?php echo Text::_('COM_MINIMARKET_CUSTOMER_TITLE'); ?></th>
 <th>Email</th><th><?php echo Text::_('COM_MINIMARKET_CRM_ORDERS'); ?></th>
 <th><?php echo Text::_('COM_MINIMARKET_CRM_ORDER_TOTAL'); ?></th>
 <th><?php echo Text::_('COM_MINIMARKET_CRM_LAST_ORDER'); ?></th>
 </tr></thead><tbody>
 <?php foreach($this->customers as $c): ?><tr>
  <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=customer&id='.(int)$c->user_id,false)); ?>"><?php echo $this->escape($c->name); ?> (#<?php echo (int)$c->user_id; ?>)</a></td>
  <td><?php echo $this->escape($c->email); ?></td>
  <td><?php echo (int)$c->orders_count; ?></td>
  <td><?php echo number_format((int)$c->orders_total_cents/100,2,',',' '); ?> ₽</td>
  <td><?php echo $this->escape($c->last_order); ?></td>
 </tr><?php endforeach; ?></tbody></table></div>
 <?php if(!$this->customers): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_CRM_EMPTY'); ?></p><?php endif; ?>
 <input type="hidden" name="task" value=""><input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
 <?php echo HTMLHelper::_('form.token'); ?>
 <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
</div></form>