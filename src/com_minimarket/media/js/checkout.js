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
   const method=form.querySelector('input[name="delivery_method"]:checked, input[type="hidden"][name="delivery_method"]')?.value||'pickup';
   for(const panel of panels){
    const active=panel.dataset.deliveryPanel===method;
    panel.hidden=!active;
    for(const field of panel.querySelectorAll('input,textarea,select'))field.disabled=!active;
   }
   const point=form.querySelector('[name="pickup_point_id"]');
   if(point)point.required=method==='pickup';
   if(city)city.required=method==='courier';
   if(address)address.required=method==='courier';
   fee=0;needsQuote=false;
   if(method==='pickup'){
     const input=form.querySelector('input[name="delivery_method"]:checked, input[type="hidden"][name="delivery_method"]');
     const point=form.querySelector('[name="pickup_point_id"]');
     const option=point?.selectedOptions?.[0];
     const configured=Number.parseInt((option&&point.value!=='0')
       ?option.dataset.feeCents:(input?.dataset.feeCents||'0'),10);
     fee=Number.isSafeInteger(configured)&&configured>=0?configured:0;
   }
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
  for(const name of ['pickup_point_id','delivery_city','delivery_country','delivery_region','delivery_postcode','delivery_address','payment_method']){
   form.querySelectorAll('[name="'+name+'"]').forEach(el=>el.addEventListener('change',invalidate));
  }
  promo?.addEventListener('change',preview);
  for(const name of ['phone','email'])form.querySelector('[name="'+name+'"]')?.addEventListener('change',preview);
  // Masks are presentation helpers. The server independently validates
  // every submitted value against the configured mask and field type.
  for(const field of form.querySelectorAll('[data-mm-input-mask]')){
    field.addEventListener('input',()=>{
      const mask=field.dataset.mmInputMask||'';
      const raw=Array.from(field.value||'');
      const takes={9:c=>/[0-9]/.test(c),A:c=>/\p{L}/u.test(c),'*':c=>c.length>0};
      let pos=0,out='';
      for(const token of mask){
        if(takes[token]){
          while(pos<raw.length&&!takes[token](raw[pos]))pos++;
          if(pos>=raw.length)break;
          out+=raw[pos++];
        }else{
          if(pos<raw.length)out+=token;
          if(raw[pos]===token)pos++;
        }
      }
      if(field.value!==out)field.value=out;
    });
  }
  // A recipient other than the buyer requires a session-bound email code.
  for(const button of form.querySelectorAll('[data-mm-otp-send]')){
    button.addEventListener('click',async()=>{
      if(button.disabled)return;
      const endpoint=form.dataset.otpUrl;
      if(!endpoint)return;
      const state=form.querySelector('[data-mm-otp-status]');
      const recipient=form.querySelector('[name="digital_recipient_email"]');
      const selected=recipient?.value.trim()||'';
      if(!selected || !recipient.checkValidity()){
        if(recipient){recipient.required=true;recipient.reportValidity();}
        return;
      }
      const body=new FormData(form);
      body.set('verification_scope','recipient');
      body.set('digital_recipient_email',selected);
      button.disabled=true;
      if(state)state.textContent='';
      try{
        const response=await fetch(endpoint,{method:'POST',body,credentials:'same-origin',
          headers:{'Accept':'application/json'}});
        const json=await response.json();
        if(state)state.textContent=json.message||'';
      }catch(error){
        if(state)state.textContent='Unable to send confirmation code.';
      }finally{button.disabled=false;}
    });
  }
  update();
  if(zones||promo?.value.trim())void preview();
 }
})();