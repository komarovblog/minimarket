/* Facet filtering progressive enhancement: server GET remains the fallback.
 * Live product visibility and incompatible options use current published facts.
 */
(() => {
  'use strict';
  const init = () => {
    const form=document.querySelector('[data-mm-facet-form]');
    const source=document.querySelector('script[data-mm-facet-data]');
    const layout=document.querySelector('[data-mm-facet-layout]');
    if (!form || !source || !layout) return;
    let data;
    try { data=JSON.parse(source.textContent); } catch (_) { return; }
    const facets=data.facets||{}, facts=data.facts||{}, skus=data.skus||{};
    const rows=Array.from(layout.querySelectorAll('[data-mm-facet-product]'));
    const empty=layout.querySelector('[data-mm-facet-empty]');
    const counter=form.querySelector('[data-mm-facet-count]');
    const groups=Array.from(form.querySelectorAll('[data-mm-facet]'));
    const sortControl=document.querySelector('[data-mm-sort]');
    if (sortControl) sortControl.addEventListener('change',()=>sortControl.form?.requestSubmit());
    // The filter value search changes only the option list, not the catalog.
    for (const search of form.querySelectorAll('[data-mm-choice-search]')) {
      search.addEventListener('input',()=>{
        const needle=search.value.trim().toLocaleLowerCase();
        const group=search.closest('[data-mm-facet]');
        for (const row of group?.querySelectorAll('[data-mm-facet-choice-row]')||[]) {
          const label=row.querySelector('label')?.textContent?.toLocaleLowerCase()||'';
          row.hidden=!!needle && !label.includes(needle);
        }
      });
    }

    const state=()=>{
      const selected={};
      for (const group of groups) {
        const id=group.dataset.mmFacet, mode=group.dataset.mmFacetMode;
        if (mode==='text') {
          const text=group.querySelector('[data-mm-facet-text]')?.value.trim();
          if (text) selected[id]={text};
        } else if (mode==='range' || mode==='slider') {
          const bounds={};
          for (const input of group.querySelectorAll('[data-mm-facet-bound]')) {
            if (input.value.trim()!=='' && Number.isFinite(Number(input.value))) {
              bounds[input.dataset.mmFacetBound]=Number(input.value);
            }
          }
          if (Object.keys(bounds).length) selected[id]=bounds;
        } else {
          const values=Array.from(group.querySelectorAll('[data-mm-facet-value]'))
            .filter(input=>input.tagName==='OPTION'?input.selected:input.checked)
            .map(input=>input.value).filter(Boolean);
          if (values.length) selected[id]={values};
        }
      }
      return selected;
    };
    const matches=(pid,selected)=>{
      const productFacts=facts[pid]||{}, productSkus=skus[pid]||[], skuRules=[];
      for (const [id,filter] of Object.entries(selected)) {
        const facet=facets[id];
        if (!facet) continue;
        if (facet.type==='sku') {
          if (filter.values?.length) skuRules.push([id,filter.values]);
          continue;
        }
        const values=productFacts[id]||[];
        if (facet.mode==='text' && filter.text) {
          const needle=filter.text.toLocaleLowerCase();
          if (!values.some(v=>String(v).toLocaleLowerCase().includes(needle))) return false;
        } else if (['slider','range'].includes(facet.mode) && ('min' in filter || 'max' in filter)) {
          if (!values.some(v=>Number.isFinite(Number(v))
             && (!('min' in filter) || Number(v)>=filter.min)
             && (!('max' in filter) || Number(v)<=filter.max))) return false;
        } else if (filter.values?.length && !filter.values.some(v=>values.includes(v))) {
          return false;
        }
      }
      return skuRules.length===0 || productSkus.some(sku=>
        skuRules.every(([id,values])=>values.includes(sku[id])));
    };
    const update=(updateUrl=false)=>{
      const selected=state();
      let visible=0;
      for (const row of rows) {
        const yes=matches(row.dataset.mmFacetProduct,selected);
        row.hidden=!yes;
        if (yes) visible++;
      }
      if (empty) empty.hidden=visible>0;
      if (counter) {
        const template=counter.dataset.mmFacetLabel||'{count}';
        counter.textContent=template.replace('{count}',String(visible));
      }
      // Compute option compatibility ignoring the current facet. This allows
      // multi-select OR inside the group and AND against all other groups.
      for (const group of groups) {
        const id=group.dataset.mmFacet, facet=facets[id];
        if (!facet) continue;
        const other={...selected};delete other[id];
        const groupCount=group.querySelector('[data-mm-facet-group-count]');
        if (groupCount) groupCount.textContent=String(rows.reduce((n,row)=>
          n+(matches(row.dataset.mmFacetProduct,other)?1:0),0));
        if (['text','range','slider'].includes(facet.mode)) continue;
        const summary=group.querySelector('.mm-facet-dropdown summary');
        if (summary) {
          const labels=Array.from(group.querySelectorAll('[data-mm-facet-value]:checked'))
            .map(input=>input.closest('[data-mm-facet-choice-row]')?.querySelector('label')?.textContent?.trim())
            .filter(Boolean);
          summary.textContent=labels.join(', ')||'–';
        }
        for (const control of group.querySelectorAll('[data-mm-facet-value]')) {
          const candidate={...other,[id]:{values:[control.value]}};
          const count=rows.reduce((total,row)=>
            total+(matches(row.dataset.mmFacetProduct,candidate)?1:0),0);
          const active=control.tagName==='OPTION'?control.selected:control.checked;
          control.disabled=count===0 && !active;
          const row=control.closest('[data-mm-facet-choice-row]');
          row?.classList.toggle('mm-facet-choice--unavailable',count===0 && !active);
          const badge=row?.querySelector('[data-mm-choice-count]');
          if (badge) badge.textContent=String(count);
        }
      }
      if (updateUrl) {
        const url=new URL(form.action,window.location.href);
        for (const [key,value] of new FormData(form)) {
          if (String(value).trim()!=='') url.searchParams.append(key,String(value));
        }
        if (sortControl && sortControl.value !== 'newest')
          url.searchParams.set('mm_order',sortControl.value);
        history.replaceState(null,'',url);
      }
    };
    let timer;
    const delayed=()=>{clearTimeout(timer);timer=setTimeout(()=>update(true),160);};
    form.addEventListener('change',event=>{
      const slider=event.target.closest('[data-mm-facet-slider]');
      if (slider) {
        const group=slider.closest('[data-mm-facet]');
        const input=group?.querySelector('[data-mm-facet-bound="'+slider.dataset.mmFacetSlider+'"]');
        if (input) input.value=slider.value;
      }
      update(true);
    });
    form.addEventListener('input',event=>{
      if (event.target.matches('[data-mm-choice-search]')) return;
      const slider=event.target.closest('[data-mm-facet-slider]');
      if (slider) {
        const input=slider.closest('[data-mm-facet]')?.querySelector(
          '[data-mm-facet-bound="'+slider.dataset.mmFacetSlider+'"]');
        if (input) input.value=slider.value;
      }
      delayed();
    });
    form.querySelector('[data-mm-facet-reset]')?.addEventListener('click',event=>{
      event.preventDefault();
      form.reset();
      for (const field of form.querySelectorAll('[data-mm-facet-bound]')) field.value='';
      for (const search of form.querySelectorAll('[data-mm-choice-search]')) {
        search.value='';
        for (const row of search.closest('[data-mm-facet]').querySelectorAll('[data-mm-facet-choice-row]')) row.hidden=false;
      }
      update(true);
    });
    update(false);
  };
  if (document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
  else init();
})();
