/* Form/media regression: variant cover and nested photo strip Joomla metadata. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const code = fs.readFileSync('src/com_minimarket/media/js/clean-media.js', 'utf8');
const source = 'images/banners/banner.jpg';
const suffix = '#joomlaImage://local-images/banners/banner.jpg?width=1140&height=600';
const values = [
  'jform[image]',
  'jform[og_image]',
  'jform[gallery][row0][path]',
  'jform[variants][row0][image]',
  'jform[variants][row0][photos][row0][path]'
];
const media = values.map((name) => {
  const field = {
    calls: 0,
    setValue(v) { this.calls++; input.value = v; }
  };
  const input = {
    name, value: source + suffix,
    closest(selector) { return selector === 'joomla-field-media' ? field : null; },
    matches(selector) { return selector.split(',').some((part) => {
      const q = part.trim();
      // A selector in clean-media.js includes only names/prefixes/suffixes.
      const full = q.match(/name="([^"]+)"/);
      const prefix = q.match(/name\^="([^"]+)"/);
      const end = q.match(/name\$="([^"]+)"/);
      return (!full || name === full[1])
        && (!prefix || name.startsWith(prefix[1]))
        && (!end || name.endsWith(end[1]))
        && !!(full || prefix || end);
    }); }
  };
  return { input, field };
});
const listeners = {};
let selector = '';
const doc = {
  readyState: 'complete',
  querySelectorAll(value) {
    selector = value;
    return media.map((v) => v.input).filter((v) => v.matches(selector));
  },
  addEventListener(name, fn) { listeners[name] = fn; }
};
const win = { addEventListener() {} };
vm.runInNewContext(code, { document: doc, window: win, setTimeout: fn => fn() },
  { timeout: 1000 });
assert.equal(typeof listeners.change, 'function');
assert.equal(media.length, 5);
media.forEach(({input,field}) => {
  assert.equal(input.value, source, 'Joomla Media fragment removed for '+input.name);
  assert.equal(field.calls, 1, 'native Media API used for '+input.name);
});
media[3].input.value = source + suffix;
listeners.change({ target: media[3].input });
assert.equal(media[3].input.value, source, 'dynamic variant cover cleaned on change');
media[4].input.value = source + suffix;
listeners.input({ target: media[4].input });
assert.equal(media[4].input.value, source, 'nested variant gallery photo cleaned on input');
media.forEach(({input}) => assert.equal(input.value.includes('#joomlaImage://'), false));
console.log('VARIANT_MEDIA_CLEANUP_OK');
