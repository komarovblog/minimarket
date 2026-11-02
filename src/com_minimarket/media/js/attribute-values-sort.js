/* Sort the values directly inside the attribute editor.
 * Alphabetical sorting is a view only. Manual order is saved when the
 * administrator clicks Joomla's regular Save or Apply button.
 */
(() => {
  'use strict';

  const init = () => {
    const select = document.querySelector('[data-mm-values-sort]');
    const tbody = document.querySelector('[data-mm-values-body]');
    if (!select || !tbody) return;

    const rows = [...tbody.querySelectorAll('[data-mm-value-row]')];
    const key = (row) => {
      const input = row.querySelector('[data-mm-value-order]');
      return Number(input ? input.value : 0);
    };
    const sort = () => {
      const mode = select.value;
      rows.sort((left, right) => {
        let compare = 0;
        if (mode === 'az' || mode === 'za') {
          compare = (left.dataset.mmValueTitle || '').localeCompare(
            right.dataset.mmValueTitle || '', undefined,
            { sensitivity: 'base', numeric: true }
          );
          if (mode === 'za') compare = -compare;
        } else {
          compare = key(left) - key(right);
        }
        return compare || Number(left.dataset.mmValueId) - Number(right.dataset.mmValueId);
      });
      rows.forEach((row) => tbody.appendChild(row));
    };

    select.addEventListener('change', sort);
    tbody.addEventListener('change', (event) => {
      if (event.target.matches('[data-mm-value-order]') && select.value === 'order') {
        sort();
      }
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
