/* MiniMarket: six canonical attribute types, manual or dictionary values. */
(() => {
  'use strict';
  const filters = {
    text: ['none','text','values','checkboxes','dropdown'],
    number: ['none','range','slider','checkboxes','dropdown'],
    editor: ['none'], boolean: ['none'], document: ['none'],
    color: ['none','swatches','checkboxes','radio','dropdown']
  };
  const setup = () => {
    const type = document.getElementById('jform_input_type');
    const source = document.getElementById('jform_value_source');
    const filter = document.getElementById('jform_filter_mode');
    const multiple = document.getElementById('jform_is_multiple');
    if (!type || !source || !filter) return;
    const choices = Array.from(filter.options, option => option.cloneNode(true));
    const refresh = () => {
      const t = type.value;
      const allowed = filters[t] || ['none'];
      const old = filter.value;
      filter.replaceChildren(...choices.filter(option => allowed.includes(option.value))
        .map(option => option.cloneNode(true)));
      filter.value = allowed.includes(old) ? old : 'none';
      if (filter.value !== old) filter.dispatchEvent(new Event('change', {bubbles:true}));
      if (t === 'boolean') {
        source.value = 'manual';
        if (multiple) multiple.value = '0';
      }
      const selectedMulti = document.querySelector('[name="jform[filter_multiple]"][value="1"]');
      const selectedSingle = document.querySelector('[name="jform[filter_multiple]"][value="0"]');
      if (selectedMulti && selectedSingle) {
        if (['checkboxes','swatches'].includes(filter.value)) selectedMulti.checked=true;
        else if (!['values','dropdown'].includes(filter.value)) selectedSingle.checked=true;
      }
    };
    type.addEventListener('change',refresh);
    filter.addEventListener('change',refresh);
    refresh();
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',setup,{once:true});
  else setup();
})();
