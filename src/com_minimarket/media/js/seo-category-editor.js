/* Native Joomla SEO landing editor. Three states for each category-bound facet:
 * unused (not applied), any (applied, arbitrary value), exact (one value).
 * Nothing is trusted client-side: server normalizes and validates all rules.
 */
(() => {
  'use strict';
  const form = document.getElementById('mm-seo-editor');
  const area = document.getElementById('mm-seo-category-choices');
  const output = document.getElementById('mm-seo-conditions');
  if (!form || !area || !output) return;
  const category = form.querySelector('input[name="category_id"]');
  const selected = form.querySelector('[data-mm-reference-picker] [data-mm-ref-selected]');
  const warning = form.querySelector('#mm-seo-rule-warning');
  const originalCategory = Number(area.dataset.mmSeoOriginalCategory || 0);
  let legacy = {}, conditions = {};
  try { legacy = JSON.parse(area.dataset.mmSeoSaved || '{}'); } catch (_) {}
  try { conditions = JSON.parse(area.dataset.mmSeoConditions || '{}'); } catch (_) {}
  if (!legacy || typeof legacy !== 'object') legacy = {};
  if (!conditions || typeof conditions !== 'object') conditions = {};
  const isModern = Object.keys(conditions).length > 0;
  let activeCategory = 0, sequence = 0, request = null, ready = false;
  const unresolved = new Set();
  const stateLabels = {
    unused: area.dataset.mmSeoUnused || 'Not used',
    any: area.dataset.mmSeoAny || 'Any selected value',
    exact: area.dataset.mmSeoExact || 'Exact value'
  };
  const make = (tag,cls,text) => {
    const node=document.createElement(tag);
    if(cls)node.className=cls;
    if(text!==undefined)node.textContent=String(text);
    return node;
  };
  const notice = (text,kind) => {
    if(!warning)return;
    warning.hidden=!text;
    if(text)warning.textContent=text;
    warning.className='alert alert-'+(kind||'warning');
  };
  const message = (text,error=false) => {
    area.replaceChildren(make('p',error?'text-danger':'form-text',text));
  };
  const prior = (id,kind) => {
    if(activeCategory!==originalCategory)return null;
    if(isModern)return conditions[id] || null;
    const raw=legacy[id];
    if(raw===undefined)return null;
    if(id==='price' && raw && typeof raw==='object' && !Array.isArray(raw))
      return {mode:'exact',value:raw};
    if(id==='sku' && typeof raw==='string' && raw.trim()!=='')
      return {mode:'exact',value:raw.trim()};
    if(kind==='range' && raw && typeof raw==='object' && !Array.isArray(raw))
      return {mode:'exact',value:raw};
    if(kind==='text' && typeof raw==='string' && raw.trim()!=='')
      return {mode:'exact',value:{text:raw.trim()}};
    if(Array.isArray(raw) && raw.length===1)
      return {mode:'exact',value:raw[0]};
    if(Array.isArray(raw) && raw.length>1){
      unresolved.add(id);
      return null;
    }
    return null;
  };
  const addStates = (id,initial) => {
    const select=make('select','form-select');
    select.dataset.mmSeoState=id;
    for(const key of ['unused','any','exact']){
      const option=make('option','',stateLabels[key]);
      option.value=key;select.append(option);
    }
    select.value=initial?.mode||'unused';
    return select;
  };
  function syncSelect(select) {
    const key=select.dataset.mmSeoState;
    const details=form.querySelectorAll('[data-mm-seo-details]');
    for(const node of details){
      if(node.dataset.mmSeoDetails===key)
        node.hidden=select.value!=='exact';
    }
  }
  function prepareSystem(){
    for(const key of ['price','stock','sku']){
      const priorValue=prior(key);
      const state=form.querySelector('[data-mm-seo-state="'+key+'"]');
      if(!state)continue;
      state.value=priorValue?.mode||'unused';
      const value=priorValue?.value;
      if(key==='price' && value && typeof value==='object'){
        for(const bound of ['min','max']){
          const field=form.querySelector('[data-mm-seo-range="price"][data-mm-bound="'+bound+'"]');
          if(field)field.value=value[bound]??'';
        }
      }else if(key==='stock'){
        const field=form.querySelector('[data-mm-seo-choice="stock"]');
        if(field && typeof value==='string')field.value=value;
      }else if(key==='sku'){
        const field=form.querySelector('[data-mm-seo-text="sku"]');
        if(field && typeof value==='string')field.value=value;
      }
      syncSelect(state);
    }
  }
  function render(groups,id){
    area.replaceChildren();
    if(!groups.length){
      message(area.dataset.mmSeoNone||'No attributes or options for this category');
      ready=true;return;
    }
    for(const group of groups){
      if(!/^[as][1-9][0-9]*$/.test(String(group.key)))continue;
      const key=String(group.key),kind=String(group.kind||'choices');
      const previous=prior(key,kind);
      const fieldset=make('fieldset','border rounded p-3 mb-3');
      const legend=make('legend','float-none w-auto px-2 fs-6',group.title||key);
      const row=make('div','row g-2 align-items-start');
      const stateBox=make('div','col-12 col-md-5');
      const state=addStates(key,previous);
      state.setAttribute('aria-label',String(group.title||key));
      stateBox.append(state);
      const details=make('div','col-12 col-md-7');
      details.dataset.mmSeoDetails=key;
      details.dataset.mmSeoKind=kind;
      let choice;
      if(kind==='range'){
        const range=make('div','row g-2');
        for(const bound of ['min','max']){
          const col=make('div','col-6');
          const label=make('label','form-label small',bound==='min'?'Min':'Max');
          const input=make('input','form-control');
          input.type='number';input.min='0';input.step='any';
          input.dataset.mmSeoNumeric=key;input.dataset.mmBound=bound;
          if(previous?.value && typeof previous.value==='object')
            input.value=String(previous.value[bound]??'');
          label.append(input);col.append(label);range.append(col);
        }
        details.append(range);
      }else if(kind==='text'||kind==='text-token'){
        choice=make('input','form-control');
        choice.type='text';choice.maxLength=100;
        choice.dataset.mmSeoText=key;
        const raw=previous?.value;
        choice.value=kind==='text'
          ?(typeof raw==='object'?String(raw?.text||''):'')
          :(typeof raw==='string'?raw.replace(/^t:/,''):'');
        choice.setAttribute('aria-label',stateLabels.exact);
        details.append(choice);
      }else{
        choice=make('select','form-select');
        choice.dataset.mmSeoChoice=key;
        const placeholder=make('option','',area.dataset.mmSeoExactValue||'Choose value');
        placeholder.value='';choice.append(placeholder);
        for(const value of group.values||[]){
          const option=make('option','',value.title||value.token);
          option.value=String(value.token||'');choice.append(option);
        }
        if(typeof previous?.value==='string')choice.value=previous.value;
        details.append(choice);
      }
      row.append(stateBox,details);fieldset.append(legend,row);area.append(fieldset);
      syncSelect(state);
    }
    ready=true;
    if(unresolved.size)notice(area.dataset.mmSeoLegacyWarning
      ||'This older page has several values in one filter. Review and replace them with one rule.');
    else notice('');
  }
  async function refresh(){
    const id=Number(category?.value||0), seq=++sequence;
    if(request)request.abort();
    request=null;ready=false;activeCategory=id;unresolved.clear();
    if(!Number.isSafeInteger(id)||id<1){
      message(area.dataset.mmSeoChoose||'Choose a category');notice('');return;
    }
    const ctrl=new AbortController();request=ctrl;
    message(area.dataset.mmSeoLoading||'Loading…');
    const url=new URL(area.dataset.mmSeoChoicesUrl,location.href);
    url.searchParams.set('category_id',String(id));
    try{
      const response=await fetch(url.toString(),{
        credentials:'same-origin',headers:{Accept:'application/json'},signal:ctrl.signal
      });
      if(!response.ok)throw new Error(String(response.status));
      const result=await response.json();
      if(seq!==sequence)return;
      render(Array.isArray(result.groups)?result.groups:[],id);
      prepareSystem();
    }catch(error){
      if(seq!==sequence||error.name==='AbortError')return;
      message(area.dataset.mmSeoError||'Failed loading choices',true);
      notice('');ready=false;
    }
  }
  // Single category selection is owned by the existing native reference picker.
  if(selected)new MutationObserver(()=>{void refresh();})
    .observe(selected,{childList:true});
  category?.addEventListener('change',()=>{void refresh();});
  form.addEventListener('change',event=>{
    const target=event.target;
    if(target.matches('[data-mm-seo-state]')){
      syncSelect(target);
      unresolved.delete(target.dataset.mmSeoState);
      if(!unresolved.size)notice('');
    }
  });
  const exact = (key) => {
    if(key==='price'){
      const result={};
      for(const bound of ['min','max']){
        const value=form.querySelector('[data-mm-seo-range="price"][data-mm-bound="'+bound+'"]')?.value.trim()??'';
        if(value!=='')result[bound]=value;
      }
      if(!Object.keys(result).length)throw new Error(stateLabels.exact+' — price');
      return result;
    }
    if(key==='stock'){
      return form.querySelector('[data-mm-seo-choice="stock"]')?.value||'';
    }
    if(key==='sku'){
      const text=form.querySelector('[data-mm-seo-text="sku"]')?.value.trim()||'';
      if(!text)throw new Error(stateLabels.exact+' — SKU');
      return text;
    }
    const number=form.querySelector('[data-mm-seo-numeric="'+key+'"]');
    if(number){
      const result={};
      for(const field of form.querySelectorAll('[data-mm-seo-numeric="'+key+'"]')){
        if(field.value.trim()!=='')result[field.dataset.mmBound]=field.value.trim();
      }
      if(!Object.keys(result).length)throw new Error(stateLabels.exact+' — '+key);
      return result;
    }
    const text=form.querySelector('[data-mm-seo-text="'+key+'"]');
    if(text){
      const value=text.value.trim();
      if(!value)throw new Error(stateLabels.exact+' — '+key);
      const kind=text.closest('[data-mm-seo-details]')?.dataset.mmSeoKind||'';
      return kind==='text'?{text:value}:'t:'+value;
    }
    const value=form.querySelector('[data-mm-seo-choice="'+key+'"]')?.value||'';
    if(!value)throw new Error(stateLabels.exact+' — '+key);
    return value;
  };
  form.addEventListener('submit',event=>{
    if(!ready||unresolved.size){
      event.preventDefault();
      notice(unresolved.size?(area.dataset.mmSeoLegacyWarning||'Review legacy filters')
        :(area.dataset.mmSeoError||'Unable to load filters'));
      return;
    }
    const conditions={};let count=0;
    try{
      for(const state of form.querySelectorAll('[data-mm-seo-state]')){
        const key=state.dataset.mmSeoState;
        if(state.value==='unused')continue;
        if(state.value==='any')conditions[key]={mode:'any'};
        else if(state.value==='exact'){
          conditions[key]={mode:'exact',value:exact(key)};
          count++;
        }
      }
      if(count===0)throw new Error(area.dataset.mmSeoNeedsExact
        ||'At least one exact value is required.');
      output.value=JSON.stringify(conditions);
      notice('');
    }catch(error){
      event.preventDefault();
      notice(String(error.message||error));
    }
  });
  void refresh();
})();
