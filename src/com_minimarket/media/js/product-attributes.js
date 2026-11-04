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
      const hasValue = Array.from(row.querySelectorAll('input,select,textarea')).some((input) => {
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
  // Repeated manual entries do not depend on deprecated preset CSV fields.
  // One '+' inserts one independently validated scalar (including numbers).
  document.addEventListener('click', (event) => {
    const add = event.target.closest('[data-mm-free-add]');
    const remove = event.target.closest('[data-mm-free-remove]');
    if (remove) {
      remove.closest('[data-mm-free-entry]')?.remove();
      refresh();
      return;
    }
    if (!add) return;
    const list = add.closest('[data-mm-free-list]');
    if (!list || list.querySelectorAll('[data-mm-free-entry]').length >= 100) return;
    const type = list.dataset.mmFreeType;
    const row = document.createElement('div');
    row.className = 'd-flex gap-2 align-items-start mb-2';
    row.dataset.mmFreeEntry = '';
    const field = document.createElement(type === 'editor' ? 'textarea' : 'input');
    if (type === 'editor') {
      field.rows = 3;
    } else {
      field.type = type === 'number' ? 'number' : type === 'color' ? 'color' : 'text';
      if (type === 'number') {
        field.step = list.dataset.mmFreeStep || '0.0001';
        if (list.dataset.mmFreeMin) field.min = list.dataset.mmFreeMin;
        if (list.dataset.mmFreeMax) field.max = list.dataset.mmFreeMax;
      }
      if (type === 'color') field.value = '#000000';
    }
    field.name = list.dataset.mmFreeName;
    field.className = 'form-control';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-danger';
    button.dataset.mmFreeRemove = '';
    button.textContent = add.dataset.mmRemoveLabel || 'Remove';
    row.append(field, button);
    list.insertBefore(row, add);
    field.focus();
    refresh();
  });
  // Native palette and optional HEX text input must always agree.
  document.addEventListener('input', (event) => {
    const pair = event.target.closest('[data-mm-color-pair]');
    if (!pair) return;
    const picker = pair.querySelector('[data-mm-color-picker]');
    const text = pair.querySelector('[data-mm-color-text]');
    if (!picker || !text) return;
    if (event.target === picker) text.value = picker.value.toUpperCase();
    else if (/^#[0-9a-fA-F]{6}$/.test(text.value)) picker.value = text.value;
  });
  document.addEventListener('change', (event) => {
    if (event.target.matches('[name="jform[category_id]"]')
        || event.target.closest('[data-mm-product-attributes]')) refresh();
  });
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refresh, { once: true });
  } else { refresh(); }
})();
