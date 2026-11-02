/* MiniMarket attribute editor: show only compatible input/filter controls.
 * Server enforces the same allow-lists; JS is purely a convenience.
 */
(() => {
  'use strict';
  const product = {
    list: ['auto','dropdown','radio','checkboxes'],
    number: ['auto','number','presets'],
    text: ['auto','presets'], editor: ['auto'], document: ['auto'],
    color: ['auto','dropdown','radio','checkboxes'], textarea: ['auto'], date: ['auto'],
    boolean: ['auto'], url: ['auto']
  };
  const filters = {
    list: ['none','checkboxes','radio','dropdown'],
    number: ['none','range','slider','checkboxes','dropdown'],
    text: ['none','text','values','checkboxes','dropdown'], editor: ['none'], document: ['none'],
    color: ['none','swatches','checkboxes','radio','dropdown'],
    textarea: ['none'], date: ['none','date_range'],
    boolean: ['none','boolean'], url: ['none']
  };
  const setup = () => {
    const type = document.getElementById('jform_input_type');
    const mode = document.getElementById('jform_input_mode');
    const filter = document.getElementById('jform_filter_mode');
    const multi = document.getElementById('jform_is_multiple');
    // The variant flag supports List, Color and Text with predefined choices.
    const multiGroup = multi?.closest('.control-group, .mb-3');
    if (!type || !mode || !filter) return;
    // Pre-existing List / URL / Date / Textarea remain editable; only NEW
    // attributes are limited to Text, Number, HTML editor, Yes/No.
    const isNew = document.getElementById('jform_id')?.value === '0'
      || !document.getElementById('jform_id')?.value;
    if (isNew) {
      for (const option of type.options) {
        if (!['text','number','editor','boolean','document','color'].includes(option.value)) {
          option.hidden = true;
          option.disabled = true;
        }
      }
      if (type.selectedOptions[0]?.disabled) {
        type.value = 'text';
      }
    }

    const attributeId = document.getElementById('jform_id');
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
        if (multiGroup) {
          multiGroup.hidden = t === 'text' && mode.value !== 'presets';
        }
        updateOptions(mode, product[t] || ['auto'], 'auto');
        // Legacy types remain selectable only when editing the same old kind.
        const legacy = ['list','textarea','date','url'];
        for (const option of type.options) {
          if (legacy.includes(option.value)) {
            const allow = Number(attributeId?.value || 0) > 0 && option.value === t;
            option.hidden = !allow;
            option.disabled = !allow;
          }
        }
        updateOptions(filter, filters[t] || ['none'], 'none');
        // Long descriptive text and URLs are not faceted catalog properties.
        if (t === 'editor' || t === 'document' || t === 'textarea' || t === 'url') {
          const enabledFilter = document.querySelector('[name="jform[is_filterable]"][value="1"]');
          const disabledFilter = document.querySelector('[name="jform[is_filterable]"][value="0"]');
          if (enabledFilter?.checked && disabledFilter) {
            disabledFilter.checked = true;
            disabledFilter.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }
        if (t === 'list' || t === 'color' || (t === 'text' && mode.value === 'presets')) {
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
          if (filter.value === 'checkboxes' || filter.value === 'swatches') {
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
