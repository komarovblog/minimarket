/* MiniMarket feed editor: category scope, selective bulk, independent browsing. */
'use strict';
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
let n=0;const ok=(yes,label)=>{assert.ok(yes,label);n++;};
function element(data={}){
 return {dataset:data,checked:false,value:'',hidden:false,open:false,textContent:'',
 handlers:{},
 addEventListener(type,fn){this.handlers[type]=fn;},
 emit(type){this.handlers[type]?.();},
 closest(){return this.row||null;},
 querySelector(){return {focus(){}};}};
}
const cats=[1,2,3].map(id=>element({id:String(id)}));
cats.forEach((c,i)=>{c.value=String(i+1);c.checked=i===0;});
const categoryRows=cats.map((c,i)=>({dataset:{search:'category '+(i+1)},hidden:false}));
cats.forEach((c,i)=>c.row=categoryRows[i]);
const products=[1,2,2,3].map((cat,i)=>element({category:String(cat),bundle:i===2?'1':'0'}));
products.forEach((p,i)=>{p.value=String(i+10);p.checked=i===0;});
const rows=['alpha','beta','charlie','delta'].map((name,i)=>({
 dataset:{search:name},hidden:false,querySelector(){return products[i];}
}));
products.forEach((p,i)=>p.row=rows[i]);
const filterRows=[1,2,3].map(()=>({hidden:false}));
const options=[1,2,3].map((v,i)=>{const x=element();x.value=String(v);x.row=filterRows[i];return x;});
const bundles=element();bundles.checked=true;
const count={textContent:'0'},catCount={textContent:'0'},filterCount={textContent:'',hidden:true};
const search=element(),categorySearch=element(),filter=element(),reset=element();
const all=element(),none=element(),catsAll=element(),catsNone=element();
const empty=element(),noProducts=element(),provider=element(),sitemapHelp=element();
const root={
 querySelectorAll(q){return ({
  '[data-mm-feed-category]':cats,'[data-mm-feed-category-row]':categoryRows,
  '[data-mm-feed-product]':products,'[data-mm-feed-row]':rows,
  '[data-mm-feed-product-filter-option]':options
 })[q]||[];},
 querySelector(q){return ({
  '#mm-feed-provider':provider,'[data-mm-feed-sitemap-help]':sitemapHelp,
  '[data-mm-feed-bundles]':bundles,
  '[data-mm-feed-category-count]':catCount,'[data-mm-feed-count]':count,
  '[data-mm-feed-search]':search,'[data-mm-feed-category-search]':categorySearch,
  '[data-mm-feed-product-filter]':filter,
  '[data-mm-feed-product-filter-count]':filterCount,
  '[data-mm-feed-filter-empty]':empty,
  '[data-mm-feed-no-products]':noProducts,
  '[data-mm-feed-product-filter-reset]':reset,
  '[data-mm-feed-all]':all,'[data-mm-feed-none]':none,
  '[data-mm-feed-cats-all]':catsAll,'[data-mm-feed-cats-none]':catsNone
 })[q]||null;}
};
const document={querySelector(q){return q==='#mm-feed-editor'?root:null;}};
vm.runInNewContext(fs.readFileSync(__dirname+'/../src/com_minimarket/media/js/feed-editor.js','utf8'),{document});
ok(count.textContent==='1' && catCount.textContent==='1' && products[0].checked,
 'initial selection matches category and product counters');
ok(filterRows[0].hidden===false && filterRows[1].hidden===true,
 'right category filter initially lists selected categories only');
ok(rows[1].hidden && !rows[0].hidden,
 'right list initially contains only products from selected categories');
cats[1].checked=true;cats[1].emit('change');
ok(products[1].checked && products[2].checked && !rows[1].hidden,
 'checking another category shows and selects its products');
ok(catCount.textContent==='2' && count.textContent==='3',
 'category/product counters respond to category selection');
ok(!filterRows[1].hidden,
 'additional checked category becomes available in right filter');
bundles.checked=false;bundles.emit('change');
ok(!products[2].checked && rows[2].hidden && products[1].checked,
 'disabling bundles removes them from visible eligible list');
categorySearch.value='category 3';categorySearch.emit('input');
ok(categoryRows[0].hidden && !categoryRows[2].hidden && cats[0].checked,
 'left search filters category rows without changing selections');
catsAll.emit('click');
ok(cats.every(c=>c.checked),
 'select all checks only visible category and preserves hidden checks');
catsNone.emit('click');
ok(!cats[2].checked && cats[0].checked && cats[1].checked,
 'clear categories affects visible only');
categorySearch.value='';categorySearch.emit('input');
cats[1].checked=false;cats[1].emit('change');
ok(rows[1].hidden && !products[1].checked && filterRows[1].hidden,
 'deselected category disappears from products and right filter');
cats[1].checked=true;cats[1].emit('change');
ok(products[1].checked && !filterRows[1].hidden,
 'rechecking category restores previous product selection');
options[1].checked=true;options[1].emit('change');
ok(rows[0].hidden && !rows[1].hidden && options[1].checked,
 'right category filter limits visibility without altering left selection');
ok(filterCount.textContent==='1' && !filterCount.hidden,
 'filter badge counts selected browse categories');
options[0].checked=true;options[0].emit('change');
ok(!rows[0].hidden && !rows[1].hidden,
 'right category filter allows multiple categories using OR');
none.emit('click');
ok(!products[0].checked && !products[1].checked && catCount.textContent==='2',
 'clear visible products preserves categories but records product exclusions');
reset.emit('click');
ok(options.every(o=>!o.checked)&&filterCount.hidden,
 'reset right filter changes browsing only');
ok(!products[0].checked&&!products[1].checked,
 'product exclusions survive browsing filter reset');
search.value='beta';search.emit('input');
ok(rows[0].hidden&&!rows[1].hidden&&rows[2].hidden,
 'product search combines with selected categories and bundle status');
ok(!products[0].checked && cats[0].checked,
 'product search never resets checked categories or products');
all.emit('click');
ok(products[1].checked && !products[0].checked,
 'bulk select only matches currently visible product');
cats[1].checked=false;cats[1].emit('change');
cats[1].checked=true;cats[1].emit('change');
ok(products[1].checked && !products[0].checked,
 'manual product overrides persist through category changes');
search.value='';search.emit('input');
bundles.checked=true;bundles.emit('change');
ok(products[2].checked && !rows[2].hidden,
 'reenabling bundles restores default selection and visibility');
catsNone.emit('click');
ok(cats.every(c=>!c.checked) && rows.every(r=>r.hidden),
 'no selected categories yields an empty product pool');
ok(!empty.hidden && !noProducts.hidden,
 'empty category and product messages are visible');
console.log('MM61_FEED_EDITOR_DOM_OK: '+n+' cases');
