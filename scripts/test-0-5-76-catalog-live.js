'use strict';
/** Exercise live root sort through its delegated DOM event, without a browser. */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const code=fs.readFileSync(__dirname+'/../src/com_minimarket/media/js/catalog-live.js','utf8');
const events={};const requests=[];const historyCalls=[];const navigations=[];
const form={action:'https://shop.local/index.php?option=com_minimarket&view=catalog'};
const select={value:'price_desc'};
const content={replaced:false,replaceWith(){this.replaced=true;}};
const root={
  querySelector(sel){
    if(sel==='[data-mm-root-content]')return content;
    if(sel==='[data-mm-root-sort-form]')return form;
    if(sel==='[data-mm-root-sort]')return select;
    return null;
  },
  addEventListener(name,fn){events[name]=fn;},
  setAttribute(){},removeAttribute(){}
};
const window={
  location:{href:'https://shop.local/index.php?option=com_minimarket&view=catalog',
    assign(url){navigations.push(url);}},
  addEventListener(){}
};
const document={
  querySelector(sel){return sel==='.com-minimarket-catalog'?root:null;},
  importNode(node){return node;}
};
class DOMParser{
  parseFromString(){
    return {querySelector(sel) {
      return sel==='.com-minimarket-catalog [data-mm-root-content]'?{}:null;
    }};
  }
}
const history={pushState(_a,_b,url){historyCalls.push(url);}};
async function fetchMock(url,opts){
  requests.push({url,opts});
  return {ok:true,text:async()=>'<section data-mm-root-content></section>'};
}
vm.runInNewContext(code,{
  document,window,history,DOMParser,URL,AbortController,fetch:fetchMock
});
assert.equal(typeof events.change,'function','sort change handler is attached');
events.change({target:{matches(sel){return sel==='[data-mm-root-sort]';}}});
setImmediate(()=>{
  assert.equal(requests.length,1,'select changing fetches once');
  assert.match(requests[0].url,/mm_order=price_desc/,'server receives selected sort');
  assert.equal(content.replaced,true,'only results section is replaced');
  assert.equal(navigations.length,0,'no full page reload on success');
  assert.equal(historyCalls.length,1,'sort state is reflected in browser history');
  assert.match(requests[0].url,/option=com_minimarket/,'uses configured catalog route');
  console.log('MM76_ROOT_AJAX_SORT_PASS: change sorted full server catalog without reload');
});
