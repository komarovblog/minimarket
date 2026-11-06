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
      // Older drafts may still carry disabled checkbox entries. Members that
      // were unchecked are already removed logically and not displayed again.
      groups = groups.map(g => ({ ...g,
        members: (g.members || []).filter(m => m.enabled !== false),
      }));
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
      output.value = JSON.stringify(groups.map(group => Object.fromEntries(
        Object.entries(group).filter(([key]) => !key.startsWith('_'))
      )));
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
    const productHref = member => {
      const id = member.id === 'current' ? Number(config.currentId) : Number(member.id);
      return Number.isSafeInteger(id) && id > 0 ?
        'index.php?option=com_minimarket&task=product.edit&id=' + id : '';
    };
    const openLink = (href, name, className) => {
      const link = el('a', className || '', name);
      link.href = href;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.addEventListener('click', event => event.stopPropagation());
      return link;
    };
    const submitGroupDeletion = () => {
      const form = root.closest('form');
      if (!form) return;
      if (window.Joomla?.submitform) {
        window.Joomla.submitform('group.deleteRelationship', form);
      } else {
        const task = form.querySelector('[name="task"]');
        if (task) task.value = 'group.deleteRelationship';
        form.requestSubmit();
      }
    };
    const submitGroupChanges = () => {
      const form = root.closest('form');
      if (!form) return;
      save();
      const task = config.mode === 'group' ? 'group.apply' : 'product.apply';
      if (window.Joomla?.submitbutton) {
        window.Joomla.submitbutton(task);
      } else if (window.Joomla?.submitform) {
        window.Joomla.submitform(task, form);
      } else {
        const control = form.querySelector('[name="task"]');
        if (control) control.value = task;
        form.requestSubmit();
      }
    };
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
      if (!group.members.some(p => memberKey(p) === String(key))) {
        group.members.unshift({ id: key, title: String(product.title),
          state: Number(product.state), enabled: true });
      }
      save();
      render();
      const index = groups.indexOf(group);
      mount.querySelectorAll('.mm-group-item')[index]
        ?.querySelector('.mm-group-finder input[type="search"]')?.focus();
    };

    const finder = group => {
      const box = el('div', 'mm-group-finder');
      const toolbar = el('div', 'mm-group-finder__search');
      const query = el('input', 'form-control form-control-sm');
      query.type = 'search';
      query.placeholder = t('searchPlaceholder', 'Search product by name or SKU');
      query.value = group._findQuery || '';
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
      const search = button(t('search', 'Search'), 'btn-primary', () => look());
      toolbar.append(query, category, search);
      box.append(toolbar, results);
      category.value = group._findCategory || '0';
      let loading = false, debounce = null, serial = 0;
      const paintResults = items => {
        results.replaceChildren();
        const selected = new Set(group.members.map(memberKey));
        if (!items.length) results.appendChild(el('p', 'small text-muted',
          t('noMatches', 'No products found')));
        for (const product of items) {
          const row = el('div', 'mm-group-finder__result');
          const caption = el('span', '');
          caption.appendChild(openLink(productHref(product),
            String(product.title) + ' (#' + product.id + ')', ''));
          caption.appendChild(el('span', 'text-muted ms-1',
            '— ' + product.category));
          if (product.state !== 1) caption.appendChild(
            el('small', 'text-muted ms-1', t('unpublished', 'Unpublished')));
          const isCurrent = config.mode === 'product' &&
            product.id === config.currentId;
          const key = isCurrent ? 'current' : String(product.id);
          const add = button('+', 'btn-success', () => addMember(group, product));
          add.setAttribute('aria-label', t('addProduct', 'Add product') + ' ' + product.title);
          add.disabled = selected.has(key);
          row.append(caption, add);
          results.appendChild(row);
        }
      };
      if (Array.isArray(group._finderResults)) paintResults(group._finderResults);
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
          const items = Array.isArray(data.items) ? data.items : [];
          group._finderResults = items;
          paintResults(items);
        } catch (_) {
          if (number === serial) report(t('error', 'Search failed'), true);
        } finally {
          if (number === serial) { loading = false; search.disabled = false; }
        }
      };
      query.addEventListener('input', () => {
        group._findQuery = query.value;
        group._finderResults = null;
        clearTimeout(debounce);
        debounce = setTimeout(look, 300);
      });
      category.addEventListener('change', () => {
        group._findCategory = category.value;
        group._finderResults = null;
        void look();
      });
      search.addEventListener('click', event => event.stopPropagation());
      return box;
    };

    const renderGroup = (g, index) => {
      const details = el('details', 'mm-group-item');
      details.open = Boolean(g._open);
      details.addEventListener('toggle', () => {
        g._open = details.open;
        const expander = details.querySelector('[data-mm-group-toggle]');
        if (expander) {
          expander.textContent = details.open
            ? t('collapse', 'Collapse') : t('expand', 'Expand');
          expander.setAttribute('aria-expanded', String(details.open));
        }
      });
      if (g.delete) details.classList.add('mm-group-item--deleted');
      const summary = el('summary', 'mm-group-item__heading');
      const count = g.members.length;
      const groupName = (g.title || t('newGroup', 'New relationship')) +
        (g.id ? ' (#' + g.id + ')' : '');
      const groupTitle = config.mode === 'product' && Number(g.id) > 0
        ? openLink('index.php?option=com_minimarket&task=group.edit&id=' +
            Number(g.id), groupName, 'fw-semibold')
        : el('span', 'fw-semibold', groupName);
      groupTitle.dataset.mmGroupTitle = '1';
      summary.appendChild(groupTitle);
      summary.appendChild(el('small', 'text-muted',
        count + ' · ' + t('members', 'Products')));
      const expander = button(
        details.open ? t('collapse', 'Collapse') : t('expand', 'Expand'),
        'btn-secondary mm-group-item__toggle', () => {
          details.open = !details.open;
          g._open = details.open;
          expander.textContent = details.open
            ? t('collapse', 'Collapse') : t('expand', 'Expand');
          expander.setAttribute('aria-expanded', String(details.open));
        });
      expander.dataset.mmGroupToggle = '1';
      expander.setAttribute('aria-expanded', String(details.open));
      expander.setAttribute('aria-label', t('toggleHelp', 'Expand or collapse relationship'));
      expander.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
      });
      summary.appendChild(expander);
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
          groupTitle.textContent = (input.value ||
            t('newGroup', 'New relationship')) +
            (g.id ? ' (#' + g.id + ')' : '');
          save();
        });
        titleLabel.appendChild(input);
        content.appendChild(titleLabel);
      }
      const members = el('div', 'mm-group-members');
      for (const member of g.members) {
        const item = el('div', 'mm-group-member');
        const name = String(member.title || '') +
          (member.id === 'current'
            ? ' (#' + config.currentId + ')'
            : ' (#' + member.id + ')');
        const href = productHref(member);
        const memberLink = href
          ? openLink(href, name, 'mm-group-member__link')
          : el('span', 'mm-group-member__link', name);
        const caption = el('span', 'mm-group-member__label');
        caption.appendChild(memberLink);
        if (member.id === 'current') caption.appendChild(
          el('small', 'text-muted', t('current', 'Current product')));
        if (member.state === 0) caption.appendChild(
          el('small', 'text-muted', t('unpublished', 'Unpublished')));
        const remove = button('−', 'btn-danger', () => update(() => {
            // Removing an entry only unlinks THIS product from THIS group.
            // The relationship and all other group members stay intact.
            g.members = g.members.filter(x => x !== member);
          }));
        remove.setAttribute('aria-label',t('removeMember','Remove') + ': ' + member.title);
        remove.disabled = Boolean(g.delete);
        item.append(caption, remove);
        members.appendChild(item);
      }
      // A search opens BEFORE the existing members, never below a long list.
      if (g._finderOpen && !g.delete) {
        content.appendChild(finder(g));
      }
      content.appendChild(members);
      if (!g.delete) {
        const pick = button('+ ' + t('addProduct', 'Add product'),
          'btn-success', () => {
            update(() => {
              g._open = true;
              g._finderOpen = !g._finderOpen;
            });
            if (g._finderOpen) {
              mount.querySelectorAll('.mm-group-item')[index]
                ?.querySelector('.mm-group-finder input[type="search"]')?.focus();
            }
          });
        content.insertBefore(pick, content.querySelector('.mm-group-finder') || members);
      }
      // Explicit save/cancel actions are visually separate from Add product.
      const saveActions = el('div', 'mm-group-item__save-actions');
      saveActions.appendChild(button(t('save', 'Save'), 'btn-success',
        submitGroupChanges));
      if (config.mode === 'product' && Number(g.id) === 0) {
        saveActions.appendChild(button(t('discard', 'Cancel creation'),
          'btn-primary', () => update(() => {
            groups.splice(index, 1);
          })));
      }
      content.appendChild(saveActions);
      const actions = el('div', 'mm-group-item__actions');
      if (Number(g.id) > 0) {
        actions.appendChild(button(
          g.delete ? t('restore', 'Undo deletion') :
            t('deleteGroup', 'Delete relationship'),
          g.delete ? 'btn-primary' : 'btn-danger', () => {
            if (!g.delete && !window.confirm(t('deleteConfirm',
              'Delete this relationship for all linked products?'))) return;
            if (config.mode === 'group') {
              submitGroupDeletion();
            } else {
              update(() => { g.delete = !g.delete; });
            }
          }));
      }
      if (actions.children.length) content.appendChild(actions);
      details.appendChild(content);
      return details;
    };

    // Match the product picker: search field, Search button, result rows,
    // green Add buttons. The query searches only GROUP NAMES, never a wall
    // of checkboxes or every product in the catalog.
    const addExisting = () => {
      const chooser = el('div', 'mm-group-finder mm-group-existing');
      const toolbar = el('div', 'mm-group-finder__search mm-group-finder__search--groups');
      const input = el('input', 'form-control form-control-sm');
      input.type = 'search';
      input.placeholder = t('groupSearch', 'Search existing relationships');
      input.setAttribute('aria-label', input.placeholder);
      const hits = el('div', 'mm-group-finder__results mm-group-existing__results');
      const search = button(t('search', 'Search'), 'btn-primary', () => filter());
      toolbar.append(input, search);
      chooser.append(toolbar, hits);
      const filter = () => {
        hits.replaceChildren();
        const term = input.value.trim().toLocaleLowerCase();
        if (!term) return;
        const used = new Set(groups.map(group => Number(group.id)));
        const matches = (config.otherGroups || []).filter(g =>
          !used.has(Number(g.id)) &&
          (String(g.title).toLocaleLowerCase().includes(term) ||
           String(g.id) === term)
        ).slice(0, 25);
        if (!matches.length) hits.appendChild(
          el('p', 'small text-muted', t('noMatches', 'No results')));
        for (const group of matches) {
          const row = el('div', 'mm-group-finder__result');
          const title = el('span', '', group.title + ' (#' + group.id + ')');
          const add = button('+', 'btn-success', async () => {
              add.disabled = true;
              try {
                const found = await api(config.groupUrl, { id: group.id });
                const data = found.group;
                if (!data || Number(data.id) !== Number(group.id) ||
                    groups.some(g => Number(g.id) === Number(data.id))) return;
                const members = (data.members || []).map(p => ({
                  id: config.currentId > 0 && p.id === config.currentId
                    ? 'current' : p.id,
                  title: p.title, state: p.state, enabled: true,
                }));
                if (!members.some(m => m.id === 'current')) {
                  members.unshift({ id: 'current', title: config.currentTitle,
                    state: 1, enabled: true });
                }
                update(() => {
                  groups.unshift({ id: data.id, title: data.title, state: data.state,
                    members, leave:false, delete:false, _open: true });
                  existingPickerOpen = false;
                });
                // Bring the newly added relationship into view at the top.
                mount.querySelector('.mm-group-item')?.scrollIntoView({
                  behavior: 'auto', block: 'nearest'
                });
              } catch (_) {
                report(t('error', 'Search failed'), true);
                add.disabled = false;
              }
            });
          row.append(title, add);
          hits.appendChild(row);
        }
      };
      let debounce;
      input.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(filter, 250);
      });
      input.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); filter(); }
      });
      return chooser;
    };

    let existingPickerOpen = false;
    const render = () => {
      mount.replaceChildren();
      if (!groups.length) mount.appendChild(el('p', 'text-muted',
        t('none', 'No relationships yet')));
      // Existing and newly created groups stay at the TOP; actions at bottom.
      for (const [i, group] of groups.entries()) {
        mount.appendChild(renderGroup(group, i));
      }
      if (config.mode === 'product') {
        const toolbar = el('div', 'mm-group-toolbar mm-group-toolbar--top');
        toolbar.appendChild(button('+ ' + t('addGroup', 'Add relationship'),
          'btn-success', () => {
            update(() => {
              groups.unshift({
                id:0,title:'',state:1,delete:false,leave:false,_open:true,
                members:[{id:'current',title:config.currentTitle ||
                  t('current','Current product'),state:1,enabled:true}]
              });
              existingPickerOpen = false;
            });
            mount.querySelector('.mm-group-item input[type="text"]')?.focus();
          }));
        toolbar.appendChild(button('+ ' + t('existing',
          'Attach existing relationship'), 'btn-success', () => {
          existingPickerOpen = !existingPickerOpen;
          render();
          mount.querySelector('.mm-group-existing input[type="search"]')?.focus();
        }));
        mount.prepend(toolbar);
        if (existingPickerOpen) {
          toolbar.after(addExisting());
        }
      }
      save();
    };
    render();
    root.closest('form')?.addEventListener('submit', save);
  }
})();
