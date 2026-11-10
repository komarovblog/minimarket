/* Native Joomla manual order editor. IDs and prices are verified server-side. */
(() => {
 'use strict';
 const init=()=>{
  const form=document.querySelector('#mm-manual-order-form');
  if(!form)return;
  const list=form.querySelector('#mm-manual-order-lines');
  const button=form.querySelector('#mm-manual-add-line');
  let seq=list?.querySelectorAll('[data-mm-manual-line]').length||0;
  button?.addEventListener('click',()=>{
   if(seq>=20||!list)return;
   const row=list.querySelector('[data-mm-manual-line]:last-child');
   if(!row)return;
   const copy=row.cloneNode(true);
   for(const element of copy.querySelectorAll('select,input')) {
    if(element.name)element.name=element.name.replace(/items\[\d+\]/,'items['+seq+']');
    if(element.id)element.id=element.id.replace(/\d+$/,String(seq));
    if(element.tagName==='SELECT')element.value='';
    if(element.type==='number')element.value='1';
   }
   for(const label of copy.querySelectorAll('label[for]')) {
     label.htmlFor=label.htmlFor.replace(/\d+$/,String(seq));
   }
   list.appendChild(copy);
   seq++;
   if(seq>=20)button.disabled=true;
  });
  list?.addEventListener('click',event=>{
   const btn=event.target.closest('[data-mm-manual-remove]');
   const rows=list.querySelectorAll('[data-mm-manual-line]');
   if(!btn||rows.length<=1)return;
   btn.closest('[data-mm-manual-line]')?.remove();
  });
  const selectMethod=()=>{
   const courier=form.querySelector('[name="delivery_method"]:checked')?.value==='courier';
   const target=form.querySelector('[data-mm-manual-courier]');
   const pickup=form.querySelector('[data-mm-manual-pickup]');
   if(target) {
    target.hidden=!courier;
    for(const field of target.querySelectorAll('select,textarea')) {
      field.disabled=!courier;
      field.required=courier;
    }
   }
   if(pickup)pickup.hidden=courier;
  };
  for(const input of form.querySelectorAll('[name="delivery_method"]')) {
   input.addEventListener('change',selectMethod);
  }
  selectMethod();
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
 else init();
})();
