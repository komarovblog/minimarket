/* Simulated DOM coverage for six Joomla grid selectors, no browser required. */
'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const sizes=['xs','sm','md','lg','xl','xxl'];
const inputs={};
function makeSelect(name,value){
  const listeners={};
  const group={classList:{classes:new Set(),toggle(className,on){
    if(on)this.classes.add(className);else this.classes.delete(className);
  }}};
  const parent={mirror:null,querySelector(s){
    const match=/^\[data-mm-grid-mirror="(.+)"\]$/.exec(s);
    return match&&this.mirror?.dataset.mmGridMirror===match[1]?this.mirror:null;
  }};
  return {
    name,value,disabled:false,group,parentElement:parent,listeners,
    attributes:{},closest(){return group;},
    setAttribute(name,value){this.attributes[name]=value;},
    addEventListener(event,handler){(listeners[event]??=[]).push(handler);},
    fire(event){for(const fn of listeners[event]??[])fn({target:this});},
    insertAdjacentElement(position,el){
      assert.equal(position,'afterend');
      parent.mirror=el;el.remove=()=>{parent.mirror=null;};
    }
  };
}
inputs['jform[category_product_layout]']=makeSelect('jform[category_product_layout]','list');
inputs['jform[product_layout]']=makeSelect('jform[product_layout]','inherit');
for(const [prefix,initial] of [['category_',3],['',-1]]){
  for(const size of sizes){
    const name='jform['+prefix+'cards_'+size+']';
    inputs[name]=makeSelect(name,String(initial));
  }
}
const document={
  readyState:'complete',
  querySelector(s){
    const match=/^\[name="(.+)"\]$/.exec(s);
    return match?inputs[match[1]]??null:null;
  },
  createElement(tag){
    assert.equal(tag,'input');
    return {type:'',name:'',disabled:false,dataset:{},value:'',remove(){}};
  }
};
const source=fs.readFileSync(
  __dirname+'/../src/com_minimarket/media/js/category-grid-settings.js','utf8'
);
vm.runInNewContext(source,{document,console},{filename:'category-grid-settings.js'});
let checks=0;
function check(condition,message){checks++;assert.ok(condition,message);}
function fields(prefix){
  return sizes.map(size=>inputs['jform['+prefix+'cards_'+size+']']);
}
check(fields('category_').every(i=>i.disabled),'global list disables all six');
check(fields('').every(i=>i.disabled),'category inherit disables all six');
check(fields('category_').every(i=>i.parentElement.mirror?.value===i.value),
  'global disabled selects use hidden mirrors');
check(fields('').every(i=>i.parentElement.mirror?.value===i.value),
  'category overrides survive disabled form submits');
const globalLayout=inputs['jform[category_product_layout]'];
globalLayout.value='grid';globalLayout.fire('change');
check(fields('category_').every(i=>!i.disabled),'global grid enables six');
check(fields('category_').every(i=>!i.parentElement.mirror),
  'enabled fields have no hidden duplicates');
globalLayout.value='list';globalLayout.fire('change');
check(fields('category_').every(i=>i.disabled),'back to global list');
const categoryLayout=inputs['jform[product_layout]'];
categoryLayout.value='grid';categoryLayout.fire('change');
check(fields('').every(i=>!i.disabled),'category grid enables six');
inputs['jform[cards_xl]'].value='6';
categoryLayout.value='inherit';categoryLayout.fire('change');
check(inputs['jform[cards_xl]'].parentElement.mirror?.value==='6',
  'category inherit preserves changed local override');
check(fields('').every(i=>i.disabled),'category inherit disables all again');
categoryLayout.value='grid';categoryLayout.fire('change');
check(inputs['jform[cards_xl]'].value==='6' &&
  !inputs['jform[cards_xl]'].parentElement.mirror,
  're-enabled override restores value without duplicate input');
categoryLayout.value='list';categoryLayout.fire('change');
check(fields('').every(i=>i.disabled),'category list disables six');
console.log('MM71_GRID_UI_OK: '+checks+' checks');
