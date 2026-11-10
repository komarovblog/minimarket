/* Separate promo and normal video editors without creating a second DB. */
(() => {
 'use strict';
 const init=()=>{
  const root=document.querySelector('[data-mm-promo-video-editor]');
  if(!root)return;
  const toggle=root.querySelector('[data-mm-promo-toggle]');
  const fields=root.querySelector('[data-mm-promo-fields]');
  const ru=(document.documentElement.lang||'').toLowerCase().startsWith('ru');
  if(!toggle||!fields)return;
  const add=ru?'Добавить промо-видео':'Add promo video';
  const remove=ru?'Убрать промо-видео':'Remove promo video';
  const paint=()=>{toggle.textContent=fields.hidden?add:remove;};
  toggle.addEventListener('click',()=>{
    if(!fields.hidden) {
      for(const input of fields.querySelectorAll('input,textarea,select')) {
        if(input.type==='checkbox'||input.type==='radio')input.checked=false;
        else if(input.tagName==='SELECT')input.selectedIndex=0;
        else input.value='';
        input.dispatchEvent(new Event('change',{bubbles:true}));
      }
    }
    fields.hidden=!fields.hidden;paint();
  });
  paint();
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();
