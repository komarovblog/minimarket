<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section class="mm-admin-panel mm-admin-list-panel">
 <h2 class="h4"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NEW'); ?></h2>
 <div class="alert alert-info" role="note"><span class="icon-info-circle" aria-hidden="true"></span>
 <?php echo Text::_('COM_MINIMARKET_DELIVERY_CREATE_HINT'); ?></div>
 <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=delivery.create',false)); ?>" class="row g-3">
  <div class="col-12">
    <div class="row g-2 mb-2">
      <div class="col-12 col-md-6">
        <label for="mm-delivery-order-search" class="form-label">Поиск заказа</label>
        <input id="mm-delivery-order-search" class="form-control" type="search" placeholder="Номер, покупатель или город">
      </div>
      <div class="col-6 col-md-3">
        <label for="mm-delivery-date-from" class="form-label">Дата от</label>
        <input id="mm-delivery-date-from" class="form-control" type="date">
      </div>
      <div class="col-6 col-md-3">
        <label for="mm-delivery-date-to" class="form-label">Дата до</label>
        <input id="mm-delivery-date-to" class="form-control" type="date">
      </div>
    </div>
    <label class="form-label" for="mm-shipment-order"><?php echo Text::_('COM_MINIMARKET_DELIVERY_ORDER'); ?> *</label>
    <select class="form-select" id="mm-shipment-order" name="order_id" required>
     <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
     <?php foreach($this->orders as $o): ?><option value="<?php echo (int)$o->id; ?>" <?php echo (int)$o->id===$this->preselectedOrder?'selected':''; ?>>#<?php echo (int)$o->id; ?> — <?php echo $this->escape($o->customer_name.' / '.$o->delivery_city.' / '.$o->created_at); ?></option><?php endforeach; ?>
    </select>
    <div class="form-text">Показаны только курьерские заказы без отправления, кроме отменённых и удалённых.</div>
    <?php if (!$this->orders): ?><div class="alert alert-warning mt-2">Подходящих заказов нет. Проверьте способ доставки у заказов.</div><?php endif; ?>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-shipment-carrier"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></label>
    <select class="form-select" id="mm-shipment-carrier" name="carrier">
      <?php foreach(['own','cdek','other'] as $carrier): ?><option value="<?php echo $carrier; ?>"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER_'.strtoupper($carrier)); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-shipment-track"><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?></label>
    <input class="form-control" id="mm-shipment-track" name="tracking_number" maxlength="120">
    <div class="form-text"><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK_HINT'); ?></div>
  </div>
  <div class="col-12">
    <label class="form-label" for="mm-shipment-note"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NOTE'); ?></label>
    <textarea class="form-control" id="mm-shipment-note" name="note" maxlength="2000" rows="3"></textarea>
  </div>
  <div class="col-12">
   <?php echo HTMLHelper::_('form.token'); ?>
   <button type="submit" class="btn btn-primary"><?php echo Text::_('JSAVE'); ?></button>
   <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
  </div>
 </form>
<script>
(() => {
  const select = document.getElementById('mm-shipment-order');
  const search = document.getElementById('mm-delivery-order-search');
  const from = document.getElementById('mm-delivery-date-from');
  const to = document.getElementById('mm-delivery-date-to');
  if (!select || !search || !from || !to) return;
  const options = [...select.options].slice(1).map(option => ({
    option, name: option.textContent.toLocaleLowerCase(), date: (option.textContent.match(/\\d{4}-\\d{2}-\\d{2}/) || [''])[0]
  }));
  const update = () => {
    const term = search.value.trim().toLocaleLowerCase();
    for (const row of options) {
      row.option.hidden = !(row.name.includes(term) && (!from.value || row.date >= from.value) && (!to.value || row.date <= to.value));
      row.option.disabled = row.option.hidden;
    }
    if (select.selectedOptions[0]?.disabled) select.value = '';
  };
  for (const field of [search, from, to]) field.addEventListener('input', update);
})();
</script>
</section>
