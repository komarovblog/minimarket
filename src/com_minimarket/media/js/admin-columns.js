/* Native Joomla-style, per-list visible columns dropdown. No SQL changes. */
(() => {
  'use strict';
  const ready=()=>{
    const ru=(document.documentElement.lang||'').toLowerCase().startsWith('ru');
    const noun=ru?'Столбцов':'Columns';
    const showAll=ru?'Показать все':'Show all';
    const list=[...document.querySelectorAll('table.mm-admin-list')];
    for(const [index,table] of list.entries()) {
      if(!table.tHead?.rows?.[0])continue;
      const columns=[...table.tHead.rows[0].cells];
      if(columns.length<2 || columns.some(c=>c.colSpan>1||c.rowSpan>1))continue;
      const view=new URLSearchParams(location.search).get('view')||'dashboard';
      const key='minimarket.admin.columns.v2.'+view+'.'+(table.id||index);
      const labels=columns.map((c,i)=>(c.textContent||'').trim()||('#'+(i+1)));
      const locked=columns.map((c,i)=>i===0 && !!c.querySelector('input[type="checkbox"]'));
      let visible=columns.map(()=>true);
      try{
        const saved=JSON.parse(localStorage.getItem(key)||'null');
        if(Array.isArray(saved)&&saved.length===visible.length) {
          visible=visible.map((_,i)=>locked[i]||saved[i]!==false);
        }
      }catch(_){}
      if(!visible.some(Boolean))visible[0]=true;

      const toolbar=document.createElement('div');
      toolbar.className='mm-columns-toolbar d-flex justify-content-end mb-1';
      const dropdown=document.createElement('details');
      dropdown.className='mm-columns-picker';
      const toggle=document.createElement('summary');
      toggle.className='btn btn-primary btn-sm p-1';
      dropdown.appendChild(toggle);
      const menu=document.createElement('div');
      menu.className='mm-columns-picker__menu';
      dropdown.appendChild(menu);
      const inputs=[];
      const apply=()=>{
        for(const row of table.rows){
          if(row.cells.length!==columns.length)continue;
          [...row.cells].forEach((cell,i)=>{
            cell.hidden=!visible[i];cell.style.display=visible[i]?'':'none';
          });
        }
        toggle.textContent=visible.filter(Boolean).length+'/'+columns.length+' '+noun+' ▾';
        inputs.forEach((checkbox,i)=>checkbox.checked=visible[i]);
        try{localStorage.setItem(key,JSON.stringify(visible));}catch(_){}
      };
      columns.forEach((column,i)=>{
        const item=document.createElement('label');
        item.className='mm-columns-picker__item';
        const checkbox=document.createElement('input');
        checkbox.type='checkbox';checkbox.checked=visible[i];checkbox.disabled=locked[i];
        checkbox.addEventListener('change',()=>{
          visible[i]=checkbox.checked;
          if(!visible.some(Boolean)){visible[i]=true;checkbox.checked=true;}
          apply();
        });
        inputs.push(checkbox);
        item.append(checkbox,document.createTextNode(' '+labels[i]));
        menu.appendChild(item);
      });
      const reset=document.createElement('button');
      reset.type='button';reset.className='btn btn-outline-secondary btn-sm mt-2 w-100';
      reset.textContent=showAll;
      reset.addEventListener('click',()=>{visible=columns.map(()=>true);apply();});
      menu.appendChild(reset);
      toolbar.appendChild(dropdown);
      const parent=table.closest('.table-responsive')||table;
      parent.parentElement?.insertBefore(toolbar,parent);
      document.addEventListener('click',e=>{if(!dropdown.contains(e.target))dropdown.open=false;});
      apply();
    }
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready,{once:true});
  else ready();
})();
