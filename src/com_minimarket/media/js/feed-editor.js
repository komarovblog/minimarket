/* MiniMarket feed editor. Categories define the product pool; searches and the
 * right-hand multi-category control affect visibility only, never selection. */
(() => {
 'use strict';
 const root=document.querySelector('#mm-feed-editor');
 if(!root)return;
 const categories=Array.from(root.querySelectorAll('[data-mm-feed-category]'));
 const categoryRows=Array.from(root.querySelectorAll('[data-mm-feed-category-row]'));
 const products=Array.from(root.querySelectorAll('[data-mm-feed-product]'));
 const rows=Array.from(root.querySelectorAll('[data-mm-feed-row]'));
 const categorySearch=root.querySelector('[data-mm-feed-category-search]');
 const productSearch=root.querySelector('[data-mm-feed-search]');
 const bundles=root.querySelector('[data-mm-feed-bundles]');
 const categoryCount=root.querySelector('[data-mm-feed-category-count]');
 const productCount=root.querySelector('[data-mm-feed-count]');
 const filterOptions=Array.from(root.querySelectorAll('[data-mm-feed-product-filter-option]'));
 const filterCount=root.querySelector('[data-mm-feed-product-filter-count]');
 const filter=root.querySelector('[data-mm-feed-product-filter]');
 const filterEmpty=root.querySelector('[data-mm-feed-filter-empty]');
 const noProducts=root.querySelector('[data-mm-feed-no-products]');
 const provider=root.querySelector('#mm-feed-provider');
 const sitemapHelp=root.querySelector('[data-mm-feed-sitemap-help]');
 const selectedCategories=()=>new Set(categories.filter(c=>c.checked).map(c=>Number(c.value)));
 // Preserve manually excluded items while category filters/searches change.
 // Disabled bundles are not saved as exclusions and can be restored later.
 const initiallySelected=selectedCategories();
 const excluded=new Set(products.filter(p=>
    initiallySelected.has(Number(p.dataset.category)) && !p.checked &&
    (bundles?.checked || p.dataset.bundle!=='1')));
 const eligible=(p,selected)=>selected.has(Number(p.dataset.category)) &&
    (bundles?.checked || p.dataset.bundle!=='1');
 const updateCounts=(selected)=>{
   if(categoryCount)categoryCount.textContent=String(selected.size);
   if(productCount)productCount.textContent=String(products.filter(p=>p.checked&&eligible(p,selected)).length);
 };
 const updateBrowse=()=>{
   const selected=selectedCategories();
   const query=(productSearch?.value||'').trim().toLowerCase();
   const filtered=new Set(filterOptions.filter(o=>o.checked&&selected.has(Number(o.value)))
     .map(o=>Number(o.value)));
   if(filterCount){
     filterCount.textContent=String(filtered.size);
     filterCount.hidden=filtered.size===0;
   }
   let visible=0;
   for(const row of rows){
     const p=row.querySelector?.('[data-mm-feed-product]');
     const category=p?Number(p.dataset.category):0;
     const show=Boolean(p && eligible(p,selected) &&
       (!filtered.size||filtered.has(category)) &&
       (row.dataset.search||'').includes(query));
     row.hidden=!show;
     if(show)visible++;
   }
   if(noProducts)noProducts.hidden=visible>0;
   updateCounts(selected);
 };
 const syncFilterChoices=()=>{
   const selected=selectedCategories();
   for(const option of filterOptions){
     const allowed=selected.has(Number(option.value));
     const row=option.closest?.('[data-mm-feed-product-filter-row]');
     if(row)row.hidden=!allowed;
     if(!allowed)option.checked=false;
   }
   if(filterEmpty)filterEmpty.hidden=selected.size>0;
   if(!selected.size&&filter)filter.open=false;
 };
 const syncProducts=()=>{
   const selected=selectedCategories();
   for(const p of products)p.checked=eligible(p,selected)&&!excluded.has(p);
   syncFilterChoices();
   updateBrowse();
 };
 const updateCategorySearch=()=>{
   const term=(categorySearch?.value||'').trim().toLowerCase();
   for(const row of categoryRows)row.hidden=!(row.dataset.search||'').includes(term);
 };
 for(const c of categories)c.addEventListener('change',syncProducts);
 bundles?.addEventListener('change',syncProducts);
 categorySearch?.addEventListener('input',updateCategorySearch);
 productSearch?.addEventListener('input',updateBrowse);
 const toggleCategories=checked=>{
   for(const c of categories){
     const row=c.closest?.('[data-mm-feed-category-row]');
     if(row&&!row.hidden)c.checked=checked;
   }
   syncProducts();
 };
 root.querySelector('[data-mm-feed-cats-all]')?.addEventListener('click',()=>toggleCategories(true));
 root.querySelector('[data-mm-feed-cats-none]')?.addEventListener('click',()=>toggleCategories(false));
 const toggleProducts=checked=>{
   for(const row of rows){
     if(row.hidden)continue;
     const p=row.querySelector?.('[data-mm-feed-product]');
     if(!p)continue;
     p.checked=checked;
     if(checked)excluded.delete(p);else excluded.add(p);
   }
   updateCounts(selectedCategories());
 };
 root.querySelector('[data-mm-feed-all]')?.addEventListener('click',()=>toggleProducts(true));
 root.querySelector('[data-mm-feed-none]')?.addEventListener('click',()=>toggleProducts(false));
 for(const p of products)p.addEventListener('change',()=>{
   if(p.checked)excluded.delete(p);else excluded.add(p);
   updateCounts(selectedCategories());
 });
 for(const option of filterOptions)option.addEventListener('change',updateBrowse);
 root.querySelector('[data-mm-feed-product-filter-reset]')?.addEventListener('click',()=>{
   for(const option of filterOptions)option.checked=false;
   updateBrowse();
 });
 filter?.addEventListener('keydown',event=>{
   if(event.key==='Escape'){
     filter.open=false;
     filter.querySelector('summary')?.focus();
   }
 });
 const updateProvider=()=>{if(sitemapHelp)sitemapHelp.hidden=provider?.value!=='sitemap';};
 provider?.addEventListener('change',updateProvider);
 updateProvider();updateCategorySearch();syncProducts();
})();
