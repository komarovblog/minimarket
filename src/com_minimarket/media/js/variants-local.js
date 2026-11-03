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
  generate?.addEventListener('click', showPreview);

  // Only additions of SKU row inputs count; do not observe the entire Joomla
  // editor or rewrite DOM on our own mutations.
  const repeater = skuRepeater();
  if (repeater) {
    const observer = new MutationObserver(records => {
      if (!records.some(record => Array.from(record.addedNodes).some(node =>
        node.nodeType === 1 && (node.matches?.(skuSelection)
          || node.querySelector?.(skuSelection))))) return;
      concealRawAdd();
      try { updateLabels(getDimensions()); } catch (_) {}
    });
    observer.observe(repeater, { childList: true, subtree: true });
  }
  concealRawAdd();
  try { updateLabels(getDimensions()); } catch (_) {}
})();
