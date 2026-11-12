/* DOM-level promo interaction regression: real click/keyboard handlers without Joomla. */
'use strict';
const fs=require('node:fs');
const vm=require('node:vm');
const path=require('node:path');
const assert=require('node:assert/strict');
const source=fs.readFileSync(path.join(__dirname,'../src/com_minimarket/media/js/promo-video.js'),'utf8');
let checks=0;
function test(ok,label){assert.ok(ok,label);checks++;}
function setup(reduced=false){
  const on=(obj)=>{obj.handlers={};obj.addEventListener=(name,cb)=>obj.handlers[name]=cb;
    obj.emit=(name,event={})=>obj.handlers[name]?.(event);return obj;};
  const attrs={};
  const player=on({muted:false,paused:false,plays:0,removed:[],
    play(){this.plays++;this.paused=false;return Promise.resolve();},
    pause(){this.paused=true;},
    removeAttribute(name){this.removed.push(name);},
    setAttribute(name,value){attrs[name]=value;},
    getAttribute(name){return attrs[name]}
  });
  const expand=on({values:{},
    setAttribute(name,val){this.values[name]=val},
    getAttribute(name){return this.values[name]}});
  const close=on({});
  const panel={hidden:true};
  const classes=new Set();
  const root={hidden:false,
    classList:{contains(name){return classes.has(name)},toggle(name,on){
      if(on)classes.add(name);else classes.delete(name);
    }},
    querySelector(sel){return ({
      '[data-mm-promo-player]':player,
      '[data-mm-promo-expand]':expand,
      '[data-mm-promo-close]':close,
      '[data-mm-promo-panel]':panel
    })[sel]||null;}
  };
  const document=on({readyState:'complete',hidden:false,
    querySelector(sel){return sel==='[data-mm-promo]'?root:null}});
  const window={matchMedia(){return {matches:reduced}}};
  vm.runInNewContext(source,{document,window,console,Promise});
  return {root,player,expand,close,panel,document};
}
let a=setup();
test(a.player.plays===0 && a.player.paused===true,'promo never autoplays on page');
a.player.emit('click');
test(a.player.plays===1 && a.player.muted===true && a.panel.hidden===false && a.root.classList.contains('is-expanded'),
 'clicking video expands floating promo, not only the separate button');
test(a.player.getAttribute('aria-expanded')==='true'
 && a.expand.getAttribute('aria-expanded')==='true','both controls update accessibility status');
let prevented=0;
a.player.emit('keydown',{key:' ',preventDefault(){prevented++}});
test(prevented===1 && a.panel.hidden===true,'Space collapses video and prevents page scrolling');
a.player.emit('keydown',{key:'Enter',preventDefault(){prevented++}});
test(prevented===2 && a.panel.hidden===false,'Enter expands promo for keyboard users');
a.expand.emit('click');
test(a.panel.hidden===true,'explicit expand button still works');
a.document.hidden=true;a.document.emit('visibilitychange');
test(a.player.paused===true,'tab hiding pauses preview');
a.document.hidden=false;a.document.emit('visibilitychange');
test(a.player.plays===2,'returning to visible tab never auto-resumes playback');
a.close.emit('click');
test(a.root.hidden===true && a.player.paused===true,'close hides and stops promo');
a.document.emit('visibilitychange');
test(a.player.plays===2,'closed promo never restarts playback');
const b=setup(true);
test(b.player.plays===0 && b.player.removed.includes('autoplay') && b.player.removed.includes('loop'),
 'all users get no autoplay or loop');
b.player.emit('click');
test(b.panel.hidden===false,'reduced-motion user can still expand explicitly');
const template=fs.readFileSync(path.join(__dirname,'../src/com_minimarket/site/tmpl/product/default.php'),'utf8');
test(template.includes('role="button" tabindex="0" aria-expanded="false"') &&
 template.includes('data-mm-promo-player'),
 'real markup exposes video surface to keyboard and screen readers');
test(template.includes('href="#mm-cart-add-form"') &&
 template.includes('data-mm-cart-form') &&
 template.includes('if ($canOrder) :'),
 'promo buy links to the real physical SKU cart form, not a fake checkout');
console.log('PROMO_VIDEO_DOM_OK: '+checks+' checks');
