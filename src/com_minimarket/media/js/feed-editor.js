/* MiniMarket feed editor: selection and browsing are independent. */
(() => {
 'use strict';
 const root=document.querySelector('#mm-feed-editor');
 if(!root)return;
 const categories=Array.from(root.querySelectorAll('[data-mm-feed-category]'));
 const products=Array.from(root.querySelectorAll('[data-mm-feed-product]'));
 const rows=Array.from(root.querySelectorAll('[data-mm-feed-row]'));
 const categoryRows=Array.from(root.querySelectorAll('[data-mm-feed-category-row]'));
 const mode=root.querySelector('#mm-feed-mode');
 const provider=root.querySelector('#mm-feed-provider');
 const sitemapHelp=root.querySelector('[data-mm-feed-sitemap-help]');
 const updateProvider=()=>{if(sitemapHelp)sitemapHelp.hidden=provider?.value!=='sitemap';};
 provider?.addEventListener('change',updateProvider);
 updateProvider();
 const bundles=root.querySelector('[data-mm-feed-bundles]');
 const count=root.querySelector('[data-mm-feed-count]');
 const search=root.querySelector('[data-mm-feed-search]');
 const categorySearch=root.querySelector('[data-mm-feed-category-search]');
 const productFilter=root.querySelector('[data-mm-feed-product-filter]');
 const selectedCategories=()=>new Set(categories.filter(c=>c.checked).map(c=>Number(c.value)));
 const updateCount=()=>{
   if(count)count.textContent=String(products.filter(p=>p.checked).length);
 };
 const applyBrowseFilter=()=>{
   const text=(search?.value||'').trim().toLowerCase();
   const filter=new Set(Array.from(productFilter?.selectedOptions||[]).map(x=>Number(x.value)));
   for(const row of rows){
     const product=row.querySelector?.('[data-mm-feed-product]');
     const matchText=(row.dataset.search||'').includes(text);
     const matchCategory=!filter.size||Boolean(product && filter.has(Number(product.dataset.category)));
     row.hidden=!(matchText&&matchCategory);
   }
 };
 const applyCategorySearch=()=>{
   const term=(categorySearch?.value||'').trim().toLowerCase();
   for(const row of categoryRows)row.hidden=!(row.dataset.search||'').includes(term);
 };
 const updateAuto=()=>{
   if(mode?.value!=='auto'){updateCount();return;}
   const selected=selectedCategories();
   for(const p of products){
     p.checked=selected.has(Number(p.dataset.category)) && (bundles?.checked||p.dataset.bundle!=='1');
   }
   updateCount();
 };
 for(const c of categories)c.addEventListener('change',updateAuto);
 bundles?.addEventListener('change',updateAuto);
 mode?.addEventListener('change',updateAuto);
 const categoryToggle=checked=>{
   for(const c of categories){
     const row=c.closest?.('[data-mm-feed-category-row]');
     if(!row||!row.hidden)c.checked=checked;
   }
   updateAuto();
 };
 root.querySelector('[data-mm-feed-cats-all]')?.addEventListener('click',()=>categoryToggle(true));
 root.querySelector('[data-mm-feed-cats-none]')?.addEventListener('click',()=>categoryToggle(false));
 const productToggle=checked=>{
   if(mode)mode.value='manual';
   for(let i=0;i<products.length;i++){
     const row=products[i].closest?.('[data-mm-feed-row]')||rows[i];
     if(!row||!row.hidden)products[i].checked=checked;
   }
   updateCount();
 };
 root.querySelector('[data-mm-feed-all]')?.addEventListener('click',()=>productToggle(true));
 root.querySelector('[data-mm-feed-none]')?.addEventListener('click',()=>productToggle(false));
 for(const p of products)p.addEventListener('change',()=>{
   if(mode)mode.value='manual';
   updateCount();
 });
 search?.addEventListener('input',applyBrowseFilter);
 productFilter?.addEventListener('change',applyBrowseFilter);
 root.querySelector('[data-mm-feed-product-filter-reset]')?.addEventListener('click',()=>{
   for(const option of Array.from(productFilter?.options||[]))option.selected=false;
   applyBrowseFilter();
 });
 categorySearch?.addEventListener('input',applyCategorySearch);
 applyBrowseFilter();applyCategorySearch();updateCount();
})();
