<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use MiniMarket\Component\Minimarket\Site\Service\AbandonedCartStore;
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$mmSearch=['view'=>'abandonedcarts','query'=>$this->search,
 'searchLabel'=>Text::_('COM_MINIMARKET_ABANDONED_CARTS_SEARCH'),
 'sort'=>$this->sort,'limit'=>$this->pageSize,
 'sortOptions'=>['newest'=>Text::_('COM_MINIMARKET_LIST_NEWEST'),
 'oldest'=>Text::_('COM_MINIMARKET_LIST_OLDEST')],'filters'=>[]];
?>
<form id="adminForm" name="adminForm" method="post"
 action="<?php echo $e(Route::_('index.php?option=com_minimarket&view=abandonedcarts',false)); ?>">
 <div id="j-main-container">
  <p class="form-text"><?php echo Text::sprintf('COM_MINIMARKET_ABANDONED_CARTS_HELP',
      AbandonedCartStore::ABANDONED_MINUTES,AbandonedCartStore::RETENTION_DAYS); ?></p>
  <div class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_ABANDONED_CARTS_NO_OUTREACH'); ?></div>
  <?php require dirname(__DIR__).'/shared/searchtools.php'; ?>
  <?php $mmColumnTable='mm-abandoned-cart-list';require dirname(__DIR__).'/shared/column-toggle.php'; ?>
  <div class="table-responsive mm-native-list-viewport">
   <table id="mm-abandoned-cart-list" class="table table-striped mm-admin-list">
    <thead><tr><th><?php echo Text::_('JDATE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_CUSTOMERS_TITLE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_ORDER_QUANTITY'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_PRODUCTS'); ?></th>
    </tr></thead>
    <tbody><?php foreach($this->carts as $cart): ?><tr>
     <td><?php echo $e($cart['updated_at']); ?> UTC</td>
     <td><?php if((int)$cart['user_id']>0): ?>
      <a href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=customer&id='.(int)$cart['user_id'],false)); ?>">
      #<?php echo (int)$cart['user_id']; ?></a>
      <?php else: ?><?php echo Text::_('COM_MINIMARKET_ABANDONED_CART_GUEST'); ?><?php endif; ?></td>
     <td><?php echo (int)$cart['item_units']; ?></td>
     <td><?php foreach($cart['lines'] as $key=>$qty):
       if(!preg_match('/^([1-9][0-9]*):[0-9]+$/D',(string)$key,$m))continue; ?>
      <span class="d-inline-block me-2">
       <a href="<?php echo $e(Route::_('index.php?option=com_minimarket&view=product&id='.(int)$m[1],false)); ?>">
        #<?php echo (int)$m[1]; ?></a> × <?php echo (int)$qty; ?>
      </span>
     <?php endforeach; ?></td>
    </tr><?php endforeach; ?></tbody>
   </table>
  </div>
  <?php if(!$this->carts): ?><p class="alert alert-info"><?php echo Text::_('COM_MINIMARKET_ABANDONED_CARTS_EMPTY'); ?></p><?php endif; ?>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="limitstart" value="<?php echo (int)$this->offset; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
  <?php echo \MiniMarket\Component\Minimarket\Administrator\Service\NativeListTools::pagination($this->total,$this->offset,$this->pageSize); ?>
 </div>
</form>
