/* Shared AJAX picker for internal orders/categories/products.
   Endpoints are same-origin Joomla admin and require a GET CSRF token. */
(() => {
'use strict';
function init(root) {
  if(root.dataset.mmReferenceReady==='1')return;
  root.dataset.mmReferenceReady='1';
  const search=root.querySelector('[data-mm-ref-search]');
  const results=root.querySelector('[data-mm-ref-results]');
  const selected=root.querySelector('[data-mm-ref-selected]');
  const feedback=root.querySelector('[data-mm-ref-feedback]');
  const input=root.querySelector('[data-mm-ref-input]');
  const fields=[...root.querySelectorAll('[data-mm-ref-filter]')];
  if(!search||!results||!selected||!input)return;
  const multiple=root.dataset.mmRefMultiple==='1';
  let controller=null;
  const labels=JSON.parse(root.dataset.mmRefLabels||'{}');
  const value=()=>[...selected.querySelectorAll('[data-mm-ref-key]')].map(x=>x.dataset.mmRefKey);
  const sync=()=>input.value=multiple?value().join(','):value()[0]||'';
  function add(item) {
    const key=String(item.key||item.id||'');
    if(!/^[1-9]\d{0,9}$/.test(key)||value().includes(key))return;
    if(!multiple)selected.replaceChildren();
    if(value().length>=100)return;
    const row=document.createElement('div');
    row.className='mm-entity-picker__row';
    row.dataset.mmRefKey=key;
    const title=document.createElement('span');
    title.className='flex-grow-1';
    title.textContent=String(item.title||'#'+key);
    row.append(title);
    if(item.subtitle) {
      const small=document.createElement('small');
      small.textContent=String(item.subtitle);
      row.append(small);
    }
    const remove=document.createElement('button');
    remove.type='button';remove.className='btn btn-danger btn-sm';
    remove.textContent='−';remove.setAttribute('aria-label',labels.remove||'Remove');
    remove.addEventListener('click',()=>{row.remove();sync();});
    row.append(remove);selected.append(row);sync();
  }
  async function run() {
    if(controller)controller.abort();
    controller=new AbortController();
    const url=new URL(root.dataset.mmRefUrl,location.href);
    url.searchParams.set('q',search.value.slice(0,100));
    for(const field of fields) if(field.value)url.searchParams.set(field.dataset.mmRefFilter,field.value);
    results.textContent=labels.loading||'Searching…';
    if(feedback)feedback.textContent='';
    try {
      const response=await fetch(url.toString(),{credentials:'same-origin',headers:{Accept:'application/json'},signal:controller.signal});
      if(!response.ok)throw new Error(String(response.status));
      const json=await response.json();
      results.replaceChildren();
      for(const item of json.items||[]) {
        const row=document.createElement('div');
        row.className='mm-entity-picker__result';
        const label=document.createElement('span');
        label.className='flex-grow-1';
        label.textContent=item.title+(item.subtitle?' · '+item.subtitle:'');
        row.append(label);
        const addButton=document.createElement('button');
        addButton.type='button';addButton.className='btn btn-success btn-sm';
        addButton.textContent='+';
        addButton.disabled=value().includes(String(item.key||item.id));
        addButton.setAttribute('aria-label',(labels.add||'Add')+' '+item.title);
        addButton.addEventListener('click',()=>{add(item);addButton.disabled=true;});
        row.append(addButton);results.append(row);
      }
      if(!results.children.length)results.textContent=labels.empty||'No results';
    } catch(error) {
      if(error.name!=='AbortError'&&feedback)feedback.textContent=labels.error||'Search failed';
    }
  }
  root.querySelector('[data-mm-ref-toggle]')?.addEventListener('click',()=>{
    const panel=root.querySelector('[data-mm-ref-panel]');
    if(panel){panel.hidden=!panel.hidden;if(!panel.hidden){search.focus();void run();}}
  });
  root.querySelector('[data-mm-ref-submit]')?.addEventListener('click',run);
  search.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();void run();}});
  fields.forEach(f=>f.addEventListener('change',run));
  sync();
}
function boot(){document.querySelectorAll('[data-mm-reference-picker]').forEach(init);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();