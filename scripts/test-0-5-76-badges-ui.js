/* MiniMarket 0.5.76: badge flags remain editable and formats independent. */
'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../src/com_minimarket/media/js/product-badges.js','utf8');
const fields={};
function field(name,value='',checkbox=false) {
  const hint={hidden:true};
  const row={hidden:false,classList:{toggle(){}},querySelector(sel) {
    return sel==='[data-mm-badge-sale-hint]'?hint:null;
  }};
  const input={name:'jform['+name+']',value,checked:false,disabled:false,
    required:false,listeners:{},closest(sel){return sel==='.control-group'?row:null;},
    addEventListener(event,fn){this.listeners[event]=fn;},
    fire(event){this.listeners[event]?.();}};
  fields[name]={input,row,hint,checkbox};
  return input;
}
field('old_price','');
field('price','1490.00');
field('badge_sale','1',true);
field('badge_sale_display','percent');
field('badge_benefit','1',true);
field('badge_benefit_display','money');
field('badge_new','1',true);
field('badge_new_days','');
field('badge_popular','1',true);
field('badge_popular_days','');
field('bundle_price_mode','fixed');
field('bundle_discount_pct','0');
const form={querySelector(query){
  const name=query.match(/jform\[([^\]]+)\]/)?.[1];
  const match=fields[name];
  if (!match) return null;
  if (query.startsWith('input[type="checkbox"]') && !match.checkbox) return null;
  return match.input;
}};
const doc={readyState:'complete',getElementById(id){return id==='adminForm'?form:null;}};
vm.runInNewContext(source,{document:doc});
assert.equal(fields.badge_sale.input.disabled,false,'sale toggle stays active before old price is set');
assert.equal(fields.badge_sale_display.row.hidden,false,'percent/ruble choice visible even when flag unchecked');
fields.badge_sale.input.checked=true;
fields.badge_sale.input.fire('change');
assert.equal(fields.badge_sale.hint.hidden,false,'a warning appears if old price is missing');
fields.old_price.input.value='1990.00';
fields.old_price.input.fire('input');
assert.equal(fields.badge_sale.input.checked,true,'editing old price does not clear manual sale selection');
assert.equal(fields.badge_sale.hint.hidden,true,'valid old price resolves warning');
fields.bundle_price_mode.input.value='discount';
fields.bundle_price_mode.input.fire('change');
assert.equal(fields.badge_sale.input.disabled,true,'calculated bundle cannot claim manual sale');
assert.equal(fields.badge_sale_display.row.hidden,true,'manual sale units hidden for auto-discount bundle');
assert.equal(fields.badge_benefit.input.disabled,false,'savings enabled for auto-discount bundle');
assert.equal(fields.badge_benefit_display.row.hidden,false,'savings money/percent choice always available');
console.log('MM76_PRODUCT_BADGE_UI_PASS: sale eligibility, manual choice, units, bundle mode');
