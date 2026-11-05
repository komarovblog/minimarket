/* Search, add and remove collections or bundle components without a huge select. */
(() => {
  'use strict';
  const node = (tag, cls, text) => {
    const item = document.createElement(tag);
    if (cls) item.className = cls;
    if (text !== undefined) item.textContent = text;
    return item;
  };
  const itemLabel = item => item.sku ? item.title + ' · ' + item.sku : item.title;
  function mount(root) {
    const mode = root.dataset.mmPicker;
    const words = JSON.parse(root.dataset.mmLabels || '{}');
    const selected = root.querySelector('[data-mm-selected]');
    const finder = root.querySelector('[data-mm-finder]');
    const results = root.querySelector('[data-mm-results]');
    const status = root.querySelector('[data-mm-message]');
    const searchInput = root.querySelector('[data-mm-query]');
    const category = root.querySelector('[data-mm-category]');
    const baseName = root.dataset.mmInput;
    if (!selected || !finder || !results || !searchInput || !baseName) return;
    let next = selected.querySelectorAll('[data-mm-key]').length;
    let pending = null;
    let request = 0;
    const existing = key => Array.from(selected.querySelectorAll('[data-mm-key]'))
      .some(entry => entry.dataset.mmKey === String(key));
    selected.addEventListener('click', event => {
      const button = event.target.closest('[data-mm-remove]');
      if (button) { event.preventDefault(); button.closest('[data-mm-key]')?.remove(); }
    });
    function append(item) {
      if (existing(item.key)) return;
      if (selected.querySelectorAll('[data-mm-key]').length >= (mode === 'bundle' ? 40 : 250)) {
        status.textContent = (words.limit || 'Maximum selections reached');
        return;
      }
      const row = node('div', 'mm-entity-picker__row');
      row.dataset.mmKey = item.key;
      const link = node('a', '', item.title);
      const targetId = Number(item.product_id || item.id);
      link.href = 'index.php?option=com_minimarket&task='
        + (mode === 'bundle' ? 'product' : 'collection') + '.edit&id=' + targetId;
      row.append(link);
      if (item.sku) row.append(node('small', '', item.sku));
      const hidden = node('input');
      hidden.type = 'hidden';
      if (mode === 'bundle') {
        const prefix = baseName + '[row' + next++ + ']';
        hidden.name = prefix + '[component]';
        hidden.value = item.key;
        row.append(hidden);
        const label = node('label', '', (words.quantity || 'Quantity'));
        const qty = node('input', 'form-control form-control-sm');
        qty.type = 'number'; qty.min = '1'; qty.max = '1000';
        qty.step = '1'; qty.required = true; qty.value = '1';
        qty.name = prefix + '[quantity]';
        label.append(qty);
        row.append(label);
      } else {
        hidden.name = baseName + '[]';
        hidden.value = item.key;
        row.append(hidden);
      }
      const remove = node('button', 'btn btn-danger btn-sm', '×');
      remove.type = 'button'; remove.dataset.mmRemove = '';
      remove.setAttribute('aria-label', (words.remove || 'Remove'));
      row.append(remove);
      selected.append(row);
      status.textContent = '';
    }
    async function search() {
      const id = ++request;
      if (pending) pending.abort();
      pending = new AbortController();
      status.textContent = '';
      results.replaceChildren(node('p', 'text-muted', (words.loading || 'Searching…')));
      const url = new URL(root.dataset.mmUrl, window.location.href);
      url.searchParams.set('q', searchInput.value.slice(0, 100));
      if (category) url.searchParams.set('category_id', category.value);
      if (mode === 'bundle') url.searchParams.set('current_id', root.dataset.mmCurrent || '0');
      try {
        const response = await fetch(url.toString(), {
          method:'GET', credentials:'same-origin', headers:{Accept:'application/json'},
          signal:pending.signal
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const payload = await response.json();
        if (id !== request) return;
        results.replaceChildren();
        const items = Array.isArray(payload.items) ? payload.items : [];
        if (!items.length) { results.append(node('p','text-muted',(words.empty || 'No results'))); return; }
        items.forEach(item => {
          if (typeof item.key !== 'string' || !/^(product:|variant:)?[1-9]\d*$/.test(item.key) || existing(item.key)) return;
          const row = node('div', 'mm-entity-picker__result');
          row.append(node('span', '', itemLabel(item)));
          const add = node('button', 'btn btn-success btn-sm', '+');
          add.type = 'button'; add.title = 'Добавить';
          add.setAttribute('aria-label', 'Добавить ' + itemLabel(item));
          add.addEventListener('click', () => { append(item); row.remove(); });
          row.append(add); results.append(row);
        });
        if (!results.children.length) results.append(node('p','text-muted',(words.all || 'All results already added')));
      } catch (error) {
        if (error.name !== 'AbortError') {
          results.replaceChildren();
          status.textContent = (words.error || 'Search failed. Please retry.');
        }
      }
    }
    root.querySelector('[data-mm-add]')?.addEventListener('click', () => {
      finder.hidden = !finder.hidden;
      if (!finder.hidden) { searchInput.focus(); search(); }
    });
    root.querySelector('[data-mm-search]')?.addEventListener('click',search);
    searchInput.addEventListener('keydown', event => {
      if (event.key === 'Enter') { event.preventDefault(); search(); }
    });
    category?.addEventListener('change', search);
  }
  document.addEventListener('DOMContentLoaded', () =>
    document.querySelectorAll('[data-mm-picker]').forEach(mount)
  );
})();
