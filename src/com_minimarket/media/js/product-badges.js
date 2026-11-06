/* MiniMarket 0.5.54: sale is visible but disabled until old price is set.
 * Bundle price method is managed on Details, separate from composition rows.
 * No form submit or timer reset from this script. */
(() => {
 'use strict';
 function start(){
  const form=document.getElementById('adminForm');
  if(!form)return;
  const old=form.querySelector('[name="jform[old_price]"]');
  const sale=form.querySelector('input[type="checkbox"][name="jform[badge_sale]"]');
  const newer=form.querySelector('input[type="checkbox"][name="jform[badge_new]"]');
  const popular=form.querySelector('input[type="checkbox"][name="jform[badge_popular]"]');
  const newDays=form.querySelector('[name="jform[badge_new_days]"]');
  const popularDays=form.querySelector('[name="jform[badge_popular_days]"]');
  const benefit=form.querySelector('input[type="checkbox"][name="jform[badge_benefit]"]');
  const mode=form.querySelector('[name="jform[bundle_price_mode]"]');
  const discount=form.querySelector('[name="jform[bundle_discount_pct]"]');
  const fixed=form.querySelector('[name="jform[price]"]');
  const row=input=>input?.closest('.control-group')||input?.parentElement;
  function refresh(){
    if(sale){
      const canEnable=old&&old.value.trim()!==''&&Number(old.value)>0;
      sale.disabled=!canEnable;
      if(!canEnable)sale.checked=false;
      // Label and Joomla's description stay VISIBLE, explaining why disabled.
      row(sale)?.classList.toggle('mm-unavailable-setting',!canEnable);
    }
    for(const [flag,days] of [[newer,newDays],[popular,popularDays]]){
      if(days&&flag) {
        const wrap=row(days);
        if(wrap)wrap.hidden=!flag.checked;
      }
    }
    if(mode){
      const automatic=mode.value==='discount';
      const wrap=row(fixed);
      if(wrap)wrap.hidden=automatic;
      if(fixed) {
        fixed.required=!automatic;
        if(automatic&&fixed.value.trim()==='')fixed.value='0.00';
      }
      const discountWrap=row(discount);
      if(discountWrap)discountWrap.hidden=!automatic;
      if(benefit){
        benefit.disabled=!automatic;
        if(!automatic)benefit.checked=false;
        row(benefit)?.classList.toggle('mm-unavailable-setting',!automatic);
      }
    }
  }
  for(const field of [old,newer,popular,mode,discount]){
    field?.addEventListener('change',refresh);
    field?.addEventListener('input',refresh);
  }
  refresh();
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);
 else start();
})();
