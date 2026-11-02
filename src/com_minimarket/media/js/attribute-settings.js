/* MiniMarket attribute editor: show only compatible input/filter controls.
 * Server enforces the same allow-lists; JS is purely a convenience.
 */
(() => {
  'use strict';
  const product = {
    list: ['auto','dropdown','radio','checkboxes'],
    number: ['auto','number','presets'],
    text: ['auto'], textarea: ['auto'], date: ['auto'],
    boolean: ['auto'], url: ['auto']
  };
  const filters = {
    list: ['none','checkboxes','radio','dropdown'],
    number: ['none','range','slider','checkboxes','dropdown'],
    text: ['none','text','values'],
    textarea: ['none'], date: ['none','date_range'],
    boolean: ['none','boolean'], url: ['none']
  };
  const setup = () => {
    const type = document.getElementById('jform_input_type');
    const mode = document.getElementById('jform_input_mode');
    const filter = document.getElementById('jform_filter_mode');
    const multi = document.getElementById('jform_is_multiple');
    const variant = document.querySelector('[name="jform[is_variant]"]:checked');
    if (!type || !mode || !filter) return;

    const updateOptions = (field, accepted, fallback) => {
      for (const option of field.options) {
        const allowed = accepted.includes(option.value);
        option.hidden = !allowed;
        option.disabled = !allowed;
      }
      if (!accepted.includes(field.value)) {
        field.value = fallback;
        field.dispatchEvent(new Event('change', { bubbles: true }));
      }
    };
    let busy = false;
    const refresh = () => {
      if (busy) return;
      busy = true;
      try {
        const t = type.value;
        updateOptions(mode, product[t] || ['auto'], 'auto');
        updateOptions(filter, filters[t] || ['none'], 'none');
        if (t === 'list') {
          if (mode.value === 'radio' || mode.value === 'checkboxes') {
            if (multi) {
              multi.value = mode.value === 'checkboxes' ? '1' : '0';
              multi.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }
        } else {
          if (multi) {
            multi.value = '0';
            multi.dispatchEvent(new Event('change', { bubbles: true }));
          }
          const enabledVariant = document.querySelector('[name="jform[is_variant]"][value="1"]');
          const disabledVariant = document.querySelector('[name="jform[is_variant]"][value="0"]');
          if (enabledVariant?.checked && disabledVariant) {
            disabledVariant.checked = true;
            disabledVariant.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }
        const filterMulti = document.querySelector('[name="jform[filter_multiple]"][value="1"]');
        const filterSingle = document.querySelector('[name="jform[filter_multiple]"][value="0"]');
        if (filterMulti && filterSingle) {
          if (filter.value === 'checkboxes') {
            filterMulti.checked = true;
            filterMulti.dispatchEvent(new Event('change', { bubbles: true }));
          } else if (!['values', 'dropdown'].includes(filter.value)) {
            filterSingle.checked = true;
            filterSingle.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }
      } finally {
        busy = false;
      }
    };
    type.addEventListener('change', refresh);
    mode.addEventListener('change', refresh);
    filter.addEventListener('change', refresh);
    refresh();
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup, { once: true });
  } else {
    setup();
  }
})();
