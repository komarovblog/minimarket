/* Native Joomla repeatable variants; enhances each row with validated axis selectors. */
(() => {
  'use strict';
  const info = document.querySelector('[data-mm-variant-config]');
  if (!info) return;
  let configs = {};
  try { configs = JSON.parse(info.dataset.mmVariantConfig || '{}'); } catch (_) {}
  const category = document.querySelector('[name="jform[category_id]"]');
  const warning = info.querySelector('[data-mm-variant-config-warning]');
  const isSelection = (el) => el.matches('input[type="hidden"][name*="[variants]"][name$="[selection]"]');
  // This wrapper is created by the MiniMarket template, not Joomla core.
  const editor = info.closest('[data-mm-variants-editor]') || info.parentElement;
  const setupLink = info.querySelector('[data-mm-variant-setup-link]');
  const variantForm = editor?.querySelector('.subform-repeatable');
  const outerAddButtons = () => variantForm
    ? Array.from(variantForm.querySelectorAll('button.group-add, a.group-add'))
      .filter((button) => button.closest('.subform-repeatable') === variantForm)
    : [];
  const optionsFor = () => (configs[String(category?.value || '')] || {});
  const redraw = () => {
    const axes = optionsFor();
    const axisEntries = Object.entries(axes);
    const canAdd = axisEntries.length >= 1 && axisEntries.length <= 4;
    for (const button of outerAddButtons()) {
      // Do not disable the nested photo-gallery Add controls.
      button.disabled = !canAdd;
      button.setAttribute('aria-disabled', String(!canAdd));
      if (!canAdd) button.setAttribute('title', info.dataset.mmNoAxes || '');
      else button.removeAttribute('title');
    }
    if (setupLink) setupLink.hidden = canAdd;
    if (warning) {
      const message = axisEntries.length === 0
        ? (info.dataset.mmNoAxes || '')
        : axisEntries.length > 4
          ? (info.dataset.mmTooManyAxes || '')
          : '';
      // Reassigning textContent creates a DOM mutation even if text is
      // identical; do not recursively wake our own MutationObserver.
      if (warning.textContent !== message) warning.textContent = message;
    }
    document.querySelectorAll('input[type="hidden"][name*="[variants]"][name$="[selection]"]')
      .forEach((hidden) => {
        // Native Subform inserts these inputs both initially and on + Add.
        let old = {};
        try { old = JSON.parse(hidden.value || '{}') || {}; } catch (_) {}
        let holder = hidden.parentElement?.querySelector('[data-mm-variant-choices]');
        if (!holder) {
          holder = document.createElement('div');
          holder.dataset.mmVariantChoices = '1';
          holder.className = 'mb-2';
          hidden.parentElement?.insertBefore(holder, hidden);
        }
        // Do not reset an in-progress selection on unrelated subform mutations.
        const key = JSON.stringify(axisEntries.map(([id, a]) => [id, a.options.map((o) => o.value)]));
        if (holder.dataset.mmConfigKey === key) return;
        holder.dataset.mmConfigKey = key;
        holder.replaceChildren();
        const stored = {};
        axisEntries.forEach(([attrId, axis]) => {
          const label = document.createElement('label');
          label.className = 'form-label small fw-semibold d-block mb-1';
          label.textContent = axis.title;
          const select = document.createElement('select');
          select.className = 'form-select form-select-sm mb-2';
          select.setAttribute('aria-label', axis.title);
          select.dataset.mmVariantAxis = attrId;
          const blank = document.createElement('option');
          blank.value = ''; blank.textContent = '—';
          select.appendChild(blank);
          axis.options.forEach((option) => {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.title;
            select.appendChild(opt);
          });
          if (typeof old[attrId] === 'string') select.value = old[attrId];
          if (!select.value && old[attrId]) {
            const unavailable = document.createElement('option');
            unavailable.value = old[attrId];
            unavailable.textContent = old[attrId] + ' (unavailable)';
            unavailable.selected = true;
            select.appendChild(unavailable);
          }
          if (select.value) stored[attrId] = select.value;
          const sync = () => {
            const next = {};
            holder.querySelectorAll('select[data-mm-variant-axis]').forEach((s) => {
              if (s.value) next[s.dataset.mmVariantAxis] = s.value;
            });
            hidden.value = JSON.stringify(next);
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
          };
          select.addEventListener('change', sync);
          label.appendChild(select);
          holder.appendChild(label);
        });
        hidden.value = JSON.stringify(stored);
      });
  };
  const root = document.querySelector('#adminForm') || document;
  const selectionSelector = 'input[type="hidden"][name*="[variants]"][name$="[selection]"]';
  // Joomla inserts new subform rows dynamically. Observe only additions
  // containing the relevant field; ignore DOM edits made by this script,
  // Joomla toolbars, editors and the rest of the admin form.
  const observer = new MutationObserver((records) => {
    const hasNewVariantRow = records.some(({ addedNodes }) =>
      Array.from(addedNodes).some((node) =>
        node.nodeType === 1 && (
          isSelection(node) || !!node.querySelector?.(selectionSelector)
        )
      )
    );
    if (hasNewVariantRow) redraw();
  });
  observer.observe(root, { childList: true, subtree: true });
  // The native Joomla subform may emit its own event after injecting a row.
  document.addEventListener('joomla:updated', (event) => {
    if (editor?.contains(event.target)) redraw();
  });
  category?.addEventListener('change', redraw);
  redraw();
})();
