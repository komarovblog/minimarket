/* Native input event regressions in a tiny Node DOM mock, no browser library. */
'use strict';
const fs=require('node:fs'),vm=require('node:vm');
const assert=require('node:assert/strict');
let n=0;
const ok=(yes,msg)=>{assert.ok(yes,msg);n++;};
function element(data={}){
 const o={dataset:data,checked:false,handlers:{},value:'',hidden:false,
  addEventListener(type,cb){this.handlers[type]=cb;},
  emit(type){this.handlers[type]?.({type});}};
 return o;
}
const c1=element({id:'1',parent:'0'});c1.value='1';c1.checked=true;
const c2=element({id:'2',parent:'1'});c2.value='2';
const c3=element({id:'3',parent:'0'});c3.value='3';
const p1=element({category:'1',bundle:'0'});
const p2=element({category:'2',bundle:'0'});
const p3=element({category:'2',bundle:'1'});
const p4=element({category:'3',bundle:'0'});
const mode=element();mode.value='auto';
const desc=element();desc.checked=true;
const bundles=element();bundles.checked=true;
const counter={textContent:'0'};
const search=element();search.value='';
const all=element(),none=element();
const rows=['alpha','beta','charlie','delta'].map(x=>({dataset:{search:x},hidden:false}));
const root={
 querySelectorAll(q){return ({
  '[data-mm-feed-category]':[c1,c2,c3],
  '[data-mm-feed-product]':[p1,p2,p3,p4],
  '[data-mm-feed-row]':rows
 })[q]||[];},
 querySelector(q){return ({
  '#mm-feed-mode':mode,'[data-mm-feed-descendants]':desc,
  '[data-mm-feed-bundles]':bundles,
  '[data-mm-feed-count]':counter,'[data-mm-feed-search]':search,
  '[data-mm-feed-all]':all,'[data-mm-feed-none]':none
 })[q]||null;}
};
const document={querySelector(q){return q==='#mm-feed-editor'?root:null;}};
const source=fs.readFileSync(__dirname+'/../src/com_minimarket/media/js/feed-editor.js','utf8');
vm.runInNewContext(source,{document});
c1.emit('change');
ok(p1.checked&&p2.checked&&p3.checked&&!p4.checked,
 'selected root includes direct and nested products');
ok(counter.textContent==='3','counter reflects published checked products');
bundles.checked=false;bundles.emit('change');
ok(!p3.checked&&p2.checked,'bundle exclusion updates UI');
desc.checked=false;desc.emit('change');
ok(p1.checked&&!p2.checked,'descendants switch excludes nested products');
c3.checked=true;c3.emit('change');
ok(p4.checked&&p1.checked,'two independent categories drive auto selection');
none.emit('click');
ok(mode.value==='manual'&&[p1,p2,p3,p4].every(p=>!p.checked),
 'clear all enters manual mode and unchecks products');
p2.checked=true;p2.emit('change');
ok(counter.textContent==='1','manual single inclusion updates counter');
all.emit('click');
ok(mode.value==='manual'&&[p1,p2,p3,p4].every(p=>p.checked),
 'select all enters manual mode');
search.value='be';search.emit('input');
ok(rows[1].hidden===false&&rows[0].hidden===true,'search filters rows only');
mode.value='auto';mode.emit('change');
ok(p1.checked&&p4.checked&&!p2.checked,
 'restoring auto recomputes category selection');
console.log('MM61_FEED_EDITOR_DOM_OK: '+n+' cases');
