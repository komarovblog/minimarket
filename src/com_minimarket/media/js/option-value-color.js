/* Optional native color picker for every order option value. The underlying
   HEX field remains nullable; image may be selected independently. */
(() => {
  'use strict';
  const form = document.querySelector('#adminForm');
  if (!form) return;
  const decorate = () => {
    for (const input of form.querySelectorAll('input[name^="jform[option_values]"][name$="[color_hex]"]')) {
      if (input.dataset.mmColorReady) continue;
      input.dataset.mmColorReady = '1';
      const tools = document.createElement('span');
      tools.className = 'mm-color-tools';
      const picker = document.createElement('input');
      picker.type = 'color';
      picker.className = 'form-control form-control-color';
      picker.setAttribute('aria-label', input.closest('.control-group')?.querySelector('.control-label')?.textContent?.trim() || 'HEX');
      const value = input.value.trim().toUpperCase();
      picker.value = /^#[0-9A-F]{6}$/.test(value) ? value : '#000000';
      picker.addEventListener('input', () => {
        input.value = picker.value.toUpperCase();
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
      const clear = document.createElement('button');
      clear.type = 'button';
      clear.className = 'btn btn-primary btn-sm';
      clear.textContent = '×';
      clear.title = window.Joomla?.Text?._('COM_MINIMARKET_HEX_CLEAR') || 'Clear color';
      clear.setAttribute('aria-label', clear.title);
      clear.addEventListener('click', () => {
        input.value = '';
        picker.value = '#000000';
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
      tools.append(picker, clear);
      input.insertAdjacentElement('afterend', tools);
      input.addEventListener('input', () => {
        const color = input.value.trim().toUpperCase();
        if (/^#[0-9A-F]{6}$/.test(color)) picker.value = color;
      });
    }
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', decorate, { once: true });
  } else decorate();
  new MutationObserver(records => {
    if (records.some(record => Array.from(record.addedNodes).some(n =>
      n.nodeType === 1 && (n.matches?.('.subform-repeatable-group') ||
        n.querySelector?.('input[name$="[color_hex]"]'))))) decorate();
  }).observe(form, { childList: true, subtree: true });
})();
