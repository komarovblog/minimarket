/* Product-local dimensions and SKU matrix: native Joomla form, no global attributes.
 * No mutation observer during idle, no automatic destruction of existing SKU rows.
 */
(() => {
  'use strict';
  const root=document.querySelector('[data-mm-local-builder]');
  if (!root) return;
  const input=root.querySelector('input[name="jform[variant_config]"]');
  if (!input) return;
  const axesBox=root.querySelector('[data-mm-axes]');
  const previewBox=root.querySelector('[data-mm-matrix-preview]');
  const feedback=root.querySelector('[data-mm-local-feedback]');
  const addAxis=root.querySelector('[data-mm-add-axis]');
  const generate=root.querySelector('[data-mm-generate]');
  const variantsEditor=root.closest('[data-mm-variants-editor]');
  const form=variantsEditor?.querySelector('.subform-repeatable');
  const nativeAdd=()=>Array.from(form?.querySelectorAll('button.group-add, a.group-add') || [])
    .find(button=>button.closest('.subform-repeatable')===form);
  // New local SKU rows are generated from combinations. The raw "+" would
  // create an unusable empty-selection SKU, so keep it for our generator only.
  const hideNativeAdd=()=> {
    const action=nativeAdd();
    if(action){
      action.hidden=true;
      action.setAttribute('aria-hidden','true');
    }
  };
  const labels=JSON.parse(root.dataset.mmLabels || '{}');
  const t=(key,fallback)=>labels[key] || fallback;
  const selectionSelector='input[type="hidden"][name^="jform[variants]"][name$="[selection]"]';
  let axes=[];
  try {
    const parsed=JSON.parse(input.value || '[]');
    axes=Array.isArray(parsed)?parsed:[];
  } catch (_) { axes=[]; }
  const by=(kind,parent=document)=>parent.querySelector(kind);
  const text=(name,value,className='')=>{
    const element=document.createElement('span');
    element.textContent=value;
    if(className)element.className=className;
    return element;
  };
  const button=(label,onClick,style='btn btn-outline-secondary btn-sm')=>{
    const b=document.createElement('button');
    b.type='button';b.className=style;b.textContent=label;b.addEventListener('click',onClick);
    return b;
  };
  const makeInput=(value,placeholder,onInput)=>{
    const i=document.createElement('input');
    i.type='text';i.className='form-control form-control-sm';i.value=value||'';
    i.placeholder=placeholder;
    i.addEventListener('input',()=>{onInput(i.value);save();});
    return i;
  };
  const usedCode=(prefix,codes)=>{
    let n=1;
    while(codes.has(prefix+n))n++;
    return prefix+n;
  };
  const save=()=>{input.value=JSON.stringify(axes);refreshLabels();};
  const currentValues=()=> {
    const all=[];
    const codes=new Set();
    let count=1;
    if(axes.length>4)throw new Error(t('tooMany','Maximum four parameters.'));
    for(const axis of axes){
      if(!axis.title?.trim() || !axis.values?.length || codes.has(axis.code))
        throw new Error(t('incomplete','Specify a name and at least one value for each parameter.'));
      codes.add(axis.code);
      const vals=new Set();
      const names=new Set();
      for(const value of axis.values){
        if(!value.title?.trim() || vals.has(value.code) || names.has(value.title.trim().toLocaleLowerCase()))
          throw new Error(t('incomplete','Fill every value without duplicates.'));
        vals.add(value.code);
        names.add(value.title.trim().toLocaleLowerCase());
      }
      count*=axis.values.length;
      if(count>200)throw new Error(t('limit','Maximum 200 combinations.'));
      all.push(axis);
    }
    if(!all.length)throw new Error(t('empty','First add a parameter, such as Size.'));
    return all;
  };
  const combinations=()=>{
    const dims=currentValues();
    let rows=[{title:'',selection:{}}];
    for(const dim of dims){
      const next=[];
      for(const item of rows)for(const value of dim.values){
        next.push({
          title:[item.title,value.title].filter(Boolean).join(' / '),
          selection:{...item.selection,[dim.code]:'v:'+value.code}
        });
      }
      rows=next;
    }
    return rows;
  };
  const canon=(selection)=>JSON.stringify(
    Object.fromEntries(Object.entries(selection).sort(([a],[b])=>a.localeCompare(b)))
  );
  const rowInputs=()=>Array.from(document.querySelectorAll(selectionSelector));
  const existingSelections=()=>new Set(rowInputs().map(h=>{
    try{return canon(JSON.parse(h.value || '{}'));}catch(_){return '';}
  }));
  const labelValue=(selection)=>{
    return axes.map(axis=>{
      const code=String(selection?.[axis.code] || '').replace(/^v:/,'');
      const option=(axis.values||[]).find(v=>v.code===code);
      return option?.title||'—';
    }).join(' / ');
  };
  const refreshLabels=()=>{
    for(const hidden of rowInputs()){
      let selection={};
      try{selection=JSON.parse(hidden.value || '{}')||{};}catch(_){}
      let marker=hidden.parentElement?.querySelector('[data-mm-local-selection]');
      if(!marker){
        marker=document.createElement('div');
        marker.dataset.mmLocalSelection='1';
        marker.className='small fw-semibold mb-1';
        hidden.parentElement?.insertBefore(marker,hidden);
      }
      const shown=labelValue(selection);
      if(marker.textContent!==shown)marker.textContent=shown;
    }
  };
  const draw=()=>{
    axesBox.replaceChildren();
    axes.forEach((axis,ai)=>{
      const card=document.createElement('div');card.className='card p-3 mb-2';
      const heading=document.createElement('div');heading.className='d-flex gap-2 align-items-center mb-2';
      heading.appendChild(text('',t('axis','Parameter'),'small fw-semibold'));
      heading.appendChild(makeInput(axis.title,t('axisExample','e.g. Size'),v=>axis.title=v));
      heading.appendChild(button('×',()=>{
        axes.splice(ai,1);save();draw();
      },'btn btn-outline-danger btn-sm'));
      card.appendChild(heading);
      const list=document.createElement('div');list.className='d-flex gap-2 flex-wrap mb-2';
      axis.values.forEach((value,vi)=>{
        const group=document.createElement('div');group.className='input-group input-group-sm';
        group.style.maxWidth='190px';
        group.appendChild(makeInput(value.title,t('value','Value'),v=>value.title=v));
        group.appendChild(button('×',()=>{
          axis.values.splice(vi,1);save();draw();
        },'btn btn-outline-danger btn-sm'));
        list.appendChild(group);
      });
      card.appendChild(list);
      card.appendChild(button('+ '+t('addValue','Add value'),()=>{
        const codes=new Set(axis.values.map(v=>v.code));
        axis.values.push({code:usedCode('value',codes),title:''});
        save();draw();
      }));
      axesBox.appendChild(card);
    });
    refreshLabels();
  };
  const show=(message,isError=false)=>{
    if(feedback.textContent!==message)feedback.textContent=message;
    feedback.classList.toggle('text-danger',isError);
  };
  const buildPreview=()=>{
    try{
      const rows=combinations();
      const exist=existingSelections();
      const missing=rows.filter(row=>!exist.has(canon(row.selection)));
      previewBox.replaceChildren();
      const panel=document.createElement('div');panel.className='card p-3';
      panel.appendChild(text('',t('preview','New SKU combinations:')+' '+missing.length+' / '+rows.length,'fw-semibold mb-2'));
      const wrap=document.createElement('div');wrap.className='d-flex flex-wrap gap-2 mb-2';
      missing.forEach(row=>{
        const chip=document.createElement('label');chip.className='form-check me-3';
        const ck=document.createElement('input');ck.type='checkbox';ck.checked=true;ck.className='form-check-input';
        ck.dataset.mmCombo=canon(row.selection);
        chip.appendChild(ck);chip.appendChild(text('',row.title,'form-check-label ms-1'));wrap.appendChild(chip);
      });
      panel.appendChild(wrap);
      panel.appendChild(button(t('confirm','Add selected SKU rows'),async()=>{
        const selected=new Set(Array.from(panel.querySelectorAll('input[data-mm-combo]:checked')).map(x=>x.dataset.mmCombo));
        const pending=missing.filter(x=>selected.has(canon(x.selection)));
        const action=nativeAdd();
        if(!form || !action){show(t('nativeUnavailable','Cannot locate Joomla SKU editor.'),true);return;}
        try{
          for(const row of pending) await appendSku(action,row);
          show(t('created','SKU rows added. Fill in or adjust SKU, price, stock, then Save.'));
          previewBox.replaceChildren();refreshLabels();
        }catch(error){show(error.message,true);}
      },'btn btn-primary btn-sm'));
      previewBox.appendChild(panel);
      show('');
    }catch(error){previewBox.replaceChildren();show(error.message,true);}
  };
  const waitRow=(existing,limit=3000)=>new Promise((resolve,reject)=>{
    const found=()=>rowInputs().find(x=>!existing.has(x));
    const initial=found();if(initial){resolve(initial);return;}
    if(!form){reject(new Error('Joomla subform unavailable'));return;}
    let timer;
    const observer=new MutationObserver(()=>{
      const added=found();
      if(!added)return;
      observer.disconnect();clearTimeout(timer);resolve(added);
    });
    observer.observe(form,{subtree:true,childList:true});
    timer=setTimeout(()=>{
      observer.disconnect();reject(new Error(t('addFailed','Cannot add a Joomla variant row.')));
    },limit);
  });
  const appendSku=async(add,row)=>{
    const before=new Set(rowInputs());
    add.click();
    const field=await waitRow(before);
    field.value=JSON.stringify(row.selection);
    field.dispatchEvent(new Event('change',{bubbles:true}));
    const fieldName=field.name;
    const set=(name,value)=>{
      const target=Array.from(document.querySelectorAll('input[name]')).find(
        x=>x.name===fieldName.replace('[selection]','['+name+']')
      );
      if(target && !target.value) target.value=value;
    };
    const base=by('[name="jform[sku]"]')?.value || 'SKU';
    const slug=row.title.split(' / ').map(v=>{
      const part=v.trim().normalize('NFKD').replace(/[^a-zA-Z0-9]+/g,'-')
        .replace(/^-|-$/g,'').toUpperCase();
      return part || 'VAR';
    }).join('-');
    set('sku',base+'-'+slug);
    set('price',by('[name="jform[price]"]')?.value || '0.00');
    set('stock','0');
  };
  addAxis?.addEventListener('click',()=>{
    if(axes.length>=4){show(t('tooMany','Maximum four parameters.'),true);return;}
    const codes=new Set(axes.map(x=>x.code));
    axes.push({code:usedCode('axis',codes),title:'',values:[{code:'value1',title:''}]});
    save();draw();show('');
  });
  generate?.addEventListener('click',buildPreview);
  // Native Joomla may insert/reorder SKU rows; only refresh labels on real
  // new selection inputs, never mutate the form continuously.
  if(form){
    const observer=new MutationObserver(changes=>{
      if(changes.some(rec=>Array.from(rec.addedNodes).some(node=>
        node.nodeType===1 && (node.matches?.(selectionSelector) ||
          !!node.querySelector?.(selectionSelector)))))refreshLabels();
    });
    observer.observe(form,{childList:true,subtree:true});
  }
  draw();
  hideNativeAdd();
})();
