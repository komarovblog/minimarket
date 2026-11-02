/* Change visible product attribute inputs when primary category changes.
 * Actual validation is enforced server-side against category inheritance.
 */
(() => {
  'use strict';
  const refresh = () => {
    const root = document.querySelector('[data-mm-product-attributes]');
    const category = document.querySelector('[name="jform[category_id]"]');
    if (!root || !category) return;
    let map = {};
    try { map = JSON.parse(root.dataset.mmCategoryMap || '{}'); } catch (_) {}
    let requiredMap = {};
    try { requiredMap = JSON.parse(root.dataset.mmCategoryRequired || '{}'); } catch (_) {}
    const allowed = new Set((map[category.value] || []).map(String));
    const required = new Set((requiredMap[category.value] || []).map(String));
    let count = 0;
    for (const row of root.querySelectorAll('[data-mm-attribute-row]')) {
      const valid = allowed.has(row.dataset.mmAttributeRow);
      const hasValue = Array.from(row.querySelectorAll('input,select')).some((input) => {
        if (input.type === 'checkbox') return input.checked;
        return input.value !== '';
      });
      // Keep a filled but no longer applicable attribute visible so the
      // administrator can clear it; PHP will reject it unless cleared.
      row.classList.toggle('d-none', !valid && !hasValue);
      const marker = row.querySelector('[data-mm-required-indicator]');
      if (marker) marker.classList.toggle('d-none', !valid || !required.has(row.dataset.mmAttributeRow));
      const warning = row.querySelector('[data-mm-attribute-incompatible]');
      if (warning) warning.classList.toggle('d-none', valid);
      if (valid) count++;
    }
    const empty = root.querySelector('[data-mm-attr-empty]');
    if (empty) empty.classList.toggle('d-none', count !== 0);
  };
  document.addEventListener('change', (event) => {
    if (event.target.matches('[name="jform[category_id]"]')
        || event.target.closest('[data-mm-product-attributes]')) refresh();
  });
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refresh, { once: true });
  } else { refresh(); }
})();
