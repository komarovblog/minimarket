/* Searchable single-brand/collection/bundle-component fields.
 * Every list-row action is a symbol with a translated accessible label.
 * Toolbar buttons outside lists retain an explicit text description. */
(() => {
 'use strict';
 const el=(tag,cls,text)=>{
   const out=document.createElement(tag);
   if(cls)out.className=cls;
   if(text!==undefined)out.textContent=String(text);
   return out;
 };
 const cents=value=>Math.max(0,Math.round(Number(value||0)*100));
 const fmt=value=>(value/100).toFixed(2);
 function mount(root){
   const mode=root.dataset.mmPicker;
   const words=JSON.parse(root.dataset.mmLabels||'{}');
   const chosen=root.querySelector('[data-mm-selected]');
   const finder=root.querySelector('[data-mm-finder]');
   const results=root.querySelector('[data-mm-results]');
   const status=root.querySelector('[data-mm-message]');
   const searchField=root.querySelector('[data-mm-query]');
   const category=root.querySelector('[data-mm-category]');
   const inputName=root.dataset.mmInput;
   if(!chosen||!finder||!results||!searchField||!inputName)return;
   // All item pickers place Add above search above chosen rows.
   const addAction=root.querySelector('[data-mm-add]');
   if(addAction)root.insertBefore(addAction,chosen);
   root.insertBefore(finder,chosen);
   let next=chosen.querySelectorAll('[data-mm-key]').length;
   let pending=null, request=0;
   const has=key=>Array.from(chosen.querySelectorAll('[data-mm-key]')).some(x=>x.dataset.mmKey===String(key));
   const label=item=>item.sku?item.title+' · '+item.sku:item.title;
   const totalBoxes=Array.from(document.querySelectorAll('[data-mm-bundle-totals]'));
   const updateTotals=()=>{
     if(mode!=='bundle')return;
     let total=0;
     for(const row of chosen.querySelectorAll('[data-mm-key]')){
       const count=Math.max(0,Number(row.querySelector('input[type="number"]')?.value||0));
       const line=cents(row.dataset.mmUnitPrice)*count;
       total+=line;
       const target=row.querySelector('[data-mm-line-total]');
       if(target)target.textContent=fmt(line/1);
     }
     const form=root.closest('form');
     const pricing=form?.querySelector('[name="jform[bundle_price_mode]"]');
     const discount=form?.querySelector('[name="jform[bundle_discount_pct]"]');
     const fixed=form?.querySelector('[name="jform[price]"]');
     const modePrice=pricing?.value||'fixed';
     const pct=Math.min(100,Math.max(0,Number(discount?.value||0)));
     const price=modePrice==='discount'?Math.round(total*(1-pct/100)):cents(fixed?.value);
     for(const totalBox of totalBoxes){
       totalBox.hidden=totalBox.hasAttribute('data-mm-discount-only') && modePrice!=='discount';
       const sum=totalBox.querySelector('[data-mm-bundle-components-total]');
       const final=totalBox.querySelector('[data-mm-bundle-final-total]');
       const label=totalBox.querySelector('[data-mm-bundle-final-label]');
       if(sum)sum.textContent=fmt(total);
       if(final)final.textContent=fmt(price);
       if(label)label.textContent=modePrice==='discount'
         ?label.dataset.mmDiscountLabel:label.dataset.mmFixedLabel;
     }
   };
   chosen.addEventListener('click',ev=>{
     const button=ev.target.closest('[data-mm-remove]');
     if(button){
       ev.preventDefault();
       button.closest('[data-mm-key]')?.remove();
       updateTotals();
     }
   });
   chosen.addEventListener('input',updateTotals);
   if(mode==='bundle'){
     const form=root.closest('form');
     for(const name of ['jform[bundle_price_mode]','jform[bundle_discount_pct]','jform[price]']){
       const field=Array.from(form?.querySelectorAll('input,select')||[]).find(x=>x.name===name);
       if(field){field.addEventListener('input',updateTotals);field.addEventListener('change',updateTotals);}
     }
   }
   function append(item){
     if(has(item.key))return;
     if(mode==='brand')chosen.replaceChildren();
     if(chosen.querySelectorAll('[data-mm-key]').length>=(mode==='bundle'?40:250)){
       status.textContent=words.limit||'Selection limit reached';return;
     }
     const row=el('div','mm-entity-picker__row');
     row.dataset.mmKey=String(item.key);
     const link=el('a','',item.title);
     link.href='index.php?option=com_minimarket&task='
       +(mode==='bundle'?'product':mode==='brand'?'brand':'collection')
       +'.edit&id='+Number(item.product_id||item.id);
     if(mode==='bundle'){
       const productCell=el('div','mm-bundle-product');
       productCell.append(link);
       if(item.sku)productCell.append(el('small','',item.sku));
       row.append(productCell);
     }else{
       row.append(link);
       if(item.sku)row.append(el('small','',item.sku));
     }
     const hidden=el('input');
     hidden.type='hidden';
     if(mode==='bundle'){
       const prefix=inputName+'[row'+next+++']';
       hidden.name=prefix+'[component]';hidden.value=item.key;
       row.dataset.mmUnitPrice=String(item.price||'0.00');
       row.append(el('span','mm-bundle-unit-price',
         (words.unit||'Price')+': '+String(item.price||'0.00')));
       row.append(hidden);
       const quantityLabel=el('label','mm-bundle-quantity');
       quantityLabel.append(el('span','visually-hidden',words.quantity||'Quantity'));
       const qty=el('input','form-control form-control-sm');
       qty.name=prefix+'[quantity]';qty.type='number';qty.min='1';
       qty.max='1000';qty.step='1';qty.required=true;qty.value='1';
       quantityLabel.append(qty);row.append(quantityLabel);
       row.append(el('strong','mm-bundle-line-total'));
       row.lastElementChild.dataset.mmLineTotal='';
     }else{
       hidden.name=mode==='brand'?inputName:inputName+'[]';
       hidden.value=item.key;
       row.append(hidden);
     }
     const remove=el('button','btn btn-danger btn-sm','−');
     remove.type='button';remove.dataset.mmRemove='';
     remove.setAttribute('aria-label',words.remove||'Remove');
     row.append(remove);
     chosen.append(row);status.textContent='';
     updateTotals();
   }
   async function search(){
     const index=++request;
     pending?.abort();pending=new AbortController();
     results.replaceChildren(el('p','text-muted',words.loading||'Searching…'));
     status.textContent='';
     const url=new URL(root.dataset.mmUrl,window.location.href);
     url.searchParams.set('q',searchField.value.slice(0,100));
     if(category)url.searchParams.set('category_id',category.value);
     if(mode==='bundle')url.searchParams.set('current_id',root.dataset.mmCurrent||'0');
     try{
       const response=await fetch(url.toString(),{
         method:'GET',credentials:'same-origin',headers:{Accept:'application/json'},
         signal:pending.signal
       });
       if(!response.ok)throw Error('HTTP '+response.status);
       const json=await response.json();
       if(index!==request)return;
       results.replaceChildren();
       const items=Array.isArray(json.items)?json.items:[];
       for(const item of items){
         if(typeof item.key!=='string'||!/^(product:|variant:)?[1-9]\d*$/.test(item.key))continue;
         const row=el('div','mm-entity-picker__result');
         row.append(el('span','',label(item)));
         if(mode==='bundle'&&item.price!==undefined) row.append(el('small','',String(item.price)));
         const add=el('button','btn btn-success btn-sm','+');
         add.type='button';add.title=words.add||'Add';
         add.setAttribute('aria-label',(words.add||'Add')+' '+label(item));
         add.disabled=has(item.key);
         add.addEventListener('click',()=>{append(item);add.disabled=true;});
         row.append(add);results.append(row);
       }
       if(!results.children.length)results.append(el('p','text-muted',
          items.length?(words.all||'All added'):(words.empty||'No results')));
     }catch(error){
       if(error.name!=='AbortError'){
         results.replaceChildren();
         status.textContent=words.error||'Search failed';
       }
     }
   }
   root.querySelector('[data-mm-add]')?.addEventListener('click',()=>{
     finder.hidden=!finder.hidden;
     if(!finder.hidden){searchField.focus();void search();}
   });
   root.querySelector('[data-mm-search]')?.addEventListener('click',search);
   searchField.addEventListener('keydown',event=>{
     if(event.key==='Enter'){event.preventDefault();void search();}
   });
   category?.addEventListener('change',search);
   updateTotals();
 }
 function boot(){document.querySelectorAll('[data-mm-picker]').forEach(mount);}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);
 else boot();
})();
