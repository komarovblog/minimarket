/* MiniMarket 0.5.55: price mode and flag visibility without changing stored data.
 * Only actively selected flag can be submitted; old price and base price remain
 * intact when switching from fixed bundle pricing to a calculated discount.
 */
(() => {
  'use strict';
  function mount() {
    const form=document.getElementById('adminForm');
    if(!form)return;
    const field=name=>form.querySelector('[name="jform['+name+']"]');
    const check=name=>form.querySelector('input[type="checkbox"][name="jform['+name+']"]');
    const old=field('old_price');
    const sale=check('badge_sale');
    const benefit=check('badge_benefit');
    const fresh=check('badge_new');
    const popular=check('badge_popular');
    const daysNew=field('badge_new_days');
    const daysPopular=field('badge_popular_days');
    const mode=field('bundle_price_mode');
    const pct=field('bundle_discount_pct');
    const price=field('price');
    const row=input=>input?.closest('.control-group')||input?.closest('.mb-3')||input?.parentElement;
    function update() {
      const discounted=!!mode&&mode.value==='discount';
      // Ordinary products never have a price mode, so manual price is visible.
      if(mode) {
        for(const [input,visible] of [[price,!discounted],[old,!discounted],[pct,discounted]]){
          const wrap=row(input);if(wrap)wrap.hidden=!visible;
        }
      }
      if(price) {
        // The calculated mode submits the existing base price unchanged.
        // The visible price in the storefront is derived from components.
        price.required=!discounted;
      }
      if(sale) {
        const present=!!old&&old.value.trim()!==''&&Number(old.value)>0;
        const hasPrice=!!price && Number(price.value)>=0;
        const eligible=!discounted&&present&&hasPrice&&Number(old.value)>Number(price.value);
        sale.disabled=!eligible;
        if(!eligible)sale.checked=false;
        row(sale)?.classList.toggle('mm-unavailable-setting',!eligible);
        // Keep the label and its explanatory description in place.
      }
      if(benefit) {
        benefit.disabled=!discounted;
        if(!discounted)benefit.checked=false;
        row(benefit)?.classList.toggle('mm-unavailable-setting',!discounted);
      }
      for(const [toggle,days] of [[fresh,daysNew],[popular,daysPopular]]){
        const wrap=row(days);if(toggle&&wrap)wrap.hidden=!toggle.checked;
      }
    }
    for(const el of [old,price,mode,pct,sale,benefit,fresh,popular]) {
      el?.addEventListener('input',update);
      el?.addEventListener('change',update);
    }
    update();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount);
  else mount();
})();
