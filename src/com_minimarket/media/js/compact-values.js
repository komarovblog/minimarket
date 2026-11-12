/* Compact native Joomla Subform editors. Keeps every existing field in DOM. */
(() => {
 'use strict';
 const boot=()=>{
  const form=document.getElementById('adminForm');
  if(!form)return;
  const type=form.querySelector('[name="jform[input_type]"]');
  const compact=()=>{
   const documentType=type?.value==='document';
   for(const group of form.querySelectorAll('.subform-repeatable-group')){
    const title=group.querySelector('input[name$="[title]"]');
    if(!title)continue;
    const file=group.querySelector('[name$="[document_file]"]');
    const fileField=file?.closest('.control-group');
    if(fileField){
      fileField.hidden=!documentType;
      fileField.style.display=documentType?'':'none';
    }
    if(group.dataset.mmCompactReady==='1')continue;
    group.dataset.mmCompactReady='1';
    const id=group.querySelector('input[name$="[id]"]');
    const details=document.createElement('details');
    details.className='mm-value-details';
    details.open=!(Number(id?.value)>0);
    const summary=document.createElement('summary');
    summary.className='mm-value-summary';
    const label=document.createElement('span');
    const description=document.createElement('small');
    description.className='text-muted';
    summary.append(label,description);
    const refresh=()=>{
      const fallback=(typeof Joomla!=='undefined'&&Joomla.Text&&Joomla.Text._)
        ?Joomla.Text._('COM_MINIMARKET_VALUE_NEW'):'New value';
      label.textContent=title.value.trim()||fallback;
      const code=group.querySelector('input[name$="[code]"]');
      description.textContent=code?.value.trim()?' · '+code.value.trim():'';
    };
    title.addEventListener('input',refresh);
    group.querySelector('input[name$="[code]"]')?.addEventListener('input',refresh);
    details.append(summary);
    const contents=document.createElement('div');
    contents.className='mm-value-details-body';
    for(const element of Array.from(group.children)){
      if(element.classList?.contains('control-group') ||
         element.classList?.contains('form-group') ||
         element.classList?.contains('mb-3'))contents.append(element);
    }
    details.append(contents);
    group.prepend(details);
    refresh();
   }
  };
  type?.addEventListener('change',compact);
  compact();
  if(typeof MutationObserver==='function'){
   const observer=new MutationObserver(compact);
   observer.observe(form,{subtree:true,childList:true});
  }
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});
 else boot();
})();