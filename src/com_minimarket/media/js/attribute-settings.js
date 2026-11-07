/* Canonical attribute editor: no old modes or storage fallbacks. */
(() => {
  'use strict';
  const allowed = {
    text: ['none','text','values','checkboxes','radio','dropdown'],
    number: ['none','range','slider','values','checkboxes','radio','dropdown'],
    editor: ['none'], document: ['none'], boolean: ['none','boolean'],
    color: ['none','swatches','checkboxes','radio','dropdown','values']
  };
  const setup=()=>{
    const type=document.getElementById('jform_input_type');
    const mode=document.getElementById('jform_filter_mode');
    const source=document.getElementById('jform_value_source');
    const multi=document.getElementById('jform_is_multiple');
    if (!type || !mode) return;
    const available=Array.from(mode.options,option=>option.cloneNode(true));
    const refresh=()=>{
      const t=type.value;
      const variants=allowed[t]||['none'];
      const previous=mode.value;
      mode.replaceChildren(...available.filter(o=>variants.includes(o.value))
        .map(o=>o.cloneNode(true)));
      mode.value=variants.includes(previous)?previous:'none';
      if (mode.value!==previous) mode.dispatchEvent(new Event('change',{bubbles:true}));
      if (t==='boolean') {
        if (source) {
          source.value='manual';
          source.dispatchEvent(new Event('change',{bubbles:true}));
        }
        if (multi) multi.value='0';
      }
      if (t==='editor' || t==='document') {
        const enabled=document.querySelector('[name="jform[is_filterable]"][value="1"]');
        const disabled=document.querySelector('[name="jform[is_filterable]"][value="0"]');
        if (enabled?.checked && disabled) {
          disabled.checked=true;
          disabled.dispatchEvent(new Event('change',{bubbles:true}));
        }
      }
    };
    type.addEventListener('change',refresh);
    refresh();
  };
  if (document.readyState==='loading')document.addEventListener('DOMContentLoaded',setup,{once:true});
  else setup();
})();
