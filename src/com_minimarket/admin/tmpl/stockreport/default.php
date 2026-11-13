<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$mmSearch=[
 'view'=>'stockreport','query'=>$this->search,
 'searchLabel'=>Text::_('COM_MINIMARKET_STOCK_REPORT_SEARCH'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>[
    'title'=>Text::_('COM_MINIMARKET_STOCK_REPORT_SORT_TITLE'),
    'cover'=>Text::_('COM_MINIMARKET_STOCK_REPORT_SORT_COVER'),
    'sold'=>Text::_('COM_MINIMARKET_STOCK_REPORT_SORT_SOLD'),
    'available'=>Text::_('COM_MINIMARKET_STOCK_REPORT_SORT_AVAILABLE')],
 'filters'=>[
    ['name'=>'filter_period','label'=>Text::_('COM_MINIMARKET_STOCK_REPORT_PERIOD'),
     'value'=>(string)$this->period,'default'=>'30','options'=>[
      '30'=>Text::_('COM_MINIMARKET_STOCK_REPORT_30_DAYS'),
      '90'=>Text::_('COM_MINIMARKET_STOCK_REPORT_90_DAYS')]],
    ['name'=>'filter_stock','label'=>Text::_('COM_MINIMARKET_STOCK_REPORT_STOCK_FILTER'),
     'value'=>$this->stockFilter,'options'=>[
      ''=>Text::_('COM_MINIMARKET_FILTER_ANY'),
      'out'=>Text::_('COM_MINIMARKET_STOCK_REPORT_OUT'),
      'low'=>Text::_('COM_MINIMARKET_STOCK_REPORT_LOW'),
      'reserved'=>Text::_('COM_MINIMARKET_STOCK_REPORT_RESERVED'),
      'no_demand'=>Text::_('COM_MINIMARKET_STOCK_REPORT_NO_DEMAND')]],
    ['name'=>'filter_abc','label'=>Text::_('COM_MINIMARKET_STOCK_REPORT_ABC'),
     'value'=>$this->abcFilter,'options'=>[
      ''=>Text::_('COM_MINIMARKET_FILTER_ANY'),'A'=>'A','B'=>'B','C'=>'C',
      'none'=>Text::_('COM_MINIMARKET_STOCK_REPORT_NO_ABC')]],
    ['name'=>'filter_xyz','label'=>Text::_('COM_MINIMARKET_STOCK_REPORT_XYZ'),
     'value'=>$this->xyzFilter,'options'=>[
      ''=>Text::_('COM_MINIMARKET_FILTER_ANY'),'X'=>'X','Y'=>'Y','Z'=>'Z',
      'none'=>Text::_('COM_MINIMARKET_STOCK_REPORT_NO_XYZ')]]
 ]
];
?>
<form method="post" name="adminForm" id="adminForm" class="mm-list-form"
      action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=stockreport',false)); ?>">
 <div id="j-main-container" class="j-main-container">
  <?php if(!$this->inventoryEnabled): ?>
   <div class="alert alert-warning"><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_INVENTORY_OFF'); ?></div>
  <?php endif; ?>
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_HELP'); ?> <?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_XYZ_HELP'); ?></p>
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-stockreport-list'; require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table id="mm-stockreport-list" class="table table-striped mm-admin-list">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_PRODUCT'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_AVAILABLE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_HELD'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_TOTAL'); ?></th>
     <th><?php echo Text::sprintf('COM_MINIMARKET_STOCK_REPORT_SOLD',$this->period); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_COVER'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_ABC'); ?></th>
     <th title="<?php echo $this->escape(Text::_('COM_MINIMARKET_STOCK_REPORT_XYZ_HELP')); ?>"><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_XYZ'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_REVENUE'); ?></th>
    </tr></thead><tbody>
    <?php foreach($this->rows as $row): ?>
     <tr>
      <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=product&layout=edit&id='.(int)$row['product_id'],false)); ?>">
       <?php echo $this->escape((string)$row['title']); ?></a>
       <?php if(!$row['published']): ?><span class="text-muted">(<?php echo Text::_('JUNPUBLISHED'); ?>)</span><?php endif; ?>
      </td>
      <td><?php echo $this->escape((string)$row['sku']); ?></td>
      <td class="text-end"><?php echo number_format((int)$row['available'],0,',',' '); ?></td>
      <td class="text-end"><?php echo number_format((int)$row['held'],0,',',' '); ?></td>
      <td class="text-end"><?php echo number_format((int)$row['total'],0,',',' '); ?></td>
      <td class="text-end"><?php echo number_format((int)$row['sold'],0,',',' '); ?></td>
      <td class="text-end"><?php echo $row['cover_days']===null?'—':number_format((float)$row['cover_days'],1,',',' '); ?></td>
      <td class="text-center"><?php echo $row['abc']!==''?$this->escape((string)$row['abc']):'—'; ?></td>
      <td class="text-center"><?php echo $row['xyz']!==''?$this->escape((string)$row['xyz']):'—'; ?></td>
      <td class="text-end text-nowrap"><?php echo number_format((int)$row['revenue_cents']/100,2,',',' '); ?> ₽</td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php if(!$this->rows): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_STOCK_REPORT_EMPTY'); ?></p><?php endif; ?>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="0">
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>
