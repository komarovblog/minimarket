/* Show actual inherited values next to Inherit in every entity editor.
 * The parent global value is rendered by Joomla, not fetched from browser. */
(() => {
  'use strict';
  const start=()=>{
    for(const form of document.querySelectorAll('form[data-mm-inherited]')){
      let inherited;
      try{inherited=JSON.parse(form.dataset.mmInherited||'{}');}catch(_){continue;}
      const prefix=(document.documentElement.lang||'').toLowerCase().startsWith('ru')
        ?'Наследовать':'Inherit';
      for(const [field,value] of Object.entries(inherited)){
        if(typeof value!=='string'||!value)continue;
        const select=form.querySelector('select[name="jform['+CSS.escape(field)+']"]');
        if(!select)continue;
        const option=[...select.options].find(o=>
          (o.value==='inherit'||o.value==='-1'||o.value==='0'||o.value==='') &&
          (o.textContent||'').toLowerCase().includes(prefix.toLowerCase()));
        if(option)option.textContent=prefix+' ('+value+')';
      }
    }
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});
  else start();
})();
