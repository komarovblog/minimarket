/*
 * All MiniMarket media selectors show only images/relative-file.ext.
 * Joomla Media appends #joomlaImage://...width=...&height=... on selection;
 * the extension does not use that adapter metadata or those source dimensions.
 *
 * The database write boundary also normalizes/validates this field. This
 * browser convenience is not a substitute for the PHP validation.
 */
(() => {
  'use strict';

  const selector = 'joomla-field-media input[name="jform[image]"], ' +
    'joomla-field-media input[name="jform[og_image]"], ' +
    'joomla-field-media input[name^="jform[gallery]"][name$="[path]"]';
  const marker = '#joomlaImage://';

  function clean(input) {
    if (!input || typeof input.value !== 'string') {
      return;
    }
    const index = input.value.indexOf(marker);
    if (index === -1) {
      return;
    }
    const path = input.value.substring(0, index);
    const field = input.closest('joomla-field-media');
    if (field && typeof field.setValue === 'function') {
      // Use Joomla's own component API: keeps the preview and internal state
      // synchronized rather than just changing an unrelated DOM property.
      field.setValue(path);
    } else {
      input.value = path;
    }
  }

  const cleanAll = () => document.querySelectorAll(selector).forEach(clean);

  function handleChange(event) {
    const input = event.target;
    if (input && typeof input.matches === 'function' && input.matches(selector)) {
      clean(input);
    }
  }

  document.addEventListener('input', handleChange, true);
  document.addEventListener('change', handleChange, true);
  document.addEventListener('joomla:updated', cleanAll);
  document.addEventListener('submit', cleanAll, true);
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cleanAll, { once: true });
  } else {
    cleanAll();
  }

  // Some older Joomla Media widgets update the input after a modal event,
  // without dispatching a native change event. Clean once on the next tick.
  const afterMediaSelect = () => setTimeout(cleanAll, 0);
  document.addEventListener('onMediaFileSelected', afterMediaSelect);
  window.addEventListener('onMediaFileSelected', afterMediaSelect);
})();
