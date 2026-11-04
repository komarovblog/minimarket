/* One relationship editor for product and group forms.
   Search results are fetched on demand; NEVER render all catalog products. */
(() => {
  'use strict';
  for (const root of document.querySelectorAll('[data-mm-group-editor]')) {
    const mount = root.querySelector('[data-mm-group-content]');
    const output = root.querySelector('[data-mm-group-payload]');
    const status = root.querySelector('[data-mm-group-status]');
    let config = {}, labels = {}, groups = [];
    try {
      config = JSON.parse(root.dataset.mmConfig || '{}');
      labels = JSON.parse(root.dataset.mmLabels || '{}');
      groups = JSON.parse(output.value || '[]');
      if (!Array.isArray(groups)) throw new Error('Invalid group data');
    } catch (_) {
      status.textContent = 'Invalid relationship data';
      continue;
    }
    const t = (key, fallback) => labels[key] || fallback;
    const el = (tag, className, textContent) => {
      const node = document.createElement(tag);
      if (className) node.className = className;
      if (textContent !== undefined) node.textContent = textContent;
      return node;
    };
    const button = (text, className, callback) => {
      const node = el('button', 'btn btn-sm ' + className, text);
      node.type = 'button';
      node.addEventListener('click', callback);
      return node;
    };
    const report = (value, error=false) => {
      status.textContent = value;
      status.classList.toggle('text-danger', error);
    };
    const save = () => {
      output.value = JSON.stringify(groups);
      output.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const api = async (url, params) => {
      const target = new URL(url, document.baseURI);
      for (const [key, value] of Object.entries(params)) {
        target.searchParams.set(key, String(value));
      }
      const response = await fetch(target, {
        credentials: 'same-origin', headers: { Accept: 'application/json' },
      });
      if (!response.ok) throw new Error(t('error', 'Search failed'));
      return response.json();
    };
    const memberKey = item => String(item.id);
    const update = (callback) => {
      callback();
      save();
      render();
    };

    const addMember = (group, product) => {
      const id = Number(product.id);
      if (!Number.isSafeInteger(id) || id < 1) return;
      const key = (config.mode === 'product' && id === config.currentId)
        ? 'current' : id;
      if (group.members.some(p => memberKey(p) === String(key))) {
        group.members.find(p => memberKey(p) === String(key)).enabled = true;
      } else {
        group.members.push({ id: key, title: String(product.title),
          state: Number(product.state), enabled: true });
      }
      save();
      render();
    };

    const finder = group => {
      const box = el('div', 'mm-group-finder');
      const toolbar = el('div', 'mm-group-finder__search');
      const query = el('input', 'form-control form-control-sm');
      query.type = 'search';
      query.placeholder = t('searchPlaceholder', 'Search product by name or SKU');
      query.setAttribute('aria-label', query.placeholder);
      const category = el('select', 'form-select form-select-sm');
      category.setAttribute('aria-label', t('category', 'Category'));
      const all = el('option', '', t('all', 'All categories'));
      all.value = '0'; category.appendChild(all);
      for (const cat of config.categories || []) {
        const option = el('option', '', cat.title);
        option.value = String(cat.id);
        category.appendChild(option);
      }
      const results = el('div', 'mm-group-finder__results');
      const search = button(t('search', 'Search'), 'btn-outline-primary', () => look());
      toolbar.append(query, category, search);
      box.append(toolbar, results);
      let loading = false, debounce = null, serial = 0;
      const look = async () => {
        const number = ++serial;
        if (loading) { /* stale response suppressed by serial */ }
        loading = true;
        search.disabled = true;
        try {
          const data = await api(config.searchUrl, {
            q: query.value.trim(), category_id: category.value,
          });
          if (number !== serial) return;
          results.replaceChildren();
          const selected = new Set(group.members
            .filter(p => p.enabled).map(memberKey));
          const items = Array.isArray(data.items) ? data.items : [];
          if (!items.length) {
            results.appendChild(el('p', 'small text-muted',
              t('noMatches', 'No products found')));
          }
          for (const product of items) {
            const row = el('div', 'mm-group-finder__result');
            const caption = el('span', '', String(product.title) +
              ' (#' + product.id + ') — ' + product.category);
            if (product.state !== 1) caption.appendChild(
              el('small', 'text-muted ms-1', t('unpublished', 'Unpublished')));
            const isCurrent = config.mode === 'product' &&
              product.id === config.currentId;
            const key = isCurrent ? 'current' : String(product.id);
            const add = button('+ ' + t('addProduct', 'Add'),
              'btn-outline-success', () => addMember(group, product));
            add.disabled = selected.has(key);
            row.append(caption, add);
            results.appendChild(row);
          }
        } catch (_) {
          if (number === serial) report(t('error', 'Search failed'), true);
        } finally {
          if (number === serial) { loading = false; search.disabled = false; }
        }
      };
      query.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(look, 300);
      });
      category.addEventListener('change', look);
      search.addEventListener('click', event => event.stopPropagation());
      return box;
    };

    const renderGroup = (g, index) => {
      const details = el('details', 'mm-group-item');
      details.open = Boolean(g._open);
      details.addEventListener('toggle', () => {
        g._open = details.open;
      });
      if (g.delete) details.classList.add('mm-group-item--deleted');
      const summary = el('summary', 'mm-group-item__heading');
      const count = g.members.filter(m => m.enabled).length;
      summary.appendChild(el('span', 'fw-semibold',
        (g.title || t('newGroup', 'New relationship')) +
          (g.id ? ' (#' + g.id + ')' : '')));
      summary.appendChild(el('small', 'text-muted',
        count + ' · ' + t('members', 'Products')));
      if (g.delete) summary.appendChild(
        el('small', 'text-danger', t('deleteGroup', 'Delete relationship')));
      details.appendChild(summary);
      const content = el('div', 'mm-group-item__body');
      if (config.mode === 'product') {
        const titleLabel = el('label', 'mm-group-item__title');
        titleLabel.appendChild(el('span', 'form-label small fw-semibold',
          t('name', 'Title')));
        const input = el('input', 'form-control form-control-sm');
        input.type = 'text'; input.maxLength = 255;
        input.value = g.title || '';
        input.disabled = Boolean(g.delete);
        input.addEventListener('input', () => {
          g.title = input.value;
          summary.querySelector('span').textContent = input.value ||
            t('newGroup', 'New relationship');
          save();
        });
        titleLabel.appendChild(input);
        content.appendChild(titleLabel);
      }
      const members = el('div', 'mm-group-members');
      for (const member of g.members) {
        const item = el('div', 'mm-group-member');
        const label = el('label', 'mm-group-member__label');
        const checkbox = el('input', 'form-check-input');
        checkbox.type = 'checkbox';
        checkbox.checked = member.enabled !== false;
        checkbox.disabled = Boolean(g.delete);
        checkbox.addEventListener('change', () => {
          member.enabled = checkbox.checked;
          if (member.id === 'current') g.leave = !checkbox.checked;
          save();
        });
        label.appendChild(checkbox);
        const name = String(member.title || '') +
          (member.id === 'current'
            ? ' — ' + t('current', 'Current product')
            : ' (#' + member.id + ')');
        label.appendChild(el('span', '', name));
        if (member.state === 0) label.appendChild(
          el('small', 'text-muted', t('unpublished', 'Unpublished')));
        const remove = button(t('removeMember', 'Remove'),
          'btn-outline-danger', () => {
            update(() => {
              if (member.id === 'current') g.leave = true;
              g.members = g.members.filter(x => x !== member);
            });
          });
        remove.disabled = Boolean(g.delete);
        item.append(label, remove);
        members.appendChild(item);
      }
      content.appendChild(members);
      if (!g.delete) {
        const pick = button('+ ' + t('addProduct', 'Add product'),
          'btn-outline-primary', () => {
            const finderBox = content.querySelector('.mm-group-finder');
            if (finderBox) { finderBox.remove(); return; }
            const created = finder(g);
            content.appendChild(created);
            created.querySelector('input[type="search"]').focus();
          });
        content.appendChild(pick);
      }
      if (config.mode === 'product') {
        const actions = el('div', 'mm-group-item__actions');
        if (g.id > 0) {
          actions.appendChild(button(t('leave', 'Remove current product'),
            'btn-outline-secondary', () => update(() => {
              const current = g.members.find(m => m.id === 'current');
              if (current) current.enabled = false;
              g.leave = true;
            })));
          actions.appendChild(button(
            g.delete ? t('active', 'Restore') :
              t('deleteGroup', 'Delete entire relationship'),
            'btn-outline-danger', () => {
              if (!g.delete && !window.confirm(t('deleteConfirm',
                'Delete this relationship for all linked products?'))) return;
              update(() => { g.delete = !g.delete; });
            }));
        } else {
          actions.appendChild(button(t('deleteGroup', 'Discard'),
            'btn-outline-danger', () =>
              update(() => { groups.splice(index, 1); })));
        }
        content.appendChild(actions);
      }
      details.appendChild(content);
      return details;
    };

    const addExisting = container => {
      const chooser = el('div', 'mm-group-existing');
      const input = el('input', 'form-control form-control-sm');
      input.type = 'search';
      input.placeholder = t('groupSearch', 'Search existing relationships');
      const hits = el('div', 'mm-group-existing__results');
      const filter = () => {
        hits.replaceChildren();
        const term = input.value.trim().toLocaleLowerCase();
        if (!term) return;
        const used = new Set(groups.map(group => Number(group.id)));
        const matches = (config.otherGroups || [])
          .filter(g => !used.has(Number(g.id)) &&
            String(g.title).toLocaleLowerCase().includes(term)).slice(0,10);
        if (!matches.length) hits.appendChild(
          el('p', 'text-muted small', t('noMatches', 'No results')));
        for (const group of matches) {
          hits.appendChild(button(group.title + ' (#' + group.id + ')',
            'btn-outline-secondary', async () => {
              try {
                const found = await api(config.groupUrl, { id: group.id });
                const data = found.group;
                if (!data || Number(data.id) !== Number(group.id)) throw Error();
                const members = (data.members || []).map(p => ({
                  id: config.currentId > 0 && p.id === config.currentId ?
                    'current' : p.id,
                  title: p.title, state: p.state, enabled: true,
                }));
                if (!members.some(m => m.id === 'current')) {
                  members.unshift({ id:'current',
                    title: config.currentTitle, state: 1, enabled: true });
                }
                update(() => {
                  groups.push({ id:data.id, title:data.title, state:data.state,
                    members, leave:false, delete:false, _open:true });
                });
              } catch (_) { report(t('error', 'Search failed'), true); }
            }));
        }
      };
      input.addEventListener('input', filter);
      chooser.append(input, hits);
      return chooser;
    };

    const render = () => {
      mount.replaceChildren();
      if (config.mode === 'product') {
        const toolbar = el('div', 'mm-group-toolbar');
        toolbar.appendChild(button('+ ' + t('addGroup', 'Add relationship'),
          'btn-primary', () => {
            update(() => {
              groups.push({
                id:0,title:'',state:1,delete:false,leave:false,_open:true,
                members:[{id:'current',title:config.currentTitle ||
                  t('current','Current product'),state:1,enabled:true}]
              });
            });
          }));
        const existing = el('details', 'mm-group-existing-picker');
        existing.appendChild(el('summary', 'btn btn-sm btn-outline-secondary',
          '+ ' + t('existing','Attach existing relationship')));
        existing.appendChild(addExisting(existing));
        toolbar.appendChild(existing);
        mount.appendChild(toolbar);
      }
      if (!groups.length) mount.appendChild(el('p', 'text-muted',
        t('none', 'No relationships yet')));
      for (const [i, group] of groups.entries()) {
        mount.appendChild(renderGroup(group, i));
      }
      save();
    };
    render();
    root.closest('form')?.addEventListener('submit', save);
  }
})();
