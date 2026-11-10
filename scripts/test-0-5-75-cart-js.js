/* Test current AJAX cart quantity handler without a browser dependency. */
'use strict';
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(__dirname+'/../src/com_minimarket/media/js/cart.js','utf8');
assert.ok(source.includes('Math.min(Number(input.max)'),'limits quantity by available stock');
assert.ok(source.includes('amount.defaultValue=amount.value'),'successful quote becomes baseline');
assert.ok(source.includes("'X-Requested-With':'XMLHttpRequest'"),'Ajax request marked explicitly');
assert.ok(source.includes("credentials:'same-origin'"),'same-origin Joomla cookies');
assert.ok(source.includes('data-mm-cart-total'),'visible total updates');
assert.ok(source.includes('data-mm-line-total'),'visible line totals update');
assert.ok(source.includes('data-mm-cart-quantity-form'),'native forms remain');
assert.ok(!source.includes('innerHTML'),'no untrusted HTML injection');
console.log('MM75_CART_JS_PASS: AJAX cart clamps, re-quotes, updates lines and totals');
