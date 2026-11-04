/* Product-local variant editor: two Joomla native repeatable subforms.
 * Do not redraw a custom options form or watch all of adminForm.
 */
(() => {
  'use strict';
  const root = document.querySelector('[data-mm-local-builder]');
  if (!root) return;

  const editor = root.closest('[data-mm-variants-editor]');
  const optionsPanel = editor?.querySelector('[data-mm-variant-options]');
  const skuPanel = editor?.querySelector('[data-mm-variant-skus]');
  const preview = root.querySelector('[data-mm-matrix-preview]');
  const feedback = root.querySelector('[data-mm-local-feedback]');
  const generate = root.querySelector('[data-mm-generate]');
  const addSku = root.querySelector('[data-mm-add-sku]');
  const applyBulk = root.querySelector('[data-mm-sku-apply]');
  const selectAllSku = root.querySelector('[data-mm-sku-all]');
  const bulkPrice = root.querySelector('[data-mm-sku-price]');
  const bulkStock = root.querySelector('[data-mm-sku-stock]');
  // Each option combination still has a unique SKU. Only the inventory
  // source changes: product stock vs per-SKU stock. Hidden inputs stay posted.
  const stockModeInputs = Array.from(
    document.querySelectorAll('input[name="jform[option_stock_mode]"]')
  );
  const sharedInventory = () => {
    const selected = stockModeInputs.find(input => input.checked);
    return !selected || selected.value === '0';
  };
  const refreshInventory = () => {
    const shared = sharedInventory();
    const bulkStockLabel = bulkStock?.closest('label');
    if (bulkStockLabel) bulkStockLabel.hidden = shared;
    for (const selection of selectionInputs()) {
      const stockInput = findSkuField(selection, 'stock');
      if (!stockInput) continue;
      // Hide only presentation, never disable/unset stock: the editor might
      // switch back to per-combination tracking at any time.
      const cell = stockInput.closest('td');
      if (cell) {
        cell.hidden = shared;
        const table = cell.closest('table');
        const header = table?.tHead?.rows[0]?.cells[cell.cellIndex];
        if (header) header.hidden = shared;
      } else {
        const group = stockInput.closest('.control-group');
        if (group) group.hidden = shared;
        else stockInput.hidden = shared;
      }
    }
  };
  stockModeInputs.forEach(input => input.addEventListener('change', refreshInventory));
  let labels = {};
  try { labels = JSON.parse(root.dataset.mmLabels || '{}'); } catch (_) {}
  const t = (key, fallback) => labels[key] || fallback;
  const axisField = /^jform\[variant_dimensions\]\[([^\]]+)\]\[(title|code)\]$/;
  const valueField = /^jform\[variant_dimensions\]\[([^\]]+)\]\[values\]\[([^\]]+)\]\[(title|code)\]$/;
  const skuSelection = 'input[type="hidden"][name^="jform[variants]"][name$="[selection]"]';
  const asText = (value, className = '') => {
    const span = document.createElement('span');
    span.textContent = value;
    span.className = className;
    return span;
  };
  const button = (value, fn, className = 'btn btn-primary btn-sm') => {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = className;
    el.textContent = value;
    el.addEventListener('click', fn);
    return el;
  };
  const report = (message, error = false) => {
    if (feedback && feedback.textContent !== message) feedback.textContent = message;
    feedback?.classList.toggle('text-danger', error);
  };
  const identifier = (field, prefix, taken) => {
    let code = (field?.value || '').trim();
    if (!code) {
      let n = 1;
      while (taken.has(prefix + n)) n++;
      code = prefix + n;
      if (!field) throw new Error(t('incomplete', 'Incomplete option.'));
      field.value = code;
    }
    if (!/^[a-z][a-z0-9_-]{0,99}$/.test(code) || taken.has(code))
      throw new Error(t('incomplete', 'Duplicate or invalid option.'));
    taken.add(code);
    return code;
  };
  const getDimensions = () => {
    if (!optionsPanel) throw new Error(t('nativeUnavailable', 'The options form is unavailable.'));
    const refs = new Map();
    for (const input of optionsPanel.querySelectorAll('input[name]')) {
      const name = input.name || '';
      const axisMatch = name.match(axisField);
      const valueMatch = name.match(valueField);
      if (!axisMatch && !valueMatch) continue;
      const axisKey = (axisMatch || valueMatch)[1];
      if (!refs.has(axisKey)) refs.set(axisKey, { fields: {}, values: new Map() });
      const axis = refs.get(axisKey);
      if (axisMatch) {
        axis.fields[axisMatch[2]] = input;
      } else {
        const valueKey = valueMatch[2];
        if (!axis.values.has(valueKey)) axis.values.set(valueKey, {});
        axis.values.get(valueKey)[valueMatch[3]] = input;
      }
    }

    if (refs.size === 0) throw new Error(t('empty', 'Add an option using the + button.'));
    if (refs.size > 4) throw new Error(t('tooMany', 'Maximum four options.'));

    const usedAxes = new Set();
    const usedNames = new Set();
    const result = [];
    let combinations = 1;
    for (const axis of refs.values()) {
      const title = (axis.fields.title?.value || '').trim();
      if (!title || usedNames.has(title.toLocaleLowerCase()) || !axis.values.size)
        throw new Error(t('incomplete', 'Enter unique option names and their values.'));
      usedNames.add(title.toLocaleLowerCase());
      const code = identifier(axis.fields.code, 'axis', usedAxes);
      const usedValues = new Set();
      const usedValueNames = new Set();
      const values = [];
      for (const value of axis.values.values()) {
        const text = (value.title?.value || '').trim();
        if (!text || usedValueNames.has(text.toLocaleLowerCase()))
          throw new Error(t('incomplete', 'Enter each unique value.'));
        usedValueNames.add(text.toLocaleLowerCase());
        values.push({ code: identifier(value.code, 'value', usedValues), title: text });
      }
      combinations *= values.length;
      if (combinations > 200) throw new Error(t('limit', 'Maximum 200 combinations.'));
      result.push({ code, title, values });
    }
    return result;
  };
  const combos = (axes) => {
    let rows = [{ title: '', selection: {} }];
    for (const axis of axes) {
      const next = [];
      for (const row of rows) {
        for (const value of axis.values) {
          next.push({
            title: [row.title, value.title].filter(Boolean).join(' / '),
            selection: { ...row.selection, [axis.code]: 'v:' + value.code }
          });
        }
      }
      rows = next;
    }
    return rows;
  };
  const canonical = (obj) => JSON.stringify(Object.fromEntries(
    Object.entries(obj).sort(([a], [b]) => a.localeCompare(b))
  ));
  const selectionInputs = () => Array.from(skuPanel?.querySelectorAll(skuSelection) || []);
  const selectedNow = () => new Set(selectionInputs().map(input => {
    try { return canonical(JSON.parse(input.value || '{}')); }
    catch (_) { return ''; }
  }));
  const findSkuField = (selectionInput, name) => {
    const targetName = selectionInput.name.replace('[selection]', '[' + name + ']');
    return Array.from(skuPanel?.querySelectorAll('input[name],select[name],textarea[name]') || [])
      .find(field => field.name === targetName);
  };
  const findSkuInput = findSkuField;
  const updateLabels = (axes) => {
    for (const input of selectionInputs()) {
      let selection = {};
      try { selection = JSON.parse(input.value || '{}') || {}; } catch (_) {}
      const title = axes.map(axis => {
        const code = String(selection[axis.code] || '').replace(/^v:/, '');
        return axis.values.find(value => value.code === code)?.title || '—';
      }).join(' / ');
      const label = findSkuInput(input, 'variant_label');
      if (label && label.value !== title) label.value = title;
    }
  };
  const skuRepeater = () => skuPanel?.querySelector('.subform-repeatable');
  const nativeAdd = () => {
    const form = skuRepeater();
    if (!form) return null;
    return Array.from(skuPanel.querySelectorAll('button.group-add, a.group-add'))
      .find(item => item.closest('.subform-repeatable') === form) || null;
  };
  const concealRawAdd = () => {
    const add = nativeAdd();
    if (add && !add.hidden) {
      add.hidden = true;
      add.setAttribute('aria-hidden', 'true');
    }
  };
  const waitForRow = (before) => new Promise((resolve, reject) => {
    const present = () => selectionInputs().find(item => !before.has(item));
    const already = present();
    if (already) return resolve(already);
    const form = skuRepeater();
    if (!form) return reject(new Error(t('nativeUnavailable', 'SKU table unavailable.')));
    const observer = new MutationObserver(() => {
      const match = present();
      if (!match) return;
      observer.disconnect();
      clearTimeout(timeout);
      resolve(match);
    });
    observer.observe(form, { childList: true, subtree: true });
    const timeout = setTimeout(() => {
      observer.disconnect();
      reject(new Error(t('addFailed', 'Joomla did not add a SKU row.')));
    }, 3000);
  });
  const translit = {
    'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh',
    'з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o',
    'п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts',
    'ч':'ch','ш':'sh','щ':'shch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'
  };
  const skuPart = (name, fallback) => {
    const normalized = Array.from(name.trim().toLocaleLowerCase())
      .map(char => Object.hasOwn(translit, char) ? translit[char] : char).join('');
    return normalized.replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
      .toUpperCase() || fallback;
  };
  // One position is one existing native Joomla Subform row. Do not maintain
  // a second JSON model or replace Joomla's save/validation mechanics.
  const refreshSkuEditors = (axes) => {
    const configKey = JSON.stringify(axes.map(axis => [
      axis.code, axis.title, axis.values.map(value => [value.code, value.title])
    ]));
    for (const selectionInput of selectionInputs()) {
      const labelField = findSkuField(selectionInput, 'variant_label');
      const mount = labelField?.parentElement;
      if (!mount) continue;
      let controls = mount.querySelector('[data-mm-sku-row-tools]');
      if (!controls) {
        controls = document.createElement('div');
        controls.dataset.mmSkuRowTools = '1';
        controls.className = 'mt-2 d-flex flex-column gap-2';
        const actions = document.createElement('div');
        actions.className = 'd-flex flex-wrap align-items-center gap-2';

        const checkLabel = document.createElement('label');
        checkLabel.className = 'form-check mb-0';
        const check = document.createElement('input');
        check.type = 'checkbox';
        check.className = 'form-check-input';
        check.dataset.mmSkuSelect = '1';
        check.setAttribute('aria-label', t('chooseSku', 'Select position'));
        const text = document.createElement('span');
        text.className = 'form-check-label small';
        text.textContent = t('chooseSku', 'Select position');
        checkLabel.append(check, text);
        actions.appendChild(checkLabel);

        const copy = button(t('copySku', 'Copy'), async () => {
          copy.disabled = true;
          try {
            const localAxes = getDimensions();
            const newSelection = await appendRow({ title: '', selection: {} }, localAxes, true);
            // Copy only non-identity trade fields. SKU, variant ID and option
            // selection must be chosen for the new position. Media stays blank.
            for (const field of ['price', 'stock', 'state']) {
              const original = findSkuField(selectionInput, field);
              const target = findSkuField(newSelection, field);
              if (!original || !target) continue;
              target.value = original.value;
              target.dispatchEvent(new Event('change', { bubbles: true }));
            }
            report(t('copyDone', 'Position copied. Select options, enter SKU and save.'));
          } catch (error) {
            report(error.message, true);
          } finally {
            copy.disabled = false;
          }
        }, 'btn btn-outline-secondary btn-sm');
        copy.dataset.mmSkuCopy = '1';
        actions.appendChild(copy);

        const choices = document.createElement('div');
        choices.dataset.mmSkuChoices = '1';
        choices.className = 'd-flex flex-wrap gap-2';
        controls.append(actions, choices);
        mount.appendChild(controls);
      }
      const choices = controls.querySelector('[data-mm-sku-choices]');
      if (!choices || choices.dataset.mmAxesKey === configKey) continue;
      choices.dataset.mmAxesKey = configKey;
      choices.replaceChildren();
      let current = {};
      try { current = JSON.parse(selectionInput.value || '{}') || {}; } catch (_) {}
      for (const axis of axes) {
        const label = document.createElement('label');
        label.className = 'form-label mb-0 small';
        label.textContent = axis.title;
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.dataset.mmSkuAxis = axis.code;
        select.setAttribute('aria-label', axis.title);
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = '—';
        select.appendChild(blank);
        for (const value of axis.values) {
          const option = document.createElement('option');
          option.value = 'v:' + value.code;
          option.textContent = value.title;
          select.appendChild(option);
        }
        const saved = current[axis.code] || '';
        if (saved && !axis.values.some(value => 'v:' + value.code === saved)) {
          // Invalid historical value stays visible and cannot be silently
          // switched to another sellable position.
          const missing = document.createElement('option');
          missing.value = saved;
          missing.textContent = saved;
          missing.disabled = true;
          select.appendChild(missing);
        }
        select.value = saved;
        select.addEventListener('change', () => {
          const selected = {};
          choices.querySelectorAll('select[data-mm-sku-axis]').forEach(field => {
            if (field.value) selected[field.dataset.mmSkuAxis] = field.value;
          });
          selectionInput.value = JSON.stringify(selected);
          selectionInput.dispatchEvent(new Event('change', { bubbles: true }));
          updateLabels(axes);
        });
        label.appendChild(select);
        choices.appendChild(label);
      }
    }
  };

  const appendRow = async (row, axes, manual = false) => {
    const add = nativeAdd();
    if (!add) throw new Error(t('nativeUnavailable', 'SKU table unavailable.'));
    const before = new Set(selectionInputs());
    add.click();
    const selection = await waitForRow(before);
    selection.value = JSON.stringify(row.selection);
    selection.dispatchEvent(new Event('change', { bubbles: true }));
    const title = findSkuInput(selection, 'variant_label');
    if (title) title.value = row.title;
    const base = (document.querySelector('[name="jform[sku]"]')?.value || 'SKU').trim();
    const suffix = row.title.split(' / ').map((name, i) =>
      skuPart(name, Object.values(row.selection)[i]?.slice(2).toUpperCase() || 'VAR')
    ).join('-');
    const sku = findSkuInput(selection, 'sku');
    if (sku && !sku.value && !manual) sku.value = base + '-' + suffix;
    const price = findSkuInput(selection, 'price');
    if (price && !price.value) price.value =
      document.querySelector('[name="jform[price]"]')?.value || '0.00';
    const stock = findSkuInput(selection, 'stock');
    if (stock && !stock.value) stock.value = '0';
    concealRawAdd();
    updateLabels(axes);
    refreshSkuEditors(axes);
    decorateSkuCards();
    refreshInventory();
    return selection;
  };
  const showPreview = () => {
    try {
      const axes = getDimensions();
      const all = combos(axes);
      const existing = selectedNow();
      const missing = all.filter(row => !existing.has(canonical(row.selection)));
      preview.replaceChildren();
      const summary = document.createElement('p');
      summary.className = 'fw-semibold';
      summary.textContent = t('preview', 'New combinations:') + ' ' + missing.length
        + ' / ' + all.length;
      preview.appendChild(summary);
      if (!missing.length) {
        report(t('created', 'All combinations are already in the SKU table.'));
        return;
      }
      const table = document.createElement('table');
      table.className = 'table table-striped table-sm align-middle';
      const header = table.createTHead().insertRow();
      const c1 = document.createElement('th');
      c1.scope = 'col'; c1.textContent = '✓';
      const c2 = document.createElement('th');
      c2.scope = 'col'; c2.textContent = t('combination', 'Variant');
      header.append(c1, c2);
      const tbody = table.createTBody();
      for (const item of missing) {
        const row = tbody.insertRow();
        const checkCell = row.insertCell();
        const nameCell = row.insertCell();
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'form-check-input';
        checkbox.checked = true;
        checkbox.dataset.mmCombination = canonical(item.selection);
        checkbox.setAttribute('aria-label', item.title);
        checkCell.appendChild(checkbox);
        nameCell.textContent = item.title;
      }
      preview.appendChild(table);
      preview.appendChild(button(t('confirm', 'Add selected SKUs'), async () => {
        const selected = new Set(Array.from(
          preview.querySelectorAll('input[data-mm-combination]:checked')
        ).map(element => element.dataset.mmCombination));
        const toAdd = missing.filter(item => selected.has(canonical(item.selection)));
        const commit = preview.querySelector('button');
        if (commit) commit.disabled = true;
        try {
          for (const item of toAdd) await appendRow(item, axes);
          report(t('created', 'SKU rows added. Fill in price, stock and save.'));
          preview.replaceChildren();
          concealRawAdd();
        } catch (error) {
          report(error.message, true);
          if (commit) commit.disabled = false;
        }
      }));
      report('');
    } catch (error) {
      preview.replaceChildren();
      report(error.message, true);
    }
  };
  addSku?.addEventListener('click', async () => {
    addSku.disabled = true;
    try {
      const axes = getDimensions();
      await appendRow({ title: '', selection: {} }, axes, true);
      report(t('manualDone', 'Select option values, enter SKU and save.'));
    } catch (error) {
      report(error.message, true);
    } finally {
      addSku.disabled = false;
    }
  });
  selectAllSku?.addEventListener('change', () => {
    skuPanel?.querySelectorAll('[data-mm-sku-select]').forEach(field => {
      field.checked = selectAllSku.checked;
    });
  });
  applyBulk?.addEventListener('click', () => {
    const price = (bulkPrice?.value || '').trim();
    const stock = sharedInventory() ? '' : (bulkStock?.value || '').trim();
    if (!price && !stock) {
      report(t('bulkRequired', 'Enter price or stock.'), true);
      return;
    }
    if ((price && !/^(?:0|[1-9]\d{0,12})(?:\.\d{1,2})?$/.test(price)) ||
        (stock && (!/^(?:0|[1-9]\d{0,9})$/.test(stock) || Number(stock) > 4294967295))) {
      report(t('bulkInvalid', 'Invalid price or stock.'), true);
      return;
    }
    const chosen = selectionInputs().filter(input => {
      const mount = findSkuField(input, 'variant_label')?.parentElement;
      return Boolean(mount?.querySelector('[data-mm-sku-select]:checked'));
    });
    if (!chosen.length) {
      report(t('bulkSelection', 'Select at least one SKU.'), true);
      return;
    }
    for (const selection of chosen) {
      for (const [key, value] of [['price', price], ['stock', stock]]) {
        if (value === '') continue;
        const field = findSkuField(selection, key);
        if (field) {
          field.value = value;
          field.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
      const mount = findSkuField(selection, 'variant_label')?.parentElement;
      const checkbox = mount?.querySelector('[data-mm-sku-select]');
      if (checkbox) checkbox.checked = false;
    }
    if (selectAllSku) selectAllSku.checked = false;
    report(t('bulkDone', 'Selected SKU fields updated. Save the product.'));
  });
  optionsPanel?.addEventListener('change', () => {
    try {
      const axes = getDimensions();
      refreshSkuEditors(axes);
      updateLabels(axes);
    } catch (_) { /* incomplete rows are validated on generation/save */ }
  });

  generate?.addEventListener('click', showPreview);

  // Compact master/detail presentation of the one native Joomla SKU form.
  // This never creates a parallel row model or disables hidden values.
  const decorateSkuCards = () => {
    for (const selection of selectionInputs()) {
      const group = selection.closest('.subform-repeatable-group');
      if (!group || group.dataset.mmCompactSku) continue;
      group.dataset.mmCompactSku = '1';
      for (const key of [
        'image', 'image_alt', 'photos', 'description_mode',
        'introtext_override', 'description_override', 'video_mode', 'videos'
      ]) {
        const namePrefix = selection.name.replace('[selection]', '[' + key + ']');
        const field = findSkuField(selection, key)
          || Array.from(group.querySelectorAll('[name]')).find(input =>
            input.name?.startsWith(namePrefix));
        let outer = field?.closest('.control-group');
        while (outer && outer.parentElement !== group) {
          const higher = outer.parentElement.closest('.control-group');
          if (!higher || !group.contains(higher)) break;
          outer = higher;
        }
        if (outer && group.contains(outer)) outer.classList.add('mm-sku-extra');
      }
      const detail = button(
        t('skuDetails', 'Details'),
        () => {
          const expanded = group.classList.toggle('mm-sku-expanded');
          detail.setAttribute('aria-expanded', expanded ? 'true' : 'false');
          detail.textContent = expanded
            ? t('skuCloseDetails', 'Hide details')
            : t('skuDetails', 'Details');
        },
        'btn btn-outline-secondary btn-sm mm-sku-expand'
      );
      detail.setAttribute('aria-expanded', 'false');
      group.appendChild(detail);
    }
  };

  // Catalog-linked options are hydrated automatically into native Joomla
  // subforms. A historical product with its own SKU axes is never overwritten.
  const catalogRoot = editor?.querySelector('[data-mm-category-catalog]');
  const categoryInput = document.querySelector('[name="jform[category_id]"]');
  let catalog = {};
  let catalogLabels = {};
  try {
    catalog = JSON.parse(catalogRoot?.dataset.mmCatalog || '{}');
    catalogLabels = JSON.parse(catalogRoot?.dataset.mmCatalogLabels || '{}');
  } catch (_) {}
  const catalogList = catalogRoot?.querySelector('[data-mm-category-option-list]');
  const categoryRecord = () => catalog[String(categoryInput?.value || '')] || null;
  const renderCategoryOptions = () => {
    if (!catalogList) return;
    catalogList.replaceChildren();
    const record = categoryRecord();
    const showNote = (message) => catalogList.appendChild(
      asText(message, 'small text-muted')
    );
    if (!categoryInput?.value || categoryInput.value === '0') {
      showNote(catalogLabels.saveCategory || 'Choose a category.');
      return;
    }
    if (record?.too_many) {
      showNote(catalogLabels.tooMany || 'Too many combinations.');
      return;
    }
    if (!record?.axes?.length) {
      showNote(catalogLabels.empty || 'No category options.');
      return;
    }
    for (const axis of record.axes) {
      const item = document.createElement('div');
      item.className = 'mm-option-list__item';
      item.appendChild(asText(axis.title, 'fw-semibold'));
      item.appendChild(asText(' (' + (axis.required
        ? (catalogLabels.required || 'Required')
        : (catalogLabels.optional || 'Optional')) + ')', 'small text-muted'));
      item.appendChild(asText(axis.values.map(value => value.title).join(' · '),
        'd-block small text-muted'));
      catalogList.appendChild(item);
    }
  };
  const nativeGroupAdd = (form) => form && Array.from(
    form.querySelectorAll('button.group-add, a.group-add')
  ).find(el => el.closest('.subform-repeatable') === form);
  const waitForNew = (scope, select, before) => new Promise((resolve, reject) => {
    const find = () => select(scope).find(el => !before.has(el));
    const present = find();
    if (present) return resolve(present);
    const observer = new MutationObserver(() => {
      const candidate = find();
      if (!candidate) return;
      observer.disconnect();
      clearTimeout(timer);
      resolve(candidate);
    });
    observer.observe(scope, { childList: true, subtree: true });
    const timer = setTimeout(() => {
      observer.disconnect();
      reject(new Error(t('nativeUnavailable', 'Joomla option editor unavailable.')));
    }, 3500);
  });
  const selectAxisTitles = (scope) => Array.from(scope.querySelectorAll('input[name]'))
    .filter(input => Boolean(input.name.match(axisField)) && input.name.endsWith('[title]'));
  const selectValueTitles = (scope) => Array.from(scope.querySelectorAll('input[name]'))
    .filter(input => Boolean(input.name.match(valueField)) && input.name.endsWith('[title]'));
  const setInput = (field, value) => {
    if (!field) throw new Error(t('nativeUnavailable', 'Option input unavailable.'));
    field.value = value;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
  };
  const lockCatalogNames = () => {
    for (const title of selectAxisTitles(optionsPanel)) {
      const found = title.name.match(axisField);
      const sourceName = 'jform[variant_dimensions][' + found[1] + '][source_option_id]';
      const source = optionsPanel.querySelector('input[name="' + sourceName + '"]');
      if (!source || Number(source.value) < 1) continue;
      title.readOnly = true;
      for (const valueTitle of selectValueTitles(title.closest('.subform-repeatable-group'))) {
        const row = valueTitle.name.match(valueField);
        const valueName = 'jform[variant_dimensions][' + row[1] + '][values]['
          + row[2] + '][source_value_id]';
        const sourceValue = optionsPanel.querySelector('input[name="' + valueName + '"]');
        if (sourceValue && Number(sourceValue.value) > 0) valueTitle.readOnly = true;
      }
    }
  };
  let hydrating = false;
  const hydrateFromCategory = async () => {
    renderCategoryOptions();
    if (hydrating || !optionsPanel) return;
    const record = categoryRecord();
    if (!record?.axes?.length || record.too_many) return;
    // Existing variants, including old product-local configurations, are
    // important user data; a category change cannot replace them implicitly.
    if (selectAxisTitles(optionsPanel).length || selectionInputs().length) {
      lockCatalogNames();
      return;
    }
    hydrating = true;
    try {
      const axisForm = optionsPanel.querySelector('.subform-repeatable');
      if (!axisForm) throw new Error(t('nativeUnavailable', 'The options form is unavailable.'));
      for (const axis of record.axes) {
        const before = new Set(selectAxisTitles(optionsPanel));
        const add = nativeGroupAdd(axisForm);
        if (!add) throw new Error(t('nativeUnavailable', 'The add-option button is unavailable.'));
        add.click();
        const axisInput = await waitForNew(optionsPanel, selectAxisTitles, before);
        const found = axisInput.name.match(axisField);
        if (!found) throw new Error(t('nativeUnavailable', 'Bad option row.'));
        const axisKey = found[1];
        setInput(axisInput, axis.title);
        for (const [name, value] of [
          ['code', axis.code], ['source_option_id', String(axis.source_option_id)]
        ]) {
          const input = optionsPanel.querySelector('input[name="jform[variant_dimensions]['
            + axisKey + '][' + name + ']"]');
          setInput(input, value);
        }
        const group = axisInput.closest('.subform-repeatable-group');
        const valuesForm = group?.querySelector('.subform-repeatable');
        if (!valuesForm) throw new Error(t('nativeUnavailable', 'The values form is unavailable.'));
        for (const choice of axis.values) {
          const valueBefore = new Set(selectValueTitles(valuesForm));
          const addValue = nativeGroupAdd(valuesForm);
          if (!addValue) throw new Error(t('nativeUnavailable', 'The add-value button is unavailable.'));
          addValue.click();
          const choiceInput = await waitForNew(valuesForm, selectValueTitles, valueBefore);
          const valueMatch = choiceInput.name.match(valueField);
          if (!valueMatch) throw new Error(t('nativeUnavailable', 'Bad option value row.'));
          setInput(choiceInput, choice.title);
          for (const [name, value] of [
            ['code', choice.code], ['source_value_id', String(choice.source_value_id)]
          ]) {
            const input = optionsPanel.querySelector('input[name="jform[variant_dimensions]['
              + valueMatch[1] + '][values][' + valueMatch[2] + '][' + name + ']"]');
            setInput(input, value);
          }
        }
      }
      lockCatalogNames();
      showPreview();
      report(t('catalogHydrated', 'Options loaded from category. Choose sellable combinations.'));
    } catch (error) {
      report(error.message, true);
    } finally {
      hydrating = false;
    }
  };
  categoryInput?.addEventListener('change', () => {
    // A product with established options retains those axes when its category
    // changes. New products get the new category's options immediately.
    void hydrateFromCategory();
  });

  // Only additions of SKU row inputs count; do not observe the entire Joomla
  // editor or rewrite DOM on our own mutations.
  const repeater = skuRepeater();
  if (repeater) {
    const observer = new MutationObserver(records => {
      if (!records.some(record => Array.from(record.addedNodes).some(node =>
        node.nodeType === 1 && (node.matches?.(skuSelection)
          || node.querySelector?.(skuSelection))))) return;
      concealRawAdd();
      decorateSkuCards();
      try {
        const axes = getDimensions();
        refreshSkuEditors(axes);
        updateLabels(axes);
        refreshInventory();
      } catch (_) {}
    });
    observer.observe(repeater, { childList: true, subtree: true });
  }
  concealRawAdd();
  decorateSkuCards();
  renderCategoryOptions();
  lockCatalogNames();
  // Joomla subforms can be initialized after the deferred component script.
  // Queue hydration, but never touch a form already holding existing options.
  queueMicrotask(() => { void hydrateFromCategory(); });
  try {
    const axes = getDimensions();
    refreshSkuEditors(axes);
    updateLabels(axes);
  } catch (_) { /* empty option editor is allowed */ }
  refreshInventory();
})();
