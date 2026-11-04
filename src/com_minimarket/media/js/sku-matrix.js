/* One direct SKU matrix + SKU-aware product gallery. No Joomla subform row-add
   dependency. All editable SKU fields are serialized into ONE validated input. */
(() => {
  'use strict';
  const root = document.querySelector('[data-mm-sku-matrix]');
  if (!root) return;
  const category = document.querySelector('[name="jform[category_id]"]');
  const form = root.closest('form');
  const mount = root.querySelector('[data-mm-matrix-table]');
  const payload = root.querySelector('[data-mm-matrix-payload]');
  const price = document.querySelector('[name="jform[price]"]');
  const stockModes = Array.from(document.querySelectorAll('[name="jform[option_stock_mode]"]'));
  let config = {}, labels = {};
  try {
    config = JSON.parse(root.dataset.mmConfig || '{}');
    labels = JSON.parse(root.dataset.mmMatrixLabels || '{}');
  } catch (_) {
    mount.textContent = 'Invalid option configuration';
    return;
  }
  const t = (key, fallback) => labels[key] || fallback;
  const el = (tag, cls = '', value = null) => {
    const node = document.createElement(tag);
    if (cls) node.className = cls;
    if (value !== null) node.textContent = String(value);
    return node;
  };
  const canonical = selection => JSON.stringify(Object.fromEntries(
    Object.entries(selection).sort(([a], [b]) => a < b ? -1 : a > b ? 1 : 0)
  ));
  const isShared = () => stockModes.find(x => x.checked)?.value === '0';
  const existing = new Map();
  for (const row of config.existingRows || []) {
    try {
      const selection = typeof row.selection === 'string'
        ? JSON.parse(row.selection) : row.selection;
      existing.set(canonical(selection), { ...row, selection });
    } catch (_) { /* Corrupt persisted rows are caught server-side on save. */ }
  }
  // Legacy/local SKU axes remain read-only at the dictionary level: they cannot
  // be silently replaced when category rules are changed.
  const hasExistingAxes = (config.existingAxes || []).length > 0;
  const axesForCategory = () => {
    if (hasExistingAxes) return config.existingAxes;
    const record = config.catalog?.[String(category?.value || '')];
    return record?.too_many ? null : (record?.axes || []);
  };
  let rows = [];
  let currentAxes = [];
  let currentCategory = null;

  const combinations = axes => {
    let list = [{ selection: {}, values: [] }];
    for (const axis of axes) {
      const next = [];
      for (const item of list) {
        for (const option of axis.values || []) {
          next.push({
            selection: { ...item.selection, [axis.code]: 'v:' + option.code },
            values: [...item.values, option.title],
          });
          if (next.length > 200) return null;
        }
      }
      list = next;
    }
    return list;
  };
  const input = (type, value, className) => {
    const node = el('input', className);
    node.type = type;
    if (type === 'checkbox') node.checked = Boolean(value);
    else node.value = value == null ? '' : String(value);
    return node;
  };
  const labelCell = (row, label, node) => {
    const cell = row.insertCell();
    cell.dataset.label = label;
    cell.appendChild(node);
    return cell;
  };
  const store = () => {
    payload.value = JSON.stringify(rows.map(row => ({
      id: Number(row.id || 0),
      selection: row.selection,
      sku: row.sku,
      stock: String(row.stock),
      state: row.active ? 1 : 0,
    })).filter(row =>
      row.id > 0 || row.state === 1 || row.sku.trim() !== '' ||
      (row.stock !== '' && row.stock !== '0')
    ));
  };
  const usableRows = () => rows.filter(row =>
    row.id > 0 || row.active || row.sku.trim() !== ''
  );
  const getChoices = () => usableRows().map(row => ({
    key: canonical(row.selection),
    label: (row.sku.trim() ? row.sku.trim() + ' — ' : '') +
      row.values.join(' / '),
  }));

  // Each gallery row contains one visibility mode and two sets of combination
  // keys. They are resolved to stable physical variant_id on the PHP side.
  const photoRows = () => Array.from(
    form?.querySelectorAll('input[name^="jform[gallery]"][name$="[path]"]') || []
  ).map(field => field.closest('.subform-repeatable-group')).filter(Boolean);
  const photoInput = (group, key) => Array.from(group.querySelectorAll('[name]'))
    .find(x => x.name?.endsWith('][' + key + ']') &&
      x.name.startsWith('jform[gallery]'));
  const parseKeys = field => {
    try {
      const value = JSON.parse(field?.value || '[]');
      return Array.isArray(value) ? value.filter(x => typeof x === 'string') : [];
    } catch (_) { return []; }
  };
  const createMulti = (key, label) => {
    const wrap = el('label', 'mm-photo-sku-list');
    wrap.appendChild(el('span', 'form-label small fw-semibold', label));
    const select = el('select', 'form-select form-select-sm');
    select.multiple = true;
    select.size = 4;
    select.dataset.mmPhotoSelect = key;
    wrap.appendChild(select);
    return wrap;
  };
  const decoratePhoto = group => {
    if (group.dataset.mmSkuPhotoReady) return;
    const mode = photoInput(group, 'visibility_mode');
    const targets = photoInput(group, 'show_keys');
    const covers = photoInput(group, 'cover_keys');
    if (!mode || !targets || !covers) return;
    group.dataset.mmSkuPhotoReady = '1';
    const wrap = el('div', 'mm-gallery-sku-assignments');
    const show = createMulti('show', t('photoShow', 'Show for SKUs'));
    const main = createMulti('cover', t('photoCover', 'Make primary for SKUs'));
    wrap.append(show, main);
    group.appendChild(wrap);
    const saveSelect = (selector, field) => {
      field.value = JSON.stringify(Array.from(selector.selectedOptions).map(x => x.value));
      field.dispatchEvent(new Event('change', { bubbles: true }));
    };
    show.querySelector('select').addEventListener('change', event => {
      saveSelect(event.currentTarget, targets);
    });
    main.querySelector('select').addEventListener('change', event => {
      saveSelect(event.currentTarget, covers);
    });
    mode.addEventListener('change', () => {
      show.hidden = mode.value !== '1';
    });
  };
  const refreshGallery = () => {
    const choices = getChoices();
    for (const group of photoRows()) {
      decoratePhoto(group);
      const mode = photoInput(group, 'visibility_mode');
      const values = [
        ['show', photoInput(group, 'show_keys')],
        ['cover', photoInput(group, 'cover_keys')],
      ];
      const controls = group.querySelector('[data-mm-sku-photo-ready]');
      // Data marker is on group; controls are always inside it.
      if (!group.dataset.mmSkuPhotoReady) continue;
      for (const [name, field] of values) {
        const select = group.querySelector('[data-mm-photo-select="' + name + '"]');
        if (!select || !field) continue;
        const selected = new Set(parseKeys(field));
        select.replaceChildren();
        for (const item of choices) {
          const option = el('option', '', item.label);
          option.value = item.key;
          option.selected = selected.has(item.key);
          select.appendChild(option);
        }
      }
      const show = group.querySelector('.mm-photo-sku-list');
      if (show && mode) show.hidden = mode.value !== '1';
    }
  };
  const paint = () => {
    mount.replaceChildren();
    if (!currentAxes?.length) {
      mount.appendChild(el('p', 'text-muted', t('empty',
        'Assign order options to the selected category.')));
      rows = [];
      store();
      refreshGallery();
      return;
    }
    const combinationsList = combinations(currentAxes);
    if (!combinationsList) {
      mount.appendChild(el('p', 'text-danger', t('limit',
        'Maximum 200 combinations.')));
      rows = [];
      store();
      return;
    }
    const existingKeys = new Set(combinationsList.map(item => canonical(item.selection)));
    if ([...existing.keys()].some(key => !existingKeys.has(key))) {
      mount.appendChild(el('p', 'text-danger',
        t('invalidExisting', 'Saved SKUs require repair before editing.')));
      return; // Do not post a partial matrix and accidentally delete SKU rows.
    }
    const table = el('table', 'table table-striped table-sm align-middle mm-sku-matrix');
    const head = table.createTHead().insertRow();
    for (const axis of currentAxes) {
      const th = el('th', '', axis.title);
      th.scope = 'col';
      head.appendChild(th);
    }
    for (const name of [
      t('sku', 'SKU'), t('stock', 'Stock'), t('active', 'Active')
    ]) {
      const th = el('th', '', name);
      th.scope = 'col';
      if (name === t('stock', 'Stock')) th.dataset.mmStockColumn = '1';
      head.appendChild(th);
    }
    const body = table.createTBody();
    rows = [];
    for (const item of combinationsList) {
      const saved = existing.get(canonical(item.selection));
      const model = {
        id: Number(saved?.id || 0), selection: item.selection, values: item.values,
        sku: String(saved?.sku || ''), stock: String(saved?.stock ?? 0),
        active: Number(saved?.state || 0) === 1,
      };
      rows.push(model);
      const tr = body.insertRow();
      for (let i = 0; i < item.values.length; i++) {
        labelCell(tr, currentAxes[i].title, el('span', '', item.values[i]));
      }
      const sku = input('text', model.sku, 'form-control form-control-sm');
      sku.maxLength = 100;
      sku.setAttribute('aria-label', t('sku', 'SKU') + ': ' + item.values.join(' / '));
      sku.addEventListener('input', () => {
        model.sku = sku.value;
        store();
        refreshGallery();
      });
      labelCell(tr, t('sku', 'SKU'), sku);
      const stock = input('number', model.stock, 'form-control form-control-sm');
      stock.min = '0'; stock.max = '4294967295'; stock.step = '1';
      stock.setAttribute('aria-label', t('stock', 'Stock') + ': ' + item.values.join(' / '));
      stock.addEventListener('input', () => {
        model.stock = stock.value;
        store();
        refreshGallery();
      });
      const stockCell = labelCell(tr, t('stock', 'Stock'), stock);
      stockCell.dataset.mmStockColumn = '1';
      const enabled = input('checkbox', model.active, 'form-check-input');
      enabled.setAttribute('aria-label', t('active', 'Active') + ': ' + item.values.join(' / '));
      enabled.addEventListener('change', () => {
        model.active = enabled.checked;
        store();
        refreshGallery();
      });
      labelCell(tr, t('active', 'Active'), enabled);
    }
    mount.appendChild(table);
    const note = el('p', 'small text-muted', t('noVariants',
      'Only activated rows, saved rows or rows with an SKU are stored.'));
    mount.appendChild(note);
    setStockMode();
    store();
    refreshGallery();
  };
  const setStockMode = () => {
    for (const field of mount.querySelectorAll('[data-mm-stock-column]')) {
      field.hidden = isShared();
    }
  };
  const reload = () => {
    const cat = category?.value || '';
    if (currentCategory === cat && currentAxes.length) return;
    currentCategory = cat;
    currentAxes = axesForCategory() || [];
    paint();
  };
  category?.addEventListener('change', reload);
  stockModes.forEach(x => x.addEventListener('change', () => {
    setStockMode();
    store();
  }));
  // Once attached, new Joomla gallery subform rows are initialized lazily.
  const gallery = document.querySelector('[name="jform[gallery_present]"]')?.closest('.control-group')
    || document.querySelector('#adminForm');
  let pending = false;
  gallery?.addEventListener('click', () => {
    if (pending) return;
    pending = true;
    requestAnimationFrame(() => { pending = false; refreshGallery(); });
  });
  new MutationObserver(records => {
    if (records.some(record => Array.from(record.addedNodes).some(node =>
      node.nodeType === 1 && (node.matches?.('joomla-field-subform, .subform-repeatable-group') ||
        node.querySelector?.('input[name^="jform[gallery]"]'))))) {
      refreshGallery();
    }
  }).observe(document.querySelector('#adminForm'), { childList: true, subtree: true });
  // Joomla toolbar submission may call form.submit() without a submit event.
  // Always synchronize after every edit as well as on normal submit.
  form?.addEventListener('submit', store);
  reload();
})();
