/* Regression: Joomla product edit must not freeze on a self-triggered observer. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(
  'src/com_minimarket/media/js/variants-admin.js', 'utf8'
);
const mutations = [];
let callback;
let redrawQueries = 0;
let warningChanges = 0;
const warningNode = {
  nodeType: 1,
  matches() { return false; },
  querySelector() { return null; }
};
const warning = {
  _text: '',
  get textContent() { return this._text; },
  set textContent(value) {
    // Browser textContent setters emit childList mutations inside adminForm.
    this._text = String(value);
    warningChanges++;
    mutations.push({ addedNodes: [warningNode] });
  }
};
const info = {
  dataset: { mmVariantConfig: JSON.stringify({
    '1': {},
    '2': { '10': {
      title: 'Размер',
      options: [{ value: 't:S', title: 'S' }]
    }}
  })},
  querySelector() { return warning; }
};
const handlers = {};
const category = {
  value: '1',
  addEventListener(name, fn) { handlers[name] = fn; }
};
const document = {
  documentElement: { lang: 'ru-RU' },
  querySelector(selector) {
    if (selector === '[data-mm-variant-config]') return info;
    if (selector === '[name="jform[category_id]"]') return category;
    if (selector === '#adminForm') return {};
    return null;
  },
  querySelectorAll() { redrawQueries++; return []; }
};
class MutationObserver {
  constructor(fn) { callback = fn; }
  observe(root, opts) {
    assert.equal(opts.childList, true);
    assert.equal(opts.subtree, true);
  }
}
vm.runInNewContext(source, { document, MutationObserver, JSON, Array, Object, Event },
  { timeout: 1000 });
assert.equal(typeof callback, 'function', 'observer must be registered');
assert.equal(warningChanges, 1, 'initial warning rendered once');

function flush(limit = 20) {
  let iterations = 0;
  while (mutations.length) {
    assert.ok(++iterations < limit, 'self-triggered observer causes infinite loop');
    callback(mutations.splice(0));
  }
}
flush();
assert.equal(warningChanges, 1, 'script must ignore its own warning DOM update');
const initialQueries = redrawQueries;
callback([{ addedNodes: [warningNode] }]);
assert.equal(redrawQueries, initialQueries, 'unrelated Joomla DOM changes ignored');
const hidden = {
  nodeType: 1,
  matches(selector) { return selector.includes('[selection]'); },
  querySelector() { return null; }
};
callback([{ addedNodes: [hidden] }]);
assert.equal(redrawQueries, initialQueries + 1, 'new Joomla subform row triggers refresh');
assert.equal(warningChanges, 1, 'subform refresh does not needlessly rewrite warning');
category.value = '2';
handlers.change();
assert.equal(warning.textContent, '', 'changing to a valid category clears warning');
flush();
assert.equal(warningChanges, 2, 'category changes do not recursively reload UI');
handlers.change();
flush();
assert.equal(warningChanges, 2, 'unchanged category does not mutate warning text');
console.log('VARIANT_ADMIN_OBSERVER_OK');
