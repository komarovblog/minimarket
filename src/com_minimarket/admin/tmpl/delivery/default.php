<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$s=$this->shipment;
?>
<div class="btn-toolbar d-flex gap-2 flex-wrap mb-3" role="toolbar">
 <button type="submit" form="mm-delivery-update-form" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
 <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=deliveries',false)); ?>"><?php echo Text::_('JCANCEL'); ?></a>
</div>
<div id="j-main-container" class="j-main-container">
 <?php echo HTMLHelper::_('uitab.startTabSet','mm-delivery-'.(int)$s->id,['active'=>'details']); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-delivery-'.(int)$s->id,'details',Text::_('COM_MINIMARKET_TAB_MAIN')); ?>
<section class="mm-admin-panel mm-admin-list-panel">
 <h2 class="h4">#<?php echo (int)$s->id; ?> — <?php echo Text::_('COM_MINIMARKET_DASHBOARD_DELIVERIES'); ?></h2>
 <p><a href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=order&id='.(int)$s->order_id,false)); ?>"><?php echo Text::_('COM_MINIMARKET_ORDER_NUMBER'); ?> #<?php echo (int)$s->order_id; ?></a> — <?php echo $this->escape((string)$s->customer_name); ?></p>
 <p><?php echo $this->escape($s->delivery_city.' / '.$s->delivery_address); ?></p>
 <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=delivery.update',false)); ?>" id="mm-delivery-update-form" class="row g-3">
  <input type="hidden" name="id" value="<?php echo (int)$s->id; ?>">
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-carrier"><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER'); ?></label>
    <select class="form-select" id="mm-carrier" name="carrier">
      <?php foreach(['own','cdek','other'] as $carrier): ?><option value="<?php echo $carrier; ?>" <?php echo $s->carrier===$carrier?'selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_CARRIER_'.strtoupper($carrier)); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-reason"><?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON'); ?></label>
    <select id="mm-reason" name="reason" class="form-select">
     <?php foreach(['initial','redelivery','replacement','other'] as $reason): ?>
      <option value="<?php echo $reason; ?>"<?php echo ($s->reason??'initial')===$reason?' selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_REASON_'.strtoupper($reason)); ?></option>
     <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-track"><?php echo Text::_('COM_MINIMARKET_DELIVERY_TRACK'); ?></label>
    <input class="form-control" id="mm-track" name="tracking_number" maxlength="120" value="<?php echo $this->escape((string)$s->tracking_number); ?>">
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label" for="mm-state"><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS'); ?></label>
    <select class="form-select" id="mm-state" name="status">
      <?php
       $allowedStates=match((string)$s->status){
         'new','awaiting_assembly'=>['awaiting_assembly','assembling','ready','in_transit','delivered','cancelled'],
         'assembling'=>['assembling','ready','in_transit','delivered','cancelled'],
         'ready'=>['ready','in_transit','awaiting_pickup','delivered','cancelled'],
         'in_transit'=>['in_transit','awaiting_pickup','delivered','failed'],
         'awaiting_pickup'=>['awaiting_pickup','delivered','failed'],
         'failed'=>['failed','ready','in_transit','awaiting_pickup','delivered'],
         default=>[(string)$s->status]
       };
       foreach($allowedStates as $state): ?><option value="<?php echo $state; ?>" <?php echo $s->status===$state?'selected':''; ?>><?php echo Text::_('COM_MINIMARKET_DELIVERY_STATUS_'.strtoupper($state)); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12">
   <label class="form-label" for="mm-note"><?php echo Text::_('COM_MINIMARKET_DELIVERY_NOTE'); ?></label>
   <textarea class="form-control" id="mm-note" name="note" maxlength="2000" rows="4"><?php echo $this->escape((string)$s->note); ?></textarea>
  </div>
  <div class="col-12"><?php echo HTMLHelper::_('form.token'); ?></div>
 </form>
 <?php if($this->canViewFinance): ?>
 <div class="mt-4 pt-3 border-top">
  <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_DELIVERY_ACTUAL_COST'); ?></h3>
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_DELIVERY_COST_HELP'); ?></p>
  <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=delivery.cost',false)); ?>" class="row g-3">
    <input type="hidden" name="id" value="<?php echo (int)$s->id; ?>">
    <div class="col-12 col-md-4">
      <label class="form-label" for="mm-shipping-cost"><?php echo Text::_('COM_MINIMARKET_DELIVERY_ACTUAL_COST'); ?></label>
      <input id="mm-shipping-cost" name="actual_cost" class="form-control" type="number" step="0.01" min="0" max="9999999999999.99"
        value="<?php echo $s->actual_cost_cents===null?'':intdiv((int)$s->actual_cost_cents,100).'.'.str_pad((string)((int)$s->actual_cost_cents%100),2,'0',STR_PAD_LEFT); ?>" required>
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label" for="mm-shipping-proof"><?php echo Text::_('COM_MINIMARKET_DELIVERY_COST_REFERENCE'); ?></label>
      <input id="mm-shipping-proof" name="cost_reference" class="form-control" maxlength="128" minlength="3"
        value="<?php echo $this->escape((string)$s->cost_reference); ?>" required>
    </div>
    <div class="col-12 col-md-2 align-self-end">
      <?php echo HTMLHelper::_('form.token'); ?>
      <button type="submit" class="btn btn-success"><?php echo Text::_('JSAVE'); ?></button>
    </div>
  </form>
  <?php if($s->actual_cost_cents!==null): ?>
   <p class="form-text"><?php echo Text::_('COM_MINIMARKET_DELIVERY_COST_CONFIRMED'); ?>:
    <?php echo $this->escape((string)($s->cost_confirmed_at??'')); ?>,
    #<?php echo (int)($s->cost_confirmed_by??0); ?></p>
  <?php endif; ?>
  <?php if($this->costRevisions): ?>
  <div class="table-responsive"><table class="table table-striped">
   <thead><tr><th><?php echo Text::_('JDATE'); ?></th><th><?php echo Text::_('COM_MINIMARKET_DELIVERY_ACTUAL_COST'); ?></th><th><?php echo Text::_('COM_MINIMARKET_DELIVERY_COST_REFERENCE'); ?></th></tr></thead>
   <tbody><?php foreach($this->costRevisions as $rev): ?>
    <tr><td><?php echo $this->escape((string)$rev->created_at); ?></td>
     <td><?php echo number_format((int)$rev->confirmed_cost_cents/100,2,'.',' '); ?> ₽</td>
     <td><?php echo $this->escape((string)$rev->evidence_reference); ?> (#<?php echo (int)$rev->actor_user_id; ?>)</td></tr>
   <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
 </div>
 <?php endif; ?>
</section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 <?php echo HTMLHelper::_('uitab.addTab','mm-delivery-'.(int)$s->id,'returns',Text::_('COM_MINIMARKET_RETURNS_TITLE')); ?>
 <section class="card card-body mm-admin-panel">
  <?php if($this->linkedReturns): ?>
  <div class="mt-3">
   <h3 class="h5"><?php echo Text::_('COM_MINIMARKET_RETURNS_TITLE'); ?></h3>
   <?php foreach($this->linkedReturns as $r): ?>
    <a class="d-block" href="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&view=returncase&id='.(int)$r->id,false)); ?>">#<?php echo (int)$r->id; ?> — <?php echo Text::_('COM_MINIMARKET_RETURN_STATUS_'.strtoupper((string)$r->status)); ?></a>
   <?php endforeach; ?>
  </div>
  <?php endif; ?>

 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>
 
 <?php echo HTMLHelper::_('uitab.addTab','mm-delivery-'.(int)$s->id,'events',Text::_('COM_MINIMARKET_SHIPMENT_EVENTS_TITLE')); ?>
 <section class="card card-body mm-admin-panel">
  <h2 class="h5"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENTS_TITLE'); ?></h2>
  <?php if((string)$s->problem_status==='open'): ?>
    <div class="alert alert-warning">
     <?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_OPEN'); ?>:
     <?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_'.strtoupper((string)$s->problem_code)); ?>
    </div>
  <?php elseif((string)$s->problem_status==='resolved'): ?>
    <p class="form-text"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_RESOLVED'); ?></p>
  <?php endif; ?>
  <p class="form-text"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENTS_HELP'); ?></p>
  <?php if((string)$s->status!=='cancelled'): ?>
  <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_minimarket&task=delivery.event',false)); ?>" class="row g-3" data-mm-shipment-event-form>
   <input type="hidden" name="id" value="<?php echo (int)$s->id; ?>">
   <input type="hidden" name="event_key" value="<?php echo bin2hex(random_bytes(16)); ?>">
   <div class="col-12 col-md-6">
    <label class="form-label" for="mm-event-code"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_TYPE'); ?></label>
    <select name="event_code" id="mm-event-code" class="form-select" required>
     <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
     <?php
      $events=\MiniMarket\Component\Minimarket\Administrator\Service\ShipmentEvents::EVENTS;
      foreach($events as $event):
       try{
        \MiniMarket\Component\Minimarket\Administrator\Service\ShipmentEvents::apply(
           (array)$s,['event_code'=>$event,'problem_code'=>'delay']);
       }catch(\DomainException){continue;}
     ?>
      <option value="<?php echo $event; ?>"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_'.strtoupper($event)); ?></option>
     <?php endforeach; ?>
    </select>
   </div>
   <div class="col-12 col-md-6">
    <label class="form-label" for="mm-event-date"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_OCCURRED'); ?></label>
    <input id="mm-event-date" name="occurred_at" type="datetime-local" class="form-control"
     value="<?php echo gmdate('Y-m-d\TH:i'); ?>" required>
    <p class="form-text"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_UTC'); ?></p>
   </div>
   <div class="col-12" data-mm-event-problem hidden>
    <label class="form-label" for="mm-event-problem-code"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_TYPE'); ?></label>
    <select id="mm-event-problem-code" name="problem_code" class="form-select" disabled>
     <option value=""><?php echo Text::_('COM_MINIMARKET_FILTER_ANY'); ?></option>
     <?php foreach(\MiniMarket\Component\Minimarket\Administrator\Service\ShipmentEvents::PROBLEMS as $problem): ?>
      <option value="<?php echo $problem; ?>"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_'.strtoupper($problem)); ?></option>
     <?php endforeach; ?>
    </select>
   </div>
   <div class="col-12">
    <label class="form-label" for="mm-event-note"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_NOTE'); ?></label>
    <textarea id="mm-event-note" name="event_note" class="form-control" rows="3" maxlength="2000"></textarea>
   </div>
   <div class="col-12">
    <?php echo HTMLHelper::_('form.token'); ?>
    <button type="submit" class="btn btn-success"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_ADD'); ?></button>
   </div>
  </form>
  <script>
  (()=>{const form=document.querySelector('[data-mm-shipment-event-form]');
   const code=form?.querySelector('[name="event_code"]');
   const wrap=form?.querySelector('[data-mm-event-problem]');
   const problem=form?.querySelector('[name="problem_code"]');
   if(!code||!wrap||!problem)return;
   const update=()=>{const open=code.value==='problem_opened';
    wrap.hidden=!open;problem.disabled=!open;problem.required=open;
    if(!open)problem.value='';};
   code.addEventListener('change',update);update();
  })();
  </script>
  <?php endif; ?>
  <?php if($this->shipmentEvents): ?>
   <div class="table-responsive mt-4"><table class="table table-striped">
    <thead><tr>
     <th><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_OCCURRED'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_TYPE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_NOTE'); ?></th>
     <th><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_SOURCE'); ?></th>
    </tr></thead>
    <tbody><?php foreach($this->shipmentEvents as $event): ?>
     <tr>
      <td><?php echo $this->escape((string)$event->occurred_at); ?></td>
      <td><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENT_'.strtoupper((string)$event->event_code)); ?>
       <?php if($event->problem_code!==''): ?>
        — <?php echo Text::_('COM_MINIMARKET_SHIPMENT_PROBLEM_'.strtoupper((string)$event->problem_code)); ?>
       <?php endif; ?>
      </td>
      <td><?php echo $this->escape((string)$event->note); ?></td>
      <td><?php echo $this->escape((string)$event->source); ?> #<?php echo (int)$event->actor_user_id; ?></td>
     </tr>
    <?php endforeach; ?></tbody>
   </table></div>
  <?php else: ?>
   <p class="form-text"><?php echo Text::_('COM_MINIMARKET_SHIPMENT_EVENTS_EMPTY'); ?></p>
  <?php endif; ?>
 </section>
 <?php echo HTMLHelper::_('uitab.endTab'); ?>

 <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
</div>
