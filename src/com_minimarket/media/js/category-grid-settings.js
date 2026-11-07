/* Joomla component Options and category editor: show six grid tiers,
 * but truly disable them for List or Inherit and preserve their values on POST.
 */
(() => {
  'use strict';
  const sizes=['xs','sm','md','lg','xl','xxl'];

  const configure=(prefix,layoutName,disabledLayouts)=>{
    const layout=document.querySelector('[name="jform['+layoutName+']"]');
    if (!layout) return;
    const selectors=sizes.map(size=>
      document.querySelector('[name="jform['+prefix+'cards_'+size+']"]'))
      .filter(Boolean);
    if (selectors.length!==sizes.length) return;

    const update=()=>{
      const disabled=disabledLayouts.includes(layout.value);
      for (const input of selectors) {
        const group=input.closest('.control-group')||input.closest('.mb-3');
        let mirror=input.parentElement?.querySelector(
          '[data-mm-grid-mirror="'+input.name+'"]');
        if (disabled && !mirror) {
          mirror=document.createElement('input');
          mirror.type='hidden';
          mirror.name=input.name;
          mirror.dataset.mmGridMirror=input.name;
          input.insertAdjacentElement('afterend',mirror);
        }
        if (mirror) {
          mirror.value=input.value;
          mirror.disabled=!disabled;
          if (!disabled)mirror.remove();
        }
        input.disabled=disabled;
        input.setAttribute('aria-disabled',disabled?'true':'false');
        group?.classList.toggle('mm-grid-disabled',disabled);
      }
    };
    layout.addEventListener('change',update);
    update();
    // If any values change before a layout switch, preserve current count.
    for (const input of selectors) {
      input.addEventListener('change',()=>{
        const mirror=input.parentElement?.querySelector(
          '[data-mm-grid-mirror="'+input.name+'"]');
        if (mirror)mirror.value=input.value;
      });
    }
  };
  const init=()=>{
    configure('category_','category_product_layout',['list']);
    configure('','product_layout',['list','inherit']);
  };
  if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded',init,{once:true});
  } else init();
})();
