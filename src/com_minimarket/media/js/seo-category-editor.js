/* SEO landing category-bound property choices, independent of global dictionaries. */
(() => {
 'use strict';
 const form=document.getElementById('mm-seo-editor');
 const area=document.getElementById('mm-seo-category-choices');
 const hidden=document.getElementById('mm-seo-query');
 if(!form||!area||!hidden)return;
 const category=form.querySelector('input[name="category_id"]');
 const selected=form.querySelector('[data-mm-reference-picker] [data-mm-ref-selected]');
 let original={};
 try{original=JSON.parse(area.dataset.mmSeoSaved||'{}');}catch(_){}
 const originalCategory=Number(area.dataset.mmSeoOriginalCategory||0);
 let current=0,request=null,ready=false;
 const message=(text,css)=>{
   area.replaceChildren();
   const p=document.createElement('p');
   p.className=css||'form-text';p.textContent=text;
   area.append(p);
 };
 function render(groups,id){
   area.replaceChildren();
   if(!groups.length){message(area.dataset.mmSeoNone||'No available options or attributes');return;}
   for(const group of groups){
     const fieldset=document.createElement('fieldset');
     fieldset.className='border rounded p-3 mb-3';
     const legend=document.createElement('legend');
     legend.className='float-none w-auto px-2 fs-6';
     legend.textContent=String(group.title||'');fieldset.append(legend);
     const grid=document.createElement('div');grid.className='row g-2';
     for(const value of group.values||[]){
       const label=document.createElement('label');
       label.className='col-12 col-md-6 col-lg-4 form-check ms-2';
       const input=document.createElement('input');
       input.type='checkbox';input.className='form-check-input';
       input.dataset.mmSeoFacet=String(group.key);
       input.value=String(value.token);
       input.checked=id===originalCategory
         && Array.isArray(original[group.key])
         && original[group.key].includes(input.value);
       const span=document.createElement('span');
       span.className='form-check-label';span.textContent=' '+String(value.title||'');
       label.append(input,span);grid.append(label);
     }
     fieldset.append(grid);area.append(fieldset);
   }
 }
 async function refresh(){
   const id=Number(category?.value||0);
   const seq=++current;
   if(request)request.abort();
   ready=false;request=null;
   if(!Number.isSafeInteger(id)||id<1){message(area.dataset.mmSeoChoose||'Select category');return;}
   const controller=new AbortController();request=controller;
   message(area.dataset.mmSeoLoading||'Loading…');
   const url=new URL(area.dataset.mmSeoChoicesUrl,location.href);
   url.searchParams.set('category_id',String(id));
   try{
     const response=await fetch(url.toString(),{
       credentials:'same-origin',headers:{Accept:'application/json'},signal:controller.signal
     });
     if(!response.ok)throw new Error(String(response.status));
     const data=await response.json();
     if(seq!==current)return;
     render(Array.isArray(data.groups)?data.groups:[],id);
     ready=true;
   }catch(error){
     if(seq!==current||error.name==='AbortError')return;
     message(area.dataset.mmSeoError||'Unable to load category options','text-danger');
   }
 }
 // The existing native reference picker updates this list when an item changes.
 if(selected)new MutationObserver(()=>{void refresh();}).observe(selected,{childList:true});
 category?.addEventListener('change',()=>{void refresh();});
 function prepareQuery(){
   const filters=structuredClone(original);
   for(const key of Object.keys(filters))if(/^[as][0-9]+$/.test(key))delete filters[key];
   for(const input of area.querySelectorAll('[data-mm-seo-facet]:checked')){
     (filters[input.dataset.mmSeoFacet]??=[]).push(input.value);
   }
   delete filters.price;delete filters.stock;delete filters.sku;
   const range={};
   for(const el of form.querySelectorAll('[data-mm-seo-range="price"]')){
     if(el.value.trim()!=='')range[el.dataset.mmBound]=el.value.trim();
   }
   if(Object.keys(range).length)filters.price=range;
   const stock=form.querySelector('[data-mm-seo-stock]')?.value||'';
   if(stock)filters.stock=[stock];
   const sku=form.querySelector('[data-mm-seo-sku]')?.value.trim()||'';
   if(sku)filters.sku=sku;
   const params=new URLSearchParams();
   for(const [key,values] of Object.entries(filters)){
     if(!/^(?:a|s)[0-9]+$|^(?:price|stock|sku)$/.test(key))continue;
     if(Array.isArray(values)){
       for(const value of values)params.append('mmf['+key+'][]',String(value));
     }else if(values && typeof values==='object'){
       for(const [sub,val] of Object.entries(values)){
         if(Array.isArray(val)){
           for(const value of val)params.append('mmf['+key+']['+sub+'][]',String(value));
         }else params.append('mmf['+key+']['+sub+']',String(val));
       }
     }else if(values!==null)params.append('mmf['+key+']',String(values));
   }
   hidden.value=params.toString();
 }
 form.addEventListener('submit',event=>{
   if(!ready){event.preventDefault();return;}
   prepareQuery();
 });
 void refresh();
})();