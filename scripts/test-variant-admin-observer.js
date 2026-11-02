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
const addButton = {
  disabled: false,
  attributes: {},
  closest() { return variantForm; },
  setAttribute(name, value) { this.attributes[name] = value; },
  removeAttribute(name) { delete this.attributes[name]; }
};
const photoAdd = {
  disabled: false,
  closest() { return { nested: true }; },
  setAttribute() {},
  removeAttribute() {}
};
const variantForm = {
  querySelectorAll() { return [addButton, photoAdd]; }
};
const setupLink = { hidden: false };
const editor = {
  querySelector(sel) { return sel === '.subform-repeatable' ? variantForm : null; },
  contains() { return true; }
};
const info = {
  dataset: { mmNoAxes: 'Configure variant attributes', mmTooManyAxes: 'Too many',
    mmVariantConfig: JSON.stringify({
    '1': {},
    '2': { '10': {
      title: 'Размер',
      options: [{ value: 't:S', title: 'S' }]
    }}
  })},
  closest() { return editor; },
  querySelector(selector) {
    return selector === '[data-mm-variant-setup-link]' ? setupLink : warning;
  }
};
const handlers = {};
const category = {
  value: '1',
  addEventListener(name, fn) { handlers[name] = fn; }
};
const document = {
  documentElement: { lang: 'ru-RU' },
  addEventListener(name, fn) { handlers['document:' + name] = fn; },
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
assert.equal(warning.textContent, 'Configure variant attributes', 'clear missing-axis guidance');
assert.equal(addButton.disabled, true, 'cannot add SKU without variant-defining attributes');
assert.equal(setupLink.hidden, false, 'attribute setup link shown when no axis');
assert.equal(photoAdd.disabled, false, 'nested photo subform remains unaffected');

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
assert.equal(addButton.disabled, false, 'SKU Add enabled once a variant axis exists');
assert.equal(setupLink.hidden, true, 'attribute setup link hidden when ready');
assert.equal(photoAdd.disabled, false, 'nested photo Add never disabled');
flush();
assert.equal(warningChanges, 2, 'category changes do not recursively reload UI');
handlers.change();
flush();
assert.equal(warningChanges, 2, 'unchanged category does not mutate warning text');
console.log('VARIANT_ADMIN_OBSERVER_OK');
