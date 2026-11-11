/* Same paged and searchable SKU picker as the MiniMarket bundle editor. */
(() => {
 'use strict';
 function init(){
  const form=document.querySelector('#mm-manual-order-form');
  if(!form)return;
  const list=form.querySelector('#mm-manual-order-lines');
  const addButton=form.querySelector('#mm-manual-add-line');
  if(!list)return;
  let seq=list.querySelectorAll('[data-mm-manual-line]').length;
  const setMethod=()=>{
   const isCourier=form.querySelector('[name="delivery_method"]:checked')?.value==='courier';
   const panel=form.querySelector('[data-mm-manual-courier]');
   const pickup=form.querySelector('[data-mm-manual-pickup]');
   if(panel){
    panel.hidden=!isCourier;
    panel.querySelectorAll('input,select,textarea').forEach(field=>{
     field.disabled=!isCourier;
     if(field.name==='delivery_city'||field.name==='delivery_address')field.required=isCourier;
    });
   }
   if(pickup)pickup.hidden=isCourier;
  };
  function choose(row,item){
   if(!/^[1-9]\d{0,9}:(?:0|[1-9]\d{0,9})$/.test(String(item.key||'')))return;
   row.querySelector('[data-mm-manual-key]').value=item.key;
   const selected=row.querySelector('[data-mm-manual-selected]');
   selected.replaceChildren();
   const label=document.createElement('span');
   label.textContent=item.title+(item.subtitle?' · '+item.subtitle:'');
   selected.append(label);
   const remove=document.createElement('button');
   remove.type='button';remove.className='btn btn-danger btn-sm ms-2';remove.textContent='−';
   remove.setAttribute('aria-label',list.dataset.mmManualPickerRemove||'−');
   remove.addEventListener('click',()=>{
    row.querySelector('[data-mm-manual-key]').value='';
    selected.replaceChildren();
   });
   selected.append(remove);
   row.querySelector('[data-mm-manual-results]').replaceChildren();
  }
  const requests=new WeakMap();
  async function search(row){
   const old=requests.get(row);
   old?.abort();
   const controller=new AbortController();
   requests.set(row,controller);
   const root=new URL(list.dataset.mmManualPickerUrl,location.href);
   root.searchParams.set('q',row.querySelector('[data-mm-manual-search]').value.slice(0,100));
   const results=row.querySelector('[data-mm-manual-results]');
   results.textContent='…';
   try{
    const response=await fetch(root.toString(),{
     credentials:'same-origin',headers:{Accept:'application/json'},signal:controller.signal
    });
    if(!response.ok)throw new Error(String(response.status));
    const json=await response.json();
    results.replaceChildren();
    for(const item of json.items||[]){
     const wrapper=document.createElement('div');
     wrapper.className='mm-entity-picker__result';
     const name=document.createElement('span');
     name.className='flex-grow-1';
     name.textContent=item.title+(item.subtitle?' · '+item.subtitle:'');
     const chooseButton=document.createElement('button');
     chooseButton.className='btn btn-success btn-sm';chooseButton.type='button';
     chooseButton.textContent='+';
     chooseButton.setAttribute('aria-label',(list.dataset.mmManualPickerAdd||'+')+' '+name.textContent);
     chooseButton.addEventListener('click',()=>choose(row,item));
     wrapper.append(name,chooseButton);results.append(wrapper);
    }
    if(!results.children.length)results.textContent=list.dataset.mmManualPickerEmpty||'—';
   }catch(error){if(error.name!=='AbortError')results.textContent=list.dataset.mmManualPickerError||'—';}
  }
  addButton?.addEventListener('click',()=>{
   if(seq>=20)return;
   const row=list.querySelector('[data-mm-manual-line]');
   if(!row)return;
   const copy=row.cloneNode(true);
   for(const field of copy.querySelectorAll('input')){
    if(field.name)field.name=field.name.replace(/items\[\d+\]/,'items['+seq+']');
    if(field.id)field.id=field.id.replace(/\d+$/,String(seq));
    if(field.matches('[data-mm-manual-key],[data-mm-manual-search]'))field.value='';
    if(field.type==='number')field.value='1';
   }
   for(const label of copy.querySelectorAll('label[for]')){
    label.htmlFor=label.htmlFor.replace(/\d+$/,String(seq));
   }
   copy.querySelector('[data-mm-manual-selected]')?.replaceChildren();
   copy.querySelector('[data-mm-manual-results]')?.replaceChildren();
   list.append(copy);seq++;
   if(seq>=20)addButton.disabled=true;
   copy.querySelector('[data-mm-manual-search]')?.focus();
  });
  list.addEventListener('click',event=>{
   const query=event.target.closest('[data-mm-manual-query]');
   if(query){void search(query.closest('[data-mm-manual-line]'));return;}
   const remove=event.target.closest('[data-mm-manual-remove]');
   if(remove){
    if(list.querySelectorAll('[data-mm-manual-line]').length>1){
     requests.get(remove.closest('[data-mm-manual-line]'))?.abort();
     remove.closest('[data-mm-manual-line]').remove();
     if(addButton)addButton.disabled=false;
    }
   }
  });
  list.addEventListener('keydown',event=>{
   if(event.key!=='Enter'||!event.target.matches('[data-mm-manual-search]'))return;
   event.preventDefault();void search(event.target.closest('[data-mm-manual-line]'));
  });
  form.querySelectorAll('[name="delivery_method"]').forEach(r=>r.addEventListener('change',setMethod));
  setMethod();
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
 else init();
})();