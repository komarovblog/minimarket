/* Shared list searchtools: accessible expand/collapse and sort/limit submit. */
(() => {
 'use strict';
 const init=()=>{
  // Custom status actions retain Joomla submit handling, but use the
  // same success/danger palette as native Publish/Unpublish buttons.
  const green=['orders.confirm','deliveries.deliver','reviews.publish',
               'promocodes.publish','seofilters.publish'];
  const red=['orders.cancel','orders.trash','deliveries.cancel','reviews.reject',
             'promocodes.unpublish','seofilters.unpublish'];
  for(const button of document.querySelectorAll('#toolbar button, .toolbar button, .subhead button, .header-btns button')){
   const target=[button.getAttribute('onclick'),button.getAttribute('data-task'),
                 button.getAttribute('task'),button.parentElement?.getAttribute('task')]
                .filter(Boolean).join(' ');
   const color=green.some(t=>target.includes(t))?'success'
        :red.some(t=>target.includes(t))?'danger':null;
   if(color){
    button.classList.remove('btn-primary','btn-secondary','btn-outline-primary');
    button.classList.add(color==='success'?'btn-success':'btn-danger');
   }
  }
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
