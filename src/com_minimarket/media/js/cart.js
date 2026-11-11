/* Cart quantity and totals update without a full page reload. Server is the
 * sole price/stock authority. Native POST remains a no-JavaScript fallback. */
(() => {
  'use strict';
  const start=() => {
    const page=document.querySelector('[data-mm-cart-page]');
    if (!page || !window.fetch || !window.FormData) return;
    const feedback=page.querySelector('[data-mm-cart-feedback]');
    const money=(cents)=>new Intl.NumberFormat(document.documentElement.lang||'ru-RU',{
      minimumFractionDigits:2,maximumFractionDigits:2,
    }).format(cents/100)+' ₽';
    let busy=false;
    const fail=(message) => {
      if (feedback) {
        feedback.hidden=false;
        feedback.textContent=message || 'Unable to update cart';
      }
    };
    const refresh=(payload)=>{
      if (feedback) {
        feedback.hidden=!payload.message;
        feedback.textContent=payload.message||'';
      }
      for (const row of page.querySelectorAll('[data-mm-cart-line]')) {
        const line=payload.lines?.[row.dataset.mmCartKey];
        if (!line) {
          row.remove();
          continue;
        }
        const amount=row.querySelector('[data-mm-cart-quantity]');
        if (amount) {
          amount.value=String(line.quantity);
          amount.defaultValue=amount.value;
          amount.max=String(Math.max(1,line.max_quantity));
        }
        const lineTotal=row.querySelector('[data-mm-line-total]');
        if (lineTotal) lineTotal.textContent=money(line.line_cents);
      }
      const total=page.querySelector('[data-mm-cart-total]');
      if (total) total.textContent=money(payload.total_cents);
      const units=page.querySelector('[data-mm-cart-units]');
      if (units) units.textContent=String(payload.units);
      for (const count of document.querySelectorAll('[data-mm-cart-count]')) {
        count.textContent=String(payload.units);
      }
      const checkout=page.querySelector('[data-mm-cart-checkout]');
      if (checkout) checkout.hidden=!payload.available || payload.empty;
      const empty=page.querySelector('[data-mm-cart-empty]');
      if (empty) empty.hidden=!payload.empty;
    };
    const send=async(form)=>{
      if (busy) return;
      busy=true;
      const controls=Array.from(page.querySelectorAll('[data-mm-cart-quantity], [data-mm-cart-remove-form] button'));
      for(const control of controls)control.disabled=true;
      try {
        const response=await fetch(form.action,{
          method:'POST',credentials:'same-origin',body:new FormData(form),
          headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
          redirect:'follow'
        });
        if(!response.ok)throw Error('HTTP '+response.status);
        const json=await response.json();
        if (!json || !json.data || typeof json.data.total_cents!=='number')
          throw Error('Invalid cart reply');
        refresh(json.data);
        if (!json.success) fail(json.message || json.data.message);
        // A saved promotion depends on current quantities. Re-render its
        // server-priced estimate after any successful cart mutation.
        if (json.success && page.querySelector('#mm-cart-promo')?.value.trim()) {
          window.location.reload();
          return;
        }
      } catch(error) {
        // On a transport error, use the native form so the order is not lost.
        fail('Не удалось обновить корзину без перезагрузки');
      } finally {
        busy=false;
        for(const control of controls)control.disabled=false;
      }
    };
    for(const form of page.querySelectorAll('[data-mm-cart-quantity-form]')) {
      const input=form.querySelector('[data-mm-cart-quantity]');
      if(!input)continue;
      let timer;
      const update=()=>{
        clearTimeout(timer);
        const n=Number(input.value);
        if (!Number.isFinite(n) || input.value.trim()==='')return;
        input.value=String(Math.max(1,Math.min(Number(input.max)||99,Math.trunc(n))));
        if(input.value===input.defaultValue)return;
        timer=setTimeout(()=>send(form),350);
      };
      input.addEventListener('change',update);
      input.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(update,450);});
      form.addEventListener('submit',(event)=>{event.preventDefault();clearTimeout(timer);send(form);});
    }
    for(const form of page.querySelectorAll('[data-mm-cart-remove-form]')) {
      form.addEventListener('submit',(event)=>{event.preventDefault();send(form);});
    }
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});
  else start();
})();
