/* MiniMarket attribute editor: filter presentation, selection cardinality and
 * search-in-values are THREE independent settings. Joomla showon provides the
 * baseline; this script limits modes to the selected canonical value type.
 */
(() => {
  'use strict';
  const filters = {
    text: ['none','dropdown','checkboxes'],
    number: ['none','range','slider','dropdown','checkboxes'],
    editor: ['none'],
    boolean: ['none','dropdown','checkboxes'],
    document: ['none'],
    color: ['none','dropdown','checkboxes']
  };
  const setup=()=>{
    const type=document.getElementById('jform_input_type');
    const source=document.getElementById('jform_value_source');
    const display=document.getElementById('jform_filter_mode');
    const productMultiple=document.getElementById('jform_is_multiple');
    if (!type || !display) return;
    const options=Array.from(display.options,o=>o.cloneNode(true));
    const radios=name=>Array.from(document.querySelectorAll('[name="jform['+name+']"]'));
    const setRadio=(name,value)=>{
      for (const radio of radios(name)) radio.checked=radio.value===value;
    };
    const refresh=()=>{
      const t=type.value,allowed=filters[t]||['none'],previous=display.value;
      display.replaceChildren(...options.filter(o=>allowed.includes(o.value))
        .map(o=>o.cloneNode(true)));
      display.value=allowed.includes(previous)?previous:'none';
      if (t==='boolean') {
        if (source) source.value='manual';
        if (productMultiple) productMultiple.value='0';
      }
      const multipleAllowed=['dropdown','checkboxes'].includes(display.value)
        && t!=='boolean';
      const searchAllowed=['text','color'].includes(t)
        && ['dropdown','checkboxes'].includes(display.value);
      if (!multipleAllowed) setRadio('filter_multiple','0');
      if (!searchAllowed) setRadio('filter_search','0');
      for (const [field,visible] of [
        ['filter_multiple',multipleAllowed],['filter_search',searchAllowed]
      ]) {
        const element=radios(field)[0];
        const control=element?.closest('.control-group');
        if (control) control.hidden=!visible;
      }
      if (display.value!==previous) display.dispatchEvent(
        new Event('change',{bubbles:true}));
    };
    type.addEventListener('change',refresh);
    display.addEventListener('change',refresh);
    refresh();
  };
  if (document.readyState==='loading') document.addEventListener('DOMContentLoaded',setup,{once:true});
  else setup();
})();
