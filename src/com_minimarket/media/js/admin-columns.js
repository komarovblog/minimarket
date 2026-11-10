/* List-column preferences for EVERY MiniMarket admin list, including orders.
 * Columns are stored per view in the admin's browser and never affect SQL. */
(() => {
  'use strict';
  const init=()=>{
    const isRu=(document.documentElement.lang||'').toLowerCase().startsWith('ru');
    const label=isRu?'Столбцы':'Columns';
    const all=isRu?'Показать все':'Show all';
    for (const [index,table] of [...document.querySelectorAll('table.mm-admin-list')].entries()) {
      const head=table.tHead?.rows?.[0];
      if(!head || !head.cells || head.cells.length<3)continue;
      const columns=[...head.cells];
      if(columns.some(cell=>cell.colSpan>1 || cell.rowSpan>1))continue;
      const id=table.id||('table-'+index);
      const view=new URLSearchParams(location.search).get('view')||'dashboard';
      const key='minimarket.admin.columns.v1.'+view+'.'+id;
      const locked=columns.map((cell,i)=>i===0 && !!cell.querySelector('input[type="checkbox"]'));
      const text=columns.map((cell,i)=>(cell.textContent||'').trim()||('#'+(i+1)));
      let visibility=columns.map(()=>true);
      try{
        const saved=JSON.parse(localStorage.getItem(key)||'null');
        if(Array.isArray(saved) && saved.length===columns.length)
          visibility=visibility.map((_,i)=>locked[i]?true:saved[i]!==false);
      }catch(_){}
      if(!visibility.some(Boolean))visibility[0]=true;

      const details=document.createElement('details');
      details.className='mm-columns-picker mb-2';
      const toggle=document.createElement('summary');
      toggle.className='btn btn-outline-secondary btn-sm';
      toggle.textContent=label;
      details.appendChild(toggle);
      const menu=document.createElement('div');
      menu.className='mm-columns-picker__menu';
      details.appendChild(menu);
      const apply=()=>{
        const visibleRows=[...table.rows];
        for(const row of visibleRows) {
          if(row.cells.length!==columns.length)continue;
          [...row.cells].forEach((cell,i)=>{
            cell.hidden=!visibility[i];
            cell.style.display=visibility[i]?'':'none';
          });
        }
        try{localStorage.setItem(key,JSON.stringify(visibility));}catch(_){}
      };
      columns.forEach((column,i)=>{
        const item=document.createElement('label');
        item.className='mm-columns-picker__item';
        const input=document.createElement('input');
        input.type='checkbox';
        input.checked=visibility[i];
        input.disabled=locked[i];
        input.addEventListener('change',()=>{
          visibility[i]=input.checked;
          if(!visibility.some(Boolean)){visibility[i]=true;input.checked=true;}
          apply();
        });
        item.append(input,document.createTextNode(' '+text[i]));
        menu.appendChild(item);
      });
      const reset=document.createElement('button');
      reset.type='button';
      reset.className='btn btn-outline-secondary btn-sm mt-2';
      reset.textContent=all;
      reset.addEventListener('click',()=>{
        visibility=columns.map(()=>true);
        for(const checkbox of menu.querySelectorAll('input[type="checkbox"]'))checkbox.checked=true;
        apply();
      });
      menu.appendChild(reset);
      table.parentElement?.insertBefore(details,table);
      apply();
    }
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
  else init();
})();
