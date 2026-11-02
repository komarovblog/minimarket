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
  const optionsFor = () => (configs[String(category?.value || '')] || {});
  const redraw = () => {
    const axes = optionsFor();
    const axisEntries = Object.entries(axes);
    if (warning) {
      warning.textContent = axisEntries.length === 0
        ? (document.documentElement.lang?.startsWith('ru')
          ? 'Назначьте категории характеристику «Различает варианты товара» со значениями.'
          : 'Assign a variant-defining attribute with predefined values to this category.')
        : axisEntries.length > 4
          ? (document.documentElement.lang?.startsWith('ru')
            ? 'Поддерживается не более четырёх характеристик вариантов.'
            : 'A maximum of four variant-defining attributes is supported.')
          : '';
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
  const observer = new MutationObserver(() => redraw());
  observer.observe(root, { childList: true, subtree: true });
  category?.addEventListener('change', redraw);
  redraw();
})();
