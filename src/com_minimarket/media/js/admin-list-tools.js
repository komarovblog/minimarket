/* Shared list searchtools: accessible expand/collapse and sort/limit submit. */
(() => {
 'use strict';
 const init=()=>{
  document.querySelectorAll('.mm-native-searchtools').forEach(form=>{
   const button=form.querySelector('[data-mm-filter-toggle]');
   if(button){
    const target=document.getElementById(button.getAttribute('aria-controls'));
    if(target){
     button.addEventListener('click',()=>{
      const expanded=button.getAttribute('aria-expanded')==='true';
      target.hidden=expanded;
      button.setAttribute('aria-expanded',String(!expanded));
     });
    }
   }
   for(const field of form.querySelectorAll('[data-mm-autosubmit]')){
    field.addEventListener('change',()=>form.requestSubmit());
   }
  });
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
 else init();
})();
