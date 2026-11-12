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
    if (!type || !display) return;
    const options=Array.from(display.options,o=>o.cloneNode(true));
    const controls=name=>Array.from(document.querySelectorAll('[name="jform['+name+']"]'));
    const setValue=(name,value)=>{
      for(const input of controls(name)){
        if(input.type==='radio'||input.type==='checkbox')input.checked=input.value===value;
        else input.value=value;
      }
    };
    const refresh=()=>{
      const t=type.value,allowed=filters[t]||['none'],previous=display.value;
      display.replaceChildren(...options.filter(o=>allowed.includes(o.value))
        .map(o=>o.cloneNode(true)));
      display.value=allowed.includes(previous)?previous:'none';
      if (t==='boolean') {
        if (source) source.value='manual';
      }
      const multipleAllowed=['dropdown','checkboxes'].includes(display.value)
        && t!=='boolean';
      const searchAllowed=['text','color'].includes(t)
        && ['dropdown','checkboxes'].includes(display.value);
      if (!multipleAllowed) setValue('filter_multiple','0');
      if (!searchAllowed) setValue('filter_search','0');
      for (const [field,visible] of [
        ['filter_multiple',multipleAllowed],['filter_search',searchAllowed]
      ]) {
        const element=controls(field)[0];
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
