/* Browser-level logic regression for dependent option selects; no Joomla runtime.
 * Simulates native <select> options and dispatches actual change callbacks.
 */
'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');

const file = require('node:path').resolve(__dirname, '../src/com_minimarket/media/js/variants.js');
const source = fs.readFileSync(file, 'utf8');
let checks = 0;
const test = (condition, message) => {
  assert.ok(condition, message);
  checks += 1;
};
const mock = (variants, startId, stockMode, productStock, optionValues) => {
  const axes = Object.entries(optionValues).map(([axis, values]) => {
    const handlers = {};
    return {
      dataset: { mmVariantAxis: axis },
      options: values.map(value => ({ value, disabled: false })),
      value: variants.find(item => item.id === startId)?.selection[axis] || values[0],
      addEventListener(name, handler) { handlers[name] = handler; },
      change(value) { this.value = value; handlers.change?.(); },
      disabled(value) { return this.options.find(option => option.value === value)?.disabled; }
    };
  });
  const nodes = {
    '[data-mm-variant-sku]': { textContent: '' },
    '[data-mm-variant-price]': { textContent: '' },
    '[data-mm-variant-stock]': { textContent: '' }
  };
  const status = { textContent: '' };
  const root = {
    dataset: {
      mmSelected: String(startId),
      mmVariantsData: JSON.stringify(variants),
      mmGalleryBase: '[]',
      mmRoot: 'https://example.test/',
      mmStockMode: String(stockMode),
      mmStockBase: String(productStock),
      mmInStock: 'In stock',
      mmOutOfStock: 'Out of stock'
    },
    querySelectorAll(selector) { return selector === '[data-mm-variant-axis]' ? axes : []; },
    querySelector(selector) { return selector === '[data-mm-variant-availability]' ? status : null; }
  };
  const document = {
    baseURI: 'https://example.test/',
    querySelector(selector) {
      if (selector === '[data-mm-variants]') return root;
      return nodes[selector] || null;
    }
  };
  vm.runInNewContext(source, { document, URL }, { filename: file });
  const options = Object.fromEntries(axes.map(s => [s.dataset.mmVariantAxis, s]));
  return {options, nodes, status};
};

const variants = [
  {id: 1, sku:'RED-S', price:'100.00', stock:5, selection:{color:'red',size:'S'}, photos:[]},
  {id: 2, sku:'RED-M', price:'200.00', stock:0, selection:{color:'red',size:'M'}, photos:[]},
  {id: 3, sku:'BLUE-M', price:'300.00', stock:7, selection:{color:'blue',size:'M'}, photos:[]},
  {id: 4, sku:'BLUE-L', price:'400.00', stock:2, selection:{color:'blue',size:'L'}, photos:[]}
];
const axes = {color:['red','blue','green'], size:['S','M','L']};

const { options, nodes, status } = mock(variants,1,1,99,axes);
test(options.color.disabled('green'), 'unrepresented primary color disabled');
test(!options.color.disabled('blue'), 'other color with valid SKU stays reachable');
test(!options.size.disabled('S'), 'only available red size enabled');
test(options.size.disabled('M'), 'red M with zero stock disabled in per-combination mode');
test(options.size.disabled('L'), 'nonexistent red L disabled');
test(nodes['[data-mm-variant-sku]'].textContent==='RED-S','initial SKU selected');

options.color.change('blue');
test(options.color.value==='blue', 'switching to Blue is allowed with previously red-only size');
test(options.size.value==='M', 'size automatically corrected to existing Blue M');
test(options.size.disabled('S'), 'Blue S disabled when no such SKU exists');
test(!options.size.disabled('L'), 'Blue L enabled');
test(nodes['[data-mm-variant-price]'].textContent==='300.00', 'price changes with corrected SKU');
options.size.change('L');
test(nodes['[data-mm-variant-sku]'].textContent==='BLUE-L', 'choosing available size changes SKU');
test(nodes['[data-mm-variant-stock]'].textContent==='2', 'per-SKU stock displayed');
options.color.change('red');
test(options.size.value==='S', 'returning Red automatically chooses sole available size');
test(options.size.disabled('M'), 'out-of-stock Red M is disabled again');
options.size.change('M');
test(options.size.value==='S', 'synthetic impossible selection cannot create fake SKU');
test(status.textContent==='In stock','available SKU remains orderable');

const shared = mock(variants,1,0,11,axes);
test(!shared.options.size.disabled('M'),'shared inventory unlocks Red M despite SKU zero stock');
test(shared.options.size.disabled('L'),'nonexistent Red L stays disabled with shared inventory');
shared.options.size.change('M');
test(shared.nodes['[data-mm-variant-sku]'].textContent==='RED-M', 'distinct SKU preserved in shared stock');
test(shared.nodes['[data-mm-variant-stock]'].textContent==='11', 'shared stock displayed instead of SKU stock');

const empty = mock(variants,2,1,99,axes);
test(empty.nodes['[data-mm-variant-sku]'].textContent==='RED-M','deep link to sold-out published SKU retains selection');
test(empty.status.textContent==='Out of stock','sold-out SKU explains unavailability');
test(empty.options.size.disabled('M'),'sold-out size is not offered as orderable');
empty.options.color.change('blue');
test(empty.options.size.value==='M','sold-out deep link can switch to an in-stock color');

const none = mock(variants,1,0,0,axes);
test(none.options.color.disabled('red') && none.options.color.disabled('blue'),
  'no stock anywhere disables choices in shared mode');
test(none.status.textContent==='Out of stock','shared zero stock visible');

const three = mock([
  {id:11,sku:'RED-S-COTTON',price:'1',stock:2,selection:{color:'red',size:'S',fabric:'cotton'}},
  {id:12,sku:'RED-M-SILK',price:'2',stock:2,selection:{color:'red',size:'M',fabric:'silk'}},
  {id:13,sku:'BLUE-L-COTTON',price:'3',stock:2,selection:{color:'blue',size:'L',fabric:'cotton'}}
],11,1,5,{color:['red','blue'],size:['S','M','L'],fabric:['cotton','silk']});
test(three.options.fabric.disabled('silk'),'third option limited to selected Red S');
three.options.size.change('M');
test(three.options.fabric.value==='silk','changing Size repairs incompatible Fabric');
test(three.options.fabric.disabled('cotton'),'third option Cotton disabled for Red M');
three.options.color.change('blue');
test(three.options.size.value==='L' && three.options.fabric.value==='cotton',
  'changing first axis corrects two later axes without a dead end');

console.log('STOREFRONT_OPTION_DEPENDENCIES_PASS: '+checks+' cases');
