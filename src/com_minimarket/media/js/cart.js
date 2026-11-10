/* Progressive enhancement: changing a cart quantity saves automatically.
 * No Javascript: a submit button inside <noscript> remains available.
 */
(() => {
  'use strict';
  const init=()=>{
    for (const form of document.querySelectorAll('[data-mm-cart-quantity-form]')) {
      const input=form.querySelector('[data-mm-cart-quantity]');
      if (!input) continue;
      let timeout=null,submitted=false;
      const save=()=>{
        clearTimeout(timeout);
        if (submitted || input.value.trim()==='') return;
        const limit=Number(input.max)||99;
        const amount=Number(input.value);
        if (!Number.isFinite(amount)) return;
        const bounded=Math.max(1,Math.min(limit,Math.trunc(amount)));
        input.value=String(bounded);
        if (bounded===Number(input.defaultValue)) return;
        submitted=true;
        form.requestSubmit();
      };
      input.addEventListener('input',()=>{
        clearTimeout(timeout);
        timeout=setTimeout(save,750);
      });
      input.addEventListener('change',save);
      form.addEventListener('submit',()=>{submitted=true;clearTimeout(timeout);});
    }
  };
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init,{once:true});
  else init();
})();
