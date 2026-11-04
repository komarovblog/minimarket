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
  const getChoices = () => rows.filter(row => row.active || row.id > 0)
    .map(row => ({
      key: canonical(row.selection),
      label: (row.sku.trim() ? row.sku.trim() + ' — ' : '') +
        row.values.join(' / ') +
        (!row.active ? ' (' + t('inactive', 'Inactive SKU') + ')' : ''),
      active: row.active,
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
  // Gallery photo controls: two clear sections under each additional image.
  // Lists use checkboxes, not a tall multi-select that requires Ctrl+click.
  let photoNumber = 0;
  const decoratePhoto = group => {
    if (group.dataset.mmSkuPhotoReady) return;
    const mode = photoInput(group, 'visibility_mode');
    const targets = photoInput(group, 'show_keys');
    const covers = photoInput(group, 'cover_keys');
    if (!mode || !targets || !covers) return;
    group.dataset.mmSkuPhotoReady = '1';
    const id = ++photoNumber;
    group.dataset.mmPhotoId = String(id);
    const panels = el('div', 'mm-gallery-sku-assignments');
    const visibility = el('section', 'mm-photo-sku-block');
    visibility.dataset.mmPhotoVisibility = '1';
    const cover = el('section', 'mm-photo-sku-block');
    cover.dataset.mmPhotoCover = '1';
    panels.append(visibility, cover);
    group.appendChild(panels);
  };
  const refreshGallery = () => {
    const choices = getChoices();
    const groups = photoRows();
    groups.forEach(decoratePhoto);
    const owners = new Map();
    for (const group of groups) {
      const field = photoInput(group, 'cover_keys');
      for (const key of parseKeys(field)) {
        if (!owners.has(key)) owners.set(key, group);
      }
    }
    const persist = (field, set) => {
      field.value = JSON.stringify([...set]);
      field.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const choiceGrid = (group, field, kind, canCheck) => {
      const grid = el('div', 'mm-photo-sku-checklist');
      grid.dataset.mmPhotoSelect = kind;
      const selected = new Set(parseKeys(field));
      if (!choices.length) {
        grid.appendChild(el('p', 'small text-muted',
          t('noActive', 'Enable SKU combinations in Options first.')));
      }
      for (const choice of choices) {
        const checked = selected.has(choice.key);
        if (!choice.active && !checked) continue;
        const label = el('label', 'mm-photo-sku-choice');
        const checkbox = el('input', 'form-check-input');
        checkbox.type = 'checkbox';
        checkbox.value = choice.key;
        checkbox.checked = checked;
        const blocked = kind === 'cover' && owners.has(choice.key) &&
          owners.get(choice.key) !== group;
        checkbox.disabled = blocked || (!choice.active && !checked);
        checkbox.addEventListener('change', () => {
          if (checkbox.checked) selected.add(choice.key);
          else selected.delete(choice.key);
          persist(field, selected);
          // A photo marked as primary must also be visible for that SKU.
          if (kind === 'cover' && checkbox.checked &&
              photoInput(group, 'visibility_mode')?.value === '1') {
            const shownField = photoInput(group, 'show_keys');
            const shown = new Set(parseKeys(shownField));
            shown.add(choice.key);
            persist(shownField, shown);
          }
          if (kind === 'show' && !checkbox.checked) {
            const primaryField = photoInput(group, 'cover_keys');
            const primary = new Set(parseKeys(primaryField));
            primary.delete(choice.key);
            persist(primaryField, primary);
          }
          refreshGallery();
        });
        const description = el('span', '', choice.label);
        label.append(checkbox, description);
        grid.appendChild(label);
      }
      return grid;
    };
    for (const group of groups) {
      if (!group.dataset.mmSkuPhotoReady) continue;
      const mode = photoInput(group, 'visibility_mode');
      const showField = photoInput(group, 'show_keys');
      const coverField = photoInput(group, 'cover_keys');
      const visibility = group.querySelector('[data-mm-photo-visibility]');
      const cover = group.querySelector('[data-mm-photo-cover]');
      if (!mode || !showField || !coverField || !visibility || !cover) continue;
      visibility.replaceChildren();
      visibility.appendChild(el('strong', 'd-block mb-2',
        t('photoVisibility', 'Show photo')));
      const radios = el('div', 'mm-photo-sku-radios');
      for (const [value, title] of [
        ['0', t('everywhere', 'For every SKU')],
        ['1', t('selected', 'Only selected SKUs')],
      ]) {
        const label = el('label', 'mm-photo-sku-choice');
        const radio = el('input', 'form-check-input');
        radio.type = 'radio';
        radio.name = 'mm-gallery-visibility-' + group.dataset.mmPhotoId;
        radio.value = value;
        radio.checked = mode.value === value;
        radio.addEventListener('change', () => {
          mode.value = value;
          if (value === '1') {
            const selected = new Set(parseKeys(showField));
            for (const key of parseKeys(coverField)) selected.add(key);
            persist(showField, selected);
          }
          refreshGallery();
        });
        label.append(radio, el('span', '', title));
        radios.appendChild(label);
      }
      visibility.appendChild(radios);
      if (mode.value === '1') {
        visibility.appendChild(choiceGrid(group, showField, 'show'));
      }
      cover.replaceChildren();
      cover.appendChild(el('strong', 'd-block mb-2',
        t('photoCover', 'Make main photo for SKUs')));
      cover.appendChild(el('p', 'small text-muted',
        t('coverHelp', 'Each SKU can have only one main photo.')));
      cover.appendChild(choiceGrid(group, coverField, 'cover'));
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
      if (name === t('stock', 'Stock')) {
        th.dataset.mmStockColumn = '1';
        th.dataset.mmPinned = 'stock';
      } else if (name === t('sku', 'SKU')) th.dataset.mmPinned = 'sku';
      else th.dataset.mmPinned = 'active';
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
      labelCell(tr, t('sku', 'SKU'), sku).dataset.mmPinned = 'sku';
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
      stockCell.dataset.mmPinned = 'stock';
      const enabled = input('checkbox', model.active, 'form-check-input');
      enabled.setAttribute('aria-label', t('active', 'Active') + ': ' + item.values.join(' / '));
      enabled.addEventListener('change', () => {
        model.active = enabled.checked;
        store();
        refreshGallery();
      });
      labelCell(tr, t('active', 'Active'), enabled).dataset.mmPinned = 'active';
    }
    const viewport = el('div', 'mm-sku-viewport');
    viewport.setAttribute('role', 'region');
    viewport.setAttribute('aria-label', t('matrix', 'SKU combinations'));
    viewport.tabIndex = 0;
    viewport.appendChild(table);
    mount.appendChild(viewport);
    const note = el('p', 'small text-muted', t('noVariants',
      'Only activated rows, saved rows or rows with an SKU are stored.'));
    mount.appendChild(note);
    setStockMode();
    store();
    refreshGallery();
  };
  const setStockMode = () => {
    root.dataset.mmSharedStock = isShared() ? '1' : '0';
    for (const field of mount.querySelectorAll('[data-mm-stock-column]')) {
      field.hidden = isShared();
    }
  };
  // Category assignment is the only source of new option definitions.
  // Preview those definitions before the compact physical-SKU grid.
  const displayCategoryOptions = () => {
    const cat = String(category?.value || '');
    const display = document.querySelector('[data-mm-category-option-list]');
    const link = document.querySelector('[data-mm-add-category-option]');
    if (link) {
      const id = Number(cat);
      link.href = Number.isSafeInteger(id) && id > 0
        ? 'index.php?option=com_minimarket&view=category&layout=edit&id=' + id
        : 'index.php?option=com_minimarket&view=categories';
    }
    if (!display) return;
    display.replaceChildren();
    const rule = config.catalog?.[cat];
    const axes = rule?.axes || [];
    const notice = el('p', 'small text-muted mb-2',
      t('categoryIntro', 'The selected category uses the following options:'));
    display.appendChild(notice);
    if (!axes.length) {
      display.appendChild(el('p', 'text-muted',
        t('categoryEmpty', 'No options assigned to this category.')));
      return;
    }
    const list = el('div', 'mm-product-option-summary');
    for (const axis of axes) {
      const row = el('div', 'mm-product-option-summary__row');
      row.appendChild(el('strong', '', axis.title));
      row.appendChild(el('span', 'text-muted',
        (axis.values || []).map(value => value.title).join(', ')));
      list.appendChild(row);
    }
    display.appendChild(list);
  };
  const reload = () => {
    const cat = category?.value || '';
    displayCategoryOptions();
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
