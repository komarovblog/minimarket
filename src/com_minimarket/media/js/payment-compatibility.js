/* Presentation only: authoritative payment/delivery pair is checked in CheckoutDelivery::quote. */
(() => {
 'use strict';
 function bind(form){
  const delivery=[...form.querySelectorAll('input[name="delivery_method"]')];
  const payment=[...form.querySelectorAll('input[name="payment_method"]')];
  if(!delivery.length||!payment.length)return;
  const message=form.querySelector('[data-mm-compatibility-error]');
  const submit=form.querySelector('button[type="submit"],input[type="submit"]')
    ||document.querySelector('button[form="'+form.id+'"][type="submit"]');
  const allowed=(p,d)=>p.value==='pending'||(p.dataset.allowedDeliveries||'').split(',').includes(d);
  let busy=false;
  function sync(){
   if(busy)return;
   busy=true;
   let changedDelivery=false;
   for(const d of delivery){
    const ok=payment.some(p=>allowed(p,d.value));
    if(d.type==='radio'){
     d.disabled=!ok;
     const parent=d.closest('[data-mm-delivery-choice]');
     if(parent)parent.hidden=!ok;
    }
   }
   let d=delivery.find(x=>x.checked&&!x.disabled);
   if(!d){
    d=delivery.find(x=>!x.disabled);
    if(d?.type==='radio'){d.checked=true;changedDelivery=true;}
   }
   for(const p of payment){
    const ok=!!d&&allowed(p,d.value);
    if(p.type==='radio'){
     p.disabled=!ok;
     const parent=p.closest('[data-mm-payment-choice]');
     if(parent)parent.hidden=!ok;
    }
   }
   let p=payment.find(x=>x.checked&&!x.disabled&&d&&allowed(x,d.value));
   if(!p){
    p=payment.find(x=>!x.disabled&&d&&allowed(x,d.value));
    if(p?.type==='radio')p.checked=true;
   }
   const invalid=!d||!p;
   if(message)message.hidden=!invalid;
   if(submit)submit.disabled=invalid;
   busy=false;
   if(changedDelivery)d?.dispatchEvent(new Event('change',{bubbles:true}));
  }
  delivery.forEach(r=>r.addEventListener('change',sync));
  payment.forEach(r=>r.addEventListener('change',sync));
  sync();
 }
 const run=()=>document.querySelectorAll('.mm-checkout-form,#mm-manual-order-form').forEach(bind);
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run,{once:true});
 else run();
})();
