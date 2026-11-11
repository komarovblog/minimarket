(() => {
 'use strict';
 for(const form of document.querySelectorAll('.mm-checkout-form[data-items-cents]')){
  const panels=[...form.querySelectorAll('[data-delivery-panel]')];
  const city=form.querySelector('[name="delivery_city"]');
  const address=form.querySelector('[name="delivery_address"]');
  const shipping=form.querySelector('[data-shipping-total]');
  const discountBox=form.querySelector('[data-promo-discount]');
  const total=form.querySelector('[data-order-total]');
  const promo=form.querySelector('[name="promo_code"]');
  const feedback=form.querySelector('[data-promo-feedback]');
  const items=Number.parseInt(form.dataset.itemsCents||'0',10);
  const locale=document.documentElement.lang||'ru-RU';
  const money=cents=>new Intl.NumberFormat(locale,{minimumFractionDigits:2,maximumFractionDigits:2})
    .format(cents/100)+' ₽';
  let discount=0,fee=0,needsQuote=false,previewController=null;
  function update(){
   const method=form.querySelector('input[name="delivery_method"]:checked')?.value||'pickup';
   for(const panel of panels){
    const active=panel.dataset.deliveryPanel===method;
    panel.hidden=!active;
    for(const field of panel.querySelectorAll('input,textarea,select'))field.disabled=!active;
   }
   if(city)city.required=method==='courier';
   if(address)address.required=method==='courier';
   fee=0;needsQuote=false;
   if(method==='courier'){
    const option=city?.selectedOptions?.[0];
    if(option && option.value){
     const mode=option.dataset.rateMode||'fixed';
     if(mode!=='fixed')needsQuote=true;
     else {
      fee=Number.parseInt(option.dataset.feeCents||'0',10);
      const free=Number.parseInt(option.dataset.freeFromCents||'0',10);
      if(!Number.isSafeInteger(fee)||fee<0)fee=0;
      if(free>0&&items>=free)fee=0;
     }
    }else needsQuote=true;
   }
   if(shipping)shipping.textContent=needsQuote?'—':money(fee);
   if(discountBox)discountBox.textContent=money(discount);
   if(total)total.textContent=needsQuote?'—':money(Math.max(0,items+fee-discount));
  }
  async function preview(){
   const code=(promo?.value||'').trim();
   previewController?.abort();
   discount=0;
   if(!code){if(feedback)feedback.textContent='';update();return;}
   previewController=new AbortController();
   try{
    const payload=new FormData(form);
    const response=await fetch(form.dataset.promoPreviewUrl,{
      method:'POST',body:payload,credentials:'same-origin',
      headers:{Accept:'application/json'},signal:previewController.signal
    });
    if(!response.ok)throw Error(String(response.status));
    const result=await response.json();
    if(result.success) discount=Math.max(0,Number(result.discount_cents)||0);
    if(feedback)feedback.textContent=result.message||'';
   }catch(error){
    if(error.name==='AbortError')return;
    discount=0;if(feedback)feedback.textContent='—';
   }
   update();
  }
  form.querySelectorAll('input[name="delivery_method"]').forEach(r=>r.addEventListener('change',update));
  city?.addEventListener('change',update);
  promo?.addEventListener('change',preview);
  for(const name of ['phone','email'])form.querySelector('[name="'+name+'"]')?.addEventListener('change',preview);
  update();if(promo?.value.trim())void preview();
 }
})();