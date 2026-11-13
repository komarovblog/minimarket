<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$mmSearch=['view'=>'recommendations','query'=>$this->search,'sort'=>$this->sort,'limit'=>$this->pageSize,
 'searchLabel'=>Text::_('COM_MINIMARKET_PRODUCTS'),
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')]];
?>
<form action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=recommendations',false)); ?>"
 method="post" name="adminForm" id="adminForm" class="mm-list-form">
 <div id="j-main-container">
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-recommendations-list';require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport"><table class="table table-striped mm-admin-list" id="mm-recommendations-list">
   <thead><tr><th>ID</th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_RECOMMEND_SIMILAR'); ?></th>
    <th><?php echo Text::_('COM_MINIMARKET_RECOMMEND_TOGETHER'); ?></th>
    <th><?php echo Text::_('JSTATUS'); ?></th></tr></thead>
   <tbody><?php foreach($this->items as $product): ?>
    <tr>
     <td>#<?php echo (int)$product->id; ?></td>
     <td><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=recommendation&product_id='.(int)$product->id,false)); ?>">
      <?php echo $this->escape((string)$product->title); ?></a></td>
     <td><?php echo $this->escape((string)$product->sku); ?></td>
     <td><?php echo Text::_('COM_MINIMARKET_RECOMMEND_MODE_'.strtoupper((string)$product->similar_mode)); ?></td>
     <td><?php echo Text::_('COM_MINIMARKET_RECOMMEND_MODE_'.strtoupper((string)$product->together_mode)); ?></td>
     <td><?php echo Text::_((int)$product->state===1?'JPUBLISHED':'JUNPUBLISHED'); ?></td>
    </tr>
   <?php endforeach; ?></tbody>
  </table></div>
  <?php if(!$this->items): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_RECOMMENDATIONS_EMPTY'); ?></p><?php endif; ?>
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>
