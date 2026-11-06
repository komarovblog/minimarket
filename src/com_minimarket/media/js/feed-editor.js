/* Progressive-enhancement for the native Joomla feed selection form. */
(() => {
 'use strict';
 const root=document.querySelector('#mm-feed-editor');
 if(!root)return;
 const categories=Array.from(root.querySelectorAll('[data-mm-feed-category]'));
 const products=Array.from(root.querySelectorAll('[data-mm-feed-product]'));
 const mode=root.querySelector('#mm-feed-mode');
 const descendants=root.querySelector('[data-mm-feed-descendants]');
 const bundles=root.querySelector('[data-mm-feed-bundles]');
 const count=root.querySelector('[data-mm-feed-count]');
 const search=root.querySelector('[data-mm-feed-search]');
 const parents=new Map(categories.map(c=>[Number(c.dataset.id),Number(c.dataset.parent)]));
 const categorySet=()=>{
   const chosen=new Set(categories.filter(c=>c.checked).map(c=>Number(c.value)));
   if(!descendants.checked)return chosen;
   for(const id of parents.keys()){
     const visited=new Set([id]);let parent=parents.get(id);
     while(parent && !visited.has(parent)){
       if(chosen.has(parent)){chosen.add(id);break;}
       visited.add(parent);parent=parents.get(parent);
     }
   }
   return chosen;
 };
 const updateCount=()=>{
   if(count)count.textContent=String(products.filter(p=>p.checked).length);
 };
 const updateAuto=()=>{
   if(mode.value!=='auto'){updateCount();return;}
   const selected=categorySet();
   products.forEach(p=>{
     p.checked=selected.has(Number(p.dataset.category))
       && (bundles.checked||p.dataset.bundle!=='1');
   });
   updateCount();
 };
 for(const c of categories)c.addEventListener('change',updateAuto);
 descendants.addEventListener('change',updateAuto);
 bundles.addEventListener('change',updateAuto);
 mode.addEventListener('change',()=>{
   if(mode.value==='auto')updateAuto();else updateCount();
 });
 root.querySelector('[data-mm-feed-all]')?.addEventListener('click',()=>{
   mode.value='manual';products.forEach(p=>p.checked=true);updateCount();
 });
 root.querySelector('[data-mm-feed-none]')?.addEventListener('click',()=>{
   mode.value='manual';products.forEach(p=>p.checked=false);updateCount();
 });
 for(const p of products)p.addEventListener('change',updateCount);
 search?.addEventListener('input',()=>{
   const text=search.value.trim().toLowerCase();
   for(const row of root.querySelectorAll('[data-mm-feed-row]')){
     row.hidden=!row.dataset.search.includes(text);
   }
 });
 updateCount();
})();
