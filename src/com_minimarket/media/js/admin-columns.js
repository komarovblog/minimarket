/* Small, native-color column selector for operational Joomla lists. */
(() => {
 'use strict';
 function init(){
  for(const control of document.querySelectorAll('[data-mm-columns-for]')){
   const id=control.dataset.mmColumnsFor,table=document.getElementById(id);
   if(!table)continue;
   const headers=[...table.querySelectorAll('thead > tr:first-child > th')];
   const panel=control.querySelector('[data-mm-columns-options]');
   if(!headers.length||!panel)continue;
   let saved={};
   try{saved=JSON.parse(localStorage.getItem('mm-columns-'+id)||'{}')||{};}catch{}
   const toggle=(index,visible)=>{
    for(const row of table.querySelectorAll('tr')){
     const cell=row.children[index];if(cell)cell.style.display=visible?'':'none';
    }
   };
   const inputs=[];
   for(let i=1;i<headers.length;i++){
    const name=headers[i].textContent.trim()||String(i);
    const checkbox=document.createElement('input');
    checkbox.type='checkbox';checkbox.className='form-check-input me-2';
    checkbox.checked=saved[i]!==false;
    checkbox.setAttribute('aria-label',name);
    const label=document.createElement('label');
    label.className='form-check py-1 mb-0';
    label.append(checkbox,document.createTextNode(name));
    checkbox.addEventListener('change',()=>{
     saved[i]=checkbox.checked;toggle(i,checkbox.checked);
     try{localStorage.setItem('mm-columns-'+id,JSON.stringify(saved));}catch{}
    });
    panel.append(label);inputs.push([i,checkbox]);
    toggle(i,checkbox.checked);
   }
   const reset=document.createElement('button');
   reset.type='button';reset.className='btn btn-outline-secondary btn-sm mt-2';
   reset.textContent=control.dataset.mmColumnsReset||'Reset';
   reset.addEventListener('click',()=>{
    saved={};
    try{localStorage.removeItem('mm-columns-'+id);}catch{}
    for(const [i,input] of inputs){input.checked=true;toggle(i,true);}
   });
   panel.append(reset);
  }
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
 else init();
})();
