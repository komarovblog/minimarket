<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$r=$this->case;
$canChange=$this->canEdit&&in_array((string)$r->status,['new','approved','received'],true);
$validNext=match((string)$r->status){
 'new'=>['new','approved','rejected'],
 'approved'=>['approved','received','rejected'],
 'received'=>['received','closed'],
 default=>[(string)$r->status]
};
?>
<div class="btn-toolbar d-flex flex-wrap gap-2 mb-3" role="toolbar">
 <?php if($canChange): ?><button class="btn btn-success" form="mm-return-status-form" type="submit"><span class="icon-save" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?></button><?php endif; ?>
 <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=returns',false)); ?>"><span class="icon-arrow-left" aria-hidden="true"></span> <?php echo Text::_('JTOOLBAR_BACK'); ?></a>
 <a class="btn btn-outline-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$r->order_id,false)); ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?> #<?php echo (int)$r->order_id; ?></a>
</div>
<div id="j-main-container" class="j-main-container">
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-return-'.(int)$r->id,['active'=>'details']); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-return-'.(int)$r->id,'details',Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
 <div class="row g-3">
  <div class="col-12 col-lg-7">
   <section class="card card-body mm-admin-panel h-100">
    <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_RETURN_STATUS_BLOCK'); ?> #<?php echo (int)$r->id; ?></h2>
    <p><?php echo Text::_('COM_MINIMARKET_RETURN_STATUS_'.strtoupper((string)$r->status)); ?></p>
    <?php if($this->canEdit): ?>
    <form id="mm-return-status-form" method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=returncase.status',false)); ?>">
     <input type="hidden" name="id" value="<?php echo (int)$r->id; ?>">
     <div class="row g-3">
      <div class="col-12 col-md-6">
       <label for="mm-return-status" class="form-label"><?php echo Text::_('COM_MINIMARKET_ORDER_STATUS'); ?></label>
       <select id="mm-return-status" name="status" class="form-select" <?php echo $canChange?'':'disabled'; ?>>
        <?php foreach($validNext as $status): ?>
        <option value="<?php echo $status; ?>"<?php echo $status===$r->status?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_RETURN_STATUS_'.strtoupper($status)); ?></option>
        <?php endforeach; ?>
       </select>
      </div>
      <div class="col-12 col-md-6">
       <label for="mm-return-resolution" class="form-label"><?php echo Text::_('COM_MINIMARKET_RETURN_RESOLUTION'); ?></label>
       <select id="mm-return-resolution" name="resolution" class="form-select" <?php echo $canChange?'':'disabled'; ?>>
        <?php foreach(['pending','replacement','refund','no_action'] as $resolution): ?>
        <option value="<?php echo $resolution; ?>"<?php echo $resolution===$r->resolution?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_RETURN_RESOLUTION_'.strtoupper($resolution)); ?></option>
        <?php endforeach; ?>
       </select>
      </div>
     </div>
     <p class="form-text"><?php echo Text::_('COM_MINIMARKET_RETURN_REFUND_WARNING'); ?></p>
     <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <?php endif; ?>
   </section>
  </div>
  <div class="col-12 col-lg-5">
   <section class="card card-body mm-admin-panel h-100">
    <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_RETURN_DETAILS'); ?></h2>
    <dl class="row mb-0">
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_CUSTOMER'); ?></dt>
     <dd class="col-sm-7"><?php echo $this->escape((string)$r->customer_name); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_RETURN_REASON'); ?></dt>
     <dd class="col-sm-7"><?php echo Text::_('COM_MINIMARKET_RETURN_REASON_'.strtoupper((string)$r->reason)); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_ORDER_CREATED'); ?></dt>
     <dd class="col-sm-7"><?php echo $this->escape((string)$r->created_at); ?></dd>
     <dt class="col-sm-5"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NUMBER'); ?></dt>
     <dd class="col-sm-7">
      <?php if($r->shipment_id): ?>
       <a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=delivery&id='.(int)$r->shipment_id,false)); ?>">#<?php echo (int)$r->shipment_id; ?></a>
      <?php else: ?>—<?php endif; ?>
     </dd>
     <dt class="col-12"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NOTE'); ?></dt>
     <dd class="col-12"><?php echo nl2br($this->escape((string)$r->note)); ?></dd>
    </dl>
   </section>
  </div>
 </div>

 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-return-'.(int)$r->id,'items',Text::_('COM_MINIMARKET_RETURN_ITEMS')); ?>
 <section class="card card-body mm-admin-panel mt-3">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_RETURN_ITEMS'); ?></h2>
  <div class="table-responsive mm-editor-table-viewport">
   <table class="table table-striped mm-admin-list">
    <thead><tr>
     <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_PRODUCT_SKU'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?></th>
    </tr></thead><tbody>
    <?php foreach($this->items as $line): ?>
     <tr>
      <td><?php echo $this->escape((string)$line->title); ?></td>
      <td><?php echo $this->escape((string)$line->sku); ?></td>
      <td><?php echo (int)$line->quantity; ?></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php if(!$this->items): ?><p class="form-text"><?php echo Text::_('COM_MINIMARKET_RETURN_NO_ITEMS'); ?></p><?php endif; ?>
  <?php if($this->canEdit&&in_array((string)$r->status,['new','approved'],true)): ?>
   <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=returncase.addItem',false)); ?>" class="row g-2 align-items-end mt-2">
    <input type="hidden" name="id" value="<?php echo (int)$r->id; ?>">
    <div class="col-12 col-md-8">
     <label for="mm-return-add-item" class="form-label"><?php echo Text::_('COM_MINIMARKET_RETURN_ITEM'); ?></label>
     <select name="order_item_id" id="mm-return-add-item" class="form-select" required>
      <option value=""><?php echo Text::_('COM_MINIMARKET_RETURN_ITEM_SELECT'); ?></option>
      <?php foreach($this->orderItems as $line): ?>
       <option value="<?php echo (int)$line->id; ?>"><?php echo $this->escape((string)$line->title.' · '.(string)$line->sku.' · ×'.(int)$line->quantity); ?></option>
      <?php endforeach; ?>
     </select>
    </div>
    <div class="col-6 col-md-2">
     <label for="mm-return-add-qty" class="form-label"><?php echo Text::_('COM_MINIMARKET_CART_QUANTITY'); ?></label>
     <input type="number" id="mm-return-add-qty" name="quantity" value="1" min="1" max="999" class="form-control" required>
    </div>
    <div class="col-6 col-md-2">
     <?php echo HTMLHelper::_('form.token'); ?>
     <button class="btn btn-success w-100" type="submit"><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MINIMARKET_RETURN_ITEM_ADD'); ?></button>
    </div>
   </form>
  <?php endif; ?>
 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</div>
