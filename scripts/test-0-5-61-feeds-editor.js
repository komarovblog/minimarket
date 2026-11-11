/* Feed editor selection + independent browsing regressions in a DOM mock. */
'use strict';
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
let n=0;const ok=(yes,label)=>{assert.ok(yes,label);n++;};
function element(data={}){
 return {dataset:data,checked:false,value:'',hidden:false,handlers:{},options:[],selectedOptions:[],
  addEventListener(type,cb){this.handlers[type]=cb;},
  emit(type){this.handlers[type]?.();},
  closest(sel){return this.row||null;}};
}
const cats=[1,2,3].map(id=>element({id:String(id)}));
cats.forEach((c,i)=>{c.value=String(i+1);c.row={hidden:false};});cats[0].checked=true;
const products=[1,2,2,3].map((cat,i)=>element({category:String(cat),bundle:i===2?'1':'0'}));
const rows=['alpha','beta','charlie','delta'].map((name,i)=>({
 dataset:{search:name},hidden:false,querySelector(){return products[i];}
}));
products.forEach((p,i)=>p.row=rows[i]);
const categoryRows=cats.map((c,i)=>({dataset:{search:'category '+(i+1)},hidden:false}));
const mode=element();mode.value='auto';
const bundles=element();bundles.checked=true;
const counter={textContent:'0'},search=element(),categorySearch=element();
const productFilter=element();const all=element(),none=element(),catsAll=element(),catsNone=element(),reset=element();
productFilter.options=[1,2,3].map(value=>({value:String(value),selected:false}));
const root={
 querySelectorAll(q){return ({
  '[data-mm-feed-category]':cats,'[data-mm-feed-product]':products,
  '[data-mm-feed-row]':rows,'[data-mm-feed-category-row]':categoryRows
 })[q]||[];},
 querySelector(q){return ({
  '#mm-feed-mode':mode,'[data-mm-feed-bundles]':bundles,
  '[data-mm-feed-count]':counter,'[data-mm-feed-search]':search,
  '[data-mm-feed-category-search]':categorySearch,
  '[data-mm-feed-product-filter]':productFilter,
  '[data-mm-feed-product-filter-reset]':reset,
  '[data-mm-feed-all]':all,'[data-mm-feed-none]':none,
  '[data-mm-feed-cats-all]':catsAll,'[data-mm-feed-cats-none]':catsNone
 })[q]||null;}
};
const document={querySelector(q){return q==='#mm-feed-editor'?root:null;}};
vm.runInNewContext(fs.readFileSync(__dirname+'/../src/com_minimarket/media/js/feed-editor.js','utf8'),{document});
cats[0].emit('change');
ok(products[0].checked&&!products[1].checked&&!products[2].checked,
 'choosing a parent category does not automatically add descendants');
cats[1].checked=true;cats[1].emit('change');
ok(products.slice(0,3).every(p=>p.checked),'multiple categories select all direct products');
bundles.checked=false;bundles.emit('change');
ok(!products[2].checked&&products[1].checked,'bundle toggle works');
categorySearch.value='category 3';categorySearch.emit('input');
ok(categoryRows[0].hidden && !categoryRows[2].hidden,'category search is independent');
catsAll.emit('click');ok(cats[2].checked&&!cats[0].checked,'select visible categories only');
categorySearch.value='';categorySearch.emit('input');
catsNone.emit('click');ok(cats.every(c=>!c.checked),'clear visible category selections');
cats[0].checked=true;cats[0].emit('change');
productFilter.options[1].selected=true;productFilter.selectedOptions=[productFilter.options[1]];
productFilter.emit('change');
ok(rows[0].hidden&&!rows[1].hidden,'product category browse filter does not change selection');
ok(cats[0].checked&&!cats[1].checked,'browsing preserves selected feed categories');
none.emit('click');
ok(mode.value==='manual' && !products[1].checked,'product clear affects visible rows and is persisted as manual');
search.value='beta';search.emit('input');ok(rows[1].hidden===false&&rows[2].hidden,'product text search intersects category filter');
reset.emit('click');ok(productFilter.options.every(x=>!x.selected),'product browse category filter reset');
console.log('MM61_FEED_EDITOR_DOM_OK: '+n+' cases');
