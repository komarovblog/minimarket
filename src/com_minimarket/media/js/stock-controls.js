/* Stock tracking is a view/edit mode, never a command to clear quantities. */
(() => {
  'use strict';
  const start = () => {
    const form=document.querySelector('#adminForm[data-mm-inventory-enabled]');
    const stock=form?.querySelector('[name="jform[stock]"]');
    if(!form||!stock)return;
    const enabled=form.dataset.mmInventoryEnabled!=='0';
    const modes=Array.from(form.querySelectorAll('[name="jform[option_stock_mode]"]'));
    const copy=document.createElement('input');
    copy.type='hidden';
    copy.name='jform[stock]';
    copy.disabled=true;
    stock.after(copy);
    const update=()=>{
      const selected=modes.find(x=>x.checked)?.value;
      const freeze=!enabled || selected==='1';
      // A disabled native field is omitted from form data: post an exact
      // hidden copy so the server doesn't turn an existing value into zero.
      copy.value=stock.value;
      copy.disabled=!freeze;
      stock.disabled=freeze;
      stock.classList.toggle('mm-stock-frozen',freeze);
      stock.setAttribute('aria-disabled',String(freeze));
    };
    stock.addEventListener('input',()=>{copy.value=stock.value;});
    modes.forEach(mode=>mode.addEventListener('change',update));
    form.addEventListener('submit',update);
    update();
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});
  else start();
})();
