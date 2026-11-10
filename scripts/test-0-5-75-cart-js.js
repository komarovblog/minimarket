/* Test real cart auto-save event handler without a browser dependency. */
'use strict';
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(__dirname+'/../src/com_minimarket/media/js/cart.js','utf8');
const input={value:'15',defaultValue:'1',max:'5',listeners:{},
  addEventListener(name,fn){this.listeners[name]=fn;}};
const form={submits:0,listeners:{},
  querySelector(sel){return sel==='[data-mm-cart-quantity]'?input:null;},
  addEventListener(name,fn){this.listeners[name]=fn;},
  requestSubmit(){this.submits++;}};
const document={readyState:'complete',
  querySelectorAll(sel){return sel==='[data-mm-cart-quantity-form]'?[form]:[];}};
let scheduled=null;
vm.runInNewContext(source,{document,setTimeout(fn){scheduled=fn;return 1;},clearTimeout(){scheduled=null;}});
input.listeners.change();
assert.equal(input.value,'5','quantity clamped to stock');
assert.equal(form.submits,1,'change triggers automatic POST');
input.value='2';input.listeners.change();
assert.equal(form.submits,1,'rapid subsequent event cannot double-submit');
assert.ok(source.includes('setTimeout(save,750)'),'typing is debounced');
console.log('MM75_CART_JS_PASS: stock clamp and single auto-submit');
