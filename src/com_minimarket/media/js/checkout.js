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
  const zones=!!form.querySelector('[name="delivery_city"][list]');
  let discount=0,fee=0,serverFee=null,needsQuote=false,controller=null;
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
    if(zones)needsQuote=true;
    else {
     const option=city?.selectedOptions?.[0];
     if(option?.value){
      fee=Number.parseInt(option.dataset.feeCents||'0',10);
      const free=Number.parseInt(option.dataset.freeFromCents||'0',10);
      if(!Number.isSafeInteger(fee)||fee<0)fee=0;
      if(free>0&&items>=free)fee=0;
     }else needsQuote=true;
    }
   }
   if(needsQuote&&Number.isSafeInteger(serverFee)&&serverFee>=0){
    fee=serverFee;needsQuote=false;
   }
   if(shipping)shipping.textContent=needsQuote?'—':money(fee);
   if(discountBox)discountBox.textContent=money(discount);
   if(total)total.textContent=needsQuote?'—':money(Math.max(0,items+fee-discount));
  }
  async function preview(){
   const code=(promo?.value||'').trim();
   controller?.abort();serverFee=null;discount=0;update();
   if(!code&&!zones){if(feedback)feedback.textContent='';return;}
   controller=new AbortController();
   try{
    const result=await fetch(form.dataset.promoPreviewUrl,{
      method:'POST',body:new FormData(form),credentials:'same-origin',
      headers:{Accept:'application/json'},signal:controller.signal
    });
    if(!result.ok)throw Error(String(result.status));
    const data=await result.json();
    if(data.success){
      discount=Math.max(0,Number(data.discount_cents)||0);
      serverFee=Number(data.shipping_cents);
    }
    if(feedback)feedback.textContent=code?(data.message||''):'';
   }catch(error){
    if(error.name==='AbortError')return;
    if(feedback)feedback.textContent='—';
   }
   update();
  }
  function invalidate(){serverFee=null;update();void preview();}
  form.querySelectorAll('input[name="delivery_method"]').forEach(r=>r.addEventListener('change',invalidate));
  for(const name of ['delivery_city','delivery_country','delivery_region','delivery_postcode','delivery_address','payment_method']){
   form.querySelectorAll('[name="'+name+'"]').forEach(el=>el.addEventListener('change',invalidate));
  }
  promo?.addEventListener('change',preview);
  for(const name of ['phone','email'])form.querySelector('[name="'+name+'"]')?.addEventListener('change',preview);
  update();
  if(zones||promo?.value.trim())void preview();
 }
})();